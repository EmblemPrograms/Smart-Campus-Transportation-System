<?php
/**
 * Booking Model
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Booking lifecycle: create, cancel, approve, complete, seat checks, tickets.
 */

declare(strict_types=1);

class Booking
{
    /**
     * Find a booking by primary key with related details.
     *
     * @param int $id Booking ID
     * @return array|null Booking row or null
     */
    public static function findById(int $id): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT b.*,
                        u.full_name AS user_name, u.email AS user_email, u.phone AS user_phone,
                        s.departure_time, s.arrival_time, s.days_of_week,
                        r.name AS route_name, r.code AS route_code,
                        pl.name AS pickup_name, dl.name AS destination_name,
                        v.plate_number, du.full_name AS driver_name
                 FROM bookings b
                 INNER JOIN users u ON u.id = b.user_id
                 INNER JOIN schedules s ON s.id = b.schedule_id
                 INNER JOIN routes r ON r.id = s.route_id
                 INNER JOIN locations pl ON pl.id = b.pickup_location_id
                 INNER JOIN locations dl ON dl.id = b.destination_location_id
                 LEFT JOIN vehicles v ON v.id = s.vehicle_id
                 LEFT JOIN drivers d ON d.id = s.driver_id
                 LEFT JOIN users du ON du.id = d.user_id
                 WHERE b.id = ? LIMIT 1'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Booking::findById failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a booking by booking number.
     *
     * @param string $bookingNumber Unique booking number
     * @return array|null Booking row or null
     */
    public static function findByNumber(string $bookingNumber): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('SELECT * FROM bookings WHERE booking_number = ? LIMIT 1');
            $stmt->bind_param('s', $bookingNumber);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Booking::findByNumber failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check whether a seat is available for a schedule on a given date.
     *
     * @param int $scheduleId Schedule ID
     * @param string $date Booking date (Y-m-d)
     * @param string $seatNumber Seat number
     * @return bool True if seat is free
     */
    public static function checkSeatAvailability(int $scheduleId, string $date, string $seatNumber): bool
    {
        try {
            $db = getDB();

            // Prefer seats table if rows exist for this schedule/date
            $seatStmt = $db->prepare(
                'SELECT id, is_available, booking_id
                 FROM seats
                 WHERE schedule_id = ? AND trip_date = ? AND seat_number = ?
                 LIMIT 1'
            );
            $seatStmt->bind_param('iss', $scheduleId, $date, $seatNumber);
            $seatStmt->execute();
            $seat = $seatStmt->get_result()->fetch_assoc();
            $seatStmt->close();

            if ($seat) {
                return (int) $seat['is_available'] === 1 && empty($seat['booking_id']);
            }

            // Fall back to bookings table
            $stmt = $db->prepare(
                'SELECT id FROM bookings
                 WHERE schedule_id = ? AND booking_date = ? AND seat_number = ?
                   AND status IN (?, ?)
                 LIMIT 1'
            );
            $pending = BOOKING_PENDING;
            $approved = BOOKING_APPROVED;
            $stmt->bind_param('issss', $scheduleId, $date, $seatNumber, $pending, $approved);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $exists === null;
        } catch (Throwable $e) {
            error_log('Booking::checkSeatAvailability failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a booking with seat allocation.
     *
     * @param array $data Booking fields
     * @return int|false New booking ID or false
     */
    public static function create(array $data)
    {
        $db = getDB();

        try {
            $scheduleId = (int) ($data['schedule_id'] ?? 0);
            $date = (string) ($data['booking_date'] ?? '');
            $seatNumber = (string) ($data['seat_number'] ?? '');

            if ($seatNumber === '' || !self::checkSeatAvailability($scheduleId, $date, $seatNumber)) {
                return false;
            }

            $db->begin_transaction();

            $bookingNumber = (string) ($data['booking_number'] ?? generateBookingNumber());
            $userId = (int) ($data['user_id'] ?? 0);
            $pickupId = (int) ($data['pickup_location_id'] ?? 0);
            $destId = (int) ($data['destination_location_id'] ?? 0);
            $isPriority = (int) ($data['is_priority'] ?? 0);
            $status = (string) ($data['status'] ?? BOOKING_PENDING);
            $notes = (string) ($data['notes'] ?? '');

            $stmt = $db->prepare(
                'INSERT INTO bookings
                    (booking_number, user_id, schedule_id, pickup_location_id, destination_location_id,
                     booking_date, seat_number, is_priority, status, notes, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->bind_param(
                'siiiississ',
                $bookingNumber,
                $userId,
                $scheduleId,
                $pickupId,
                $destId,
                $date,
                $seatNumber,
                $isPriority,
                $status,
                $notes
            );
            $ok = $stmt->execute();
            $bookingId = $ok ? (int) $stmt->insert_id : 0;
            $stmt->close();

            if (!$ok || $bookingId <= 0) {
                $db->rollback();
                return false;
            }

            // Optional booking details
            if (!empty($data['passenger_name']) || !empty($data['passenger_phone']) || !empty($data['special_request'])) {
                $pName = (string) ($data['passenger_name'] ?? '');
                $pPhone = (string) ($data['passenger_phone'] ?? '');
                $special = (string) ($data['special_request'] ?? '');
                $detailStmt = $db->prepare(
                    'INSERT INTO booking_details (booking_id, passenger_name, passenger_phone, special_request, created_at)
                     VALUES (?, ?, ?, ?, NOW())'
                );
                $detailStmt->bind_param('isss', $bookingId, $pName, $pPhone, $special);
                $detailStmt->execute();
                $detailStmt->close();
            }

            // Mark seat as taken if seats row exists
            $updateSeat = $db->prepare(
                'UPDATE seats
                 SET is_available = 0, booking_id = ?
                 WHERE schedule_id = ? AND trip_date = ? AND seat_number = ?'
            );
            $updateSeat->bind_param('iiss', $bookingId, $scheduleId, $date, $seatNumber);
            $updateSeat->execute();
            $updateSeat->close();

            $db->commit();
            return $bookingId;
        } catch (Throwable $e) {
            if ($db->errno || $db->connect_errno === 0) {
                try {
                    $db->rollback();
                } catch (Throwable $ignored) {
                }
            }
            error_log('Booking::create failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel a booking and free its seat.
     *
     * @param int $id Booking ID
     * @param int|null $cancelledBy User ID who cancelled
     * @return bool Success
     */
    public static function cancel(int $id, ?int $cancelledBy = null): bool
    {
        $db = getDB();

        try {
            $booking = self::findById($id);
            if (!$booking || in_array($booking['status'], [BOOKING_CANCELLED, BOOKING_COMPLETED], true)) {
                return false;
            }

            $db->begin_transaction();

            $status = BOOKING_CANCELLED;
            $stmt = $db->prepare(
                'UPDATE bookings
                 SET status = ?, cancelled_at = NOW(), cancelled_by = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->bind_param('sii', $status, $cancelledBy, $id);
            $ok = $stmt->execute();
            $stmt->close();

            if (!$ok) {
                $db->rollback();
                return false;
            }

            $scheduleId = (int) $booking['schedule_id'];
            $date = (string) $booking['booking_date'];
            $seatNumber = (string) $booking['seat_number'];

            $seatStmt = $db->prepare(
                'UPDATE seats
                 SET is_available = 1, booking_id = NULL
                 WHERE schedule_id = ? AND trip_date = ? AND seat_number = ? AND booking_id = ?'
            );
            $seatStmt->bind_param('issi', $scheduleId, $date, $seatNumber, $id);
            $seatStmt->execute();
            $seatStmt->close();

            $db->commit();
            return true;
        } catch (Throwable $e) {
            try {
                $db->rollback();
            } catch (Throwable $ignored) {
            }
            error_log('Booking::cancel failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Approve a pending booking.
     *
     * @param int $id Booking ID
     * @return bool Success
     */
    public static function approve(int $id): bool
    {
        try {
            $db = getDB();
            $status = BOOKING_APPROVED;
            $pending = BOOKING_PENDING;
            $stmt = $db->prepare(
                'UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ? AND status = ?'
            );
            $stmt->bind_param('sis', $status, $id, $pending);
            $ok = $stmt->execute() && $stmt->affected_rows > 0;
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Booking::approve failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark a booking as completed.
     *
     * @param int $id Booking ID
     * @return bool Success
     */
    public static function complete(int $id): bool
    {
        try {
            $db = getDB();
            $status = BOOKING_COMPLETED;
            $approved = BOOKING_APPROVED;
            $stmt = $db->prepare(
                'UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ? AND status = ?'
            );
            $stmt->bind_param('sis', $status, $id, $approved);
            $ok = $stmt->execute() && $stmt->affected_rows > 0;
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Booking::complete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get bookings for a specific user.
     *
     * @param int $userId User ID
     * @param string|null $status Optional status filter
     * @return array List of bookings
     */
    public static function getByUser(int $userId, ?string $status = null): array
    {
        try {
            $db = getDB();
            if ($status !== null && $status !== '') {
                $stmt = $db->prepare(
                    'SELECT b.*, r.name AS route_name, s.departure_time,
                            pl.name AS pickup_name, dl.name AS destination_name
                     FROM bookings b
                     INNER JOIN schedules s ON s.id = b.schedule_id
                     INNER JOIN routes r ON r.id = s.route_id
                     INNER JOIN locations pl ON pl.id = b.pickup_location_id
                     INNER JOIN locations dl ON dl.id = b.destination_location_id
                     WHERE b.user_id = ? AND b.status = ?
                     ORDER BY b.booking_date DESC, s.departure_time DESC'
                );
                $stmt->bind_param('is', $userId, $status);
            } else {
                $stmt = $db->prepare(
                    'SELECT b.*, r.name AS route_name, s.departure_time,
                            pl.name AS pickup_name, dl.name AS destination_name
                     FROM bookings b
                     INNER JOIN schedules s ON s.id = b.schedule_id
                     INNER JOIN routes r ON r.id = s.route_id
                     INNER JOIN locations pl ON pl.id = b.pickup_location_id
                     INNER JOIN locations dl ON dl.id = b.destination_location_id
                     WHERE b.user_id = ?
                     ORDER BY b.booking_date DESC, s.departure_time DESC'
                );
                $stmt->bind_param('i', $userId);
            }
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Booking::getByUser failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get bookings for a specific date.
     *
     * @param string $date Date in Y-m-d format
     * @param string|null $status Optional status filter
     * @return array List of bookings
     */
    public static function getByDate(string $date, ?string $status = null): array
    {
        try {
            $db = getDB();
            if ($status !== null && $status !== '') {
                $stmt = $db->prepare(
                    'SELECT b.*, u.full_name AS user_name, r.name AS route_name, s.departure_time
                     FROM bookings b
                     INNER JOIN users u ON u.id = b.user_id
                     INNER JOIN schedules s ON s.id = b.schedule_id
                     INNER JOIN routes r ON r.id = s.route_id
                     WHERE b.booking_date = ? AND b.status = ?
                     ORDER BY s.departure_time ASC'
                );
                $stmt->bind_param('ss', $date, $status);
            } else {
                $stmt = $db->prepare(
                    'SELECT b.*, u.full_name AS user_name, r.name AS route_name, s.departure_time
                     FROM bookings b
                     INNER JOIN users u ON u.id = b.user_id
                     INNER JOIN schedules s ON s.id = b.schedule_id
                     INNER JOIN routes r ON r.id = s.route_id
                     WHERE b.booking_date = ?
                     ORDER BY s.departure_time ASC'
                );
                $stmt->bind_param('s', $date);
            }
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Booking::getByDate failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate printable ticket data for a booking.
     *
     * @param int $id Booking ID
     * @return array|null Ticket payload or null
     */
    public static function generateTicketData(int $id): ?array
    {
        $booking = self::findById($id);
        if (!$booking) {
            return null;
        }

        return [
            'booking_id' => (int) $booking['id'],
            'booking_number' => $booking['booking_number'],
            'passenger_name' => $booking['user_name'] ?? '',
            'passenger_email' => $booking['user_email'] ?? '',
            'passenger_phone' => $booking['user_phone'] ?? '',
            'route_name' => $booking['route_name'] ?? '',
            'route_code' => $booking['route_code'] ?? '',
            'pickup' => $booking['pickup_name'] ?? '',
            'destination' => $booking['destination_name'] ?? '',
            'booking_date' => $booking['booking_date'],
            'departure_time' => $booking['departure_time'] ?? '',
            'arrival_time' => $booking['arrival_time'] ?? '',
            'seat_number' => $booking['seat_number'],
            'is_priority' => (int) ($booking['is_priority'] ?? 0) === 1,
            'status' => $booking['status'],
            'vehicle' => $booking['plate_number'] ?? 'TBA',
            'driver' => $booking['driver_name'] ?? 'TBA',
            'issued_at' => date('Y-m-d H:i:s'),
            'institution' => APP_INSTITUTION,
            'system' => APP_FULL_NAME,
        ];
    }

    /**
     * Delete a booking permanently.
     *
     * @param int $id Booking ID
     * @return bool Success
     */
    public static function delete(int $id): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('DELETE FROM bookings WHERE id = ?');
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Booking::delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all bookings with optional status filter and pagination.
     *
     * @param string|null $status Optional status
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array{data:array,pagination:array}
     */
    public static function all(?string $status = null, int $page = 1, int $perPage = ITEMS_PER_PAGE): array
    {
        $empty = ['data' => [], 'pagination' => paginate(0, $page, $perPage)];

        try {
            $db = getDB();
            $where = '1=1';
            $types = '';
            $params = [];

            if ($status !== null && $status !== '') {
                $where .= ' AND b.status = ?';
                $types .= 's';
                $params[] = $status;
            }

            $countStmt = $db->prepare("SELECT COUNT(*) AS total FROM bookings b WHERE {$where}");
            if ($types !== '') {
                $countStmt->bind_param($types, ...$params);
            }
            $countStmt->execute();
            $total = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
            $countStmt->close();

            $pagination = paginate($total, $page, $perPage);
            $offset = $pagination['offset'];

            $sql = "SELECT b.*, u.full_name AS user_name, r.name AS route_name, s.departure_time
                    FROM bookings b
                    INNER JOIN users u ON u.id = b.user_id
                    INNER JOIN schedules s ON s.id = b.schedule_id
                    INNER JOIN routes r ON r.id = s.route_id
                    WHERE {$where}
                    ORDER BY b.created_at DESC
                    LIMIT ? OFFSET ?";
            $stmt = $db->prepare($sql);
            $limitTypes = $types . 'ii';
            $limitParams = array_merge($params, [$perPage, $offset]);
            $stmt->bind_param($limitTypes, ...$limitParams);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return ['data' => $rows, 'pagination' => $pagination];
        } catch (Throwable $e) {
            error_log('Booking::all failed: ' . $e->getMessage());
            return $empty;
        }
    }
}
