<?php
/**
 * Booking Controller
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Handles booking creation, cancellation, schedule lookup, and seat queries.
 */

declare(strict_types=1);

class BookingController
{
    /**
     * Create a new booking for the given user with seat allocation and notifications.
     *
     * @param array $data Booking payload from form/AJAX
     * @param int $userId Authenticated user ID
     * @param bool $isStaff Whether the requester is staff (priority allowed)
     * @return array{success:bool,message:string,booking_id?:int,booking_number?:string,ticket_url?:string}
     */
    public static function createBooking(array $data, int $userId, bool $isStaff = false): array
    {
        $scheduleId = (int) ($data['schedule_id'] ?? 0);
        $pickupId = (int) ($data['pickup_location_id'] ?? 0);
        $destId = (int) ($data['destination_location_id'] ?? 0);
        $bookingDate = clean((string) ($data['booking_date'] ?? ''));
        $seatNumber = clean((string) ($data['seat_number'] ?? ''));
        $notes = clean((string) ($data['notes'] ?? ''));
        $isPriority = $isStaff && !empty($data['is_priority']) ? 1 : 0;

        if ($scheduleId <= 0 || $pickupId <= 0 || $destId <= 0 || $bookingDate === '' || $seatNumber === '') {
            return ['success' => false, 'message' => 'Please complete all booking fields.'];
        }

        if ($pickupId === $destId) {
            return ['success' => false, 'message' => 'Pickup and destination must be different.'];
        }

        $dateTs = strtotime($bookingDate);
        if ($dateTs === false || $dateTs < strtotime(date('Y-m-d'))) {
            return ['success' => false, 'message' => 'Booking date must be today or a future date.'];
        }

        $schedule = Schedule::findById($scheduleId);
        if (!$schedule || ($schedule['status'] ?? '') !== 'active') {
            return ['success' => false, 'message' => 'Selected schedule is not available.'];
        }

        $dayName = date('D', $dateTs);
        $days = (string) ($schedule['days_of_week'] ?? '');
        if ($days !== '' && stripos($days, $dayName) === false) {
            return ['success' => false, 'message' => 'This shuttle does not run on the selected day.'];
        }

        if (!Booking::checkSeatAvailability($scheduleId, $bookingDate, $seatNumber)) {
            return ['success' => false, 'message' => 'Selected seat is no longer available. Please choose another.'];
        }

        // Ensure seats remain for this schedule/date
        $remaining = self::countRemainingSeats($scheduleId, $bookingDate, (int) ($schedule['available_seats'] ?? 0));
        if ($remaining <= 0) {
            return ['success' => false, 'message' => 'No seats remaining on this shuttle for the selected date.'];
        }

        $autoApprove = Setting::getBool('auto_approve_bookings', true);
        if ($isPriority) {
            $autoApprove = true;
        }
        $status = $autoApprove ? BOOKING_APPROVED : BOOKING_PENDING;

        $bookingNumber = generateBookingNumber();
        $user = User::findById($userId);

        $payload = [
            'booking_number' => $bookingNumber,
            'user_id' => $userId,
            'schedule_id' => $scheduleId,
            'pickup_location_id' => $pickupId,
            'destination_location_id' => $destId,
            'booking_date' => $bookingDate,
            'seat_number' => $seatNumber,
            'is_priority' => $isPriority,
            'status' => $status,
            'notes' => $notes,
            'passenger_name' => $user['full_name'] ?? currentUserName(),
            'passenger_phone' => $user['phone'] ?? '',
            'special_request' => $notes,
        ];

        // Ensure seat rows exist so Booking::create can mark them taken
        self::ensureSeatsForScheduleDate($scheduleId, $bookingDate, (int) ($schedule['available_seats'] ?? 14));

        $bookingId = Booking::create($payload);
        if (!$bookingId) {
            return ['success' => false, 'message' => 'Unable to create booking. Seat may have been taken.'];
        }

        $role = currentUserRole() ?? 'student';
        $ticketUrl = url('views/' . $role . '/ticket.php?id=' . $bookingId);

        createNotification(
            $userId,
            'Booking ' . ucfirst($status),
            'Your booking ' . $bookingNumber . ' has been created and is now ' . $status . '.',
            $status === BOOKING_APPROVED ? 'success' : 'info',
            $ticketUrl
        );

        logActivity(
            $userId,
            'booking_create',
            'Created booking ' . $bookingNumber . ($isPriority ? ' (priority)' : ''),
            'bookings'
        );

        return [
            'success' => true,
            'message' => 'Booking ' . $bookingNumber . ' created successfully.',
            'booking_id' => $bookingId,
            'booking_number' => $bookingNumber,
            'ticket_url' => $ticketUrl,
            'status' => $status,
        ];
    }

    /**
     * Cancel a booking owned by the user (or allow cancel if admin context not required).
     *
     * @param int $bookingId Booking ID
     * @param int $userId User requesting cancellation
     * @return array{success:bool,message:string}
     */
    public static function cancelBooking(int $bookingId, int $userId): array
    {
        $booking = Booking::findById($bookingId);
        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found.'];
        }

        if ((int) $booking['user_id'] !== $userId) {
            return ['success' => false, 'message' => 'You can only cancel your own bookings.'];
        }

        if (!in_array($booking['status'], [BOOKING_PENDING, BOOKING_APPROVED], true)) {
            return ['success' => false, 'message' => 'Only pending or approved bookings can be cancelled.'];
        }

        $bookingDate = (string) $booking['booking_date'];
        $departure = (string) ($booking['departure_time'] ?? '00:00:00');
        $tripTs = strtotime($bookingDate . ' ' . $departure);
        if ($tripTs !== false && $tripTs < time()) {
            return ['success' => false, 'message' => 'Past bookings cannot be cancelled.'];
        }

        if (!Booking::cancel($bookingId, $userId)) {
            return ['success' => false, 'message' => 'Unable to cancel booking. Please try again.'];
        }

        $number = (string) $booking['booking_number'];
        createNotification(
            $userId,
            'Booking Cancelled',
            'Your booking ' . $number . ' has been cancelled.',
            'warning',
            url('views/' . (currentUserRole() ?? 'student') . '/bookings.php')
        );

        logActivity($userId, 'booking_cancel', 'Cancelled booking ' . $number, 'bookings');

        return ['success' => true, 'message' => 'Booking ' . $number . ' cancelled successfully.'];
    }

    /**
     * Get available schedules for a date, optionally filtered by pickup/destination on route.
     *
     * @param string $date Booking date (Y-m-d)
     * @param int|null $pickupId Optional pickup location ID
     * @param int|null $destId Optional destination location ID
     * @return array{success:bool,schedules:array,message:string}
     */
    public static function getAvailableSchedules(string $date, ?int $pickupId = null, ?int $destId = null): array
    {
        $date = clean($date);
        if ($date === '' || strtotime($date) === false) {
            return ['success' => false, 'schedules' => [], 'message' => 'Invalid date provided.'];
        }

        $schedules = Schedule::getAvailableForDate($date);
        $filtered = [];

        foreach ($schedules as $schedule) {
            $remaining = (int) ($schedule['seats_remaining'] ?? 0);
            if ($remaining <= 0) {
                continue;
            }

            // Soft filter: prefer routes whose start/end match, but still include all with seats
            $matchScore = 0;
            if ($pickupId && (int) ($schedule['start_location_id'] ?? 0) === $pickupId) {
                $matchScore++;
            }
            if ($destId && (int) ($schedule['end_location_id'] ?? 0) === $destId) {
                $matchScore++;
            }

            $schedule['match_score'] = $matchScore;
            $schedule['seats'] = self::getAvailableSeats(
                (int) $schedule['id'],
                $date,
                (int) ($schedule['available_seats'] ?? 14)
            );
            $filtered[] = $schedule;
        }

        usort($filtered, static function (array $a, array $b): int {
            $score = ((int) $b['match_score']) <=> ((int) $a['match_score']);
            if ($score !== 0) {
                return $score;
            }
            return strcmp((string) $a['departure_time'], (string) $b['departure_time']);
        });

        return [
            'success' => true,
            'schedules' => $filtered,
            'message' => count($filtered) . ' shuttle(s) available.',
        ];
    }

    /**
     * Get available seat numbers for a schedule on a date, generating defaults if needed.
     *
     * @param int $scheduleId Schedule ID
     * @param string $date Trip date
     * @param int $capacity Fallback capacity when no seat rows exist
     * @return array List of available seat number strings
     */
    public static function getAvailableSeats(int $scheduleId, string $date, int $capacity = 14): array
    {
        self::ensureSeatsForScheduleDate($scheduleId, $date, $capacity);

        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT seat_number FROM seats
                 WHERE schedule_id = ? AND trip_date = ? AND is_available = 1 AND booking_id IS NULL
                 ORDER BY seat_number ASC'
            );
            $stmt->bind_param('is', $scheduleId, $date);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return array_column($rows, 'seat_number');
        } catch (Throwable $e) {
            error_log('BookingController::getAvailableSeats failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Count remaining bookable seats for a schedule/date.
     *
     * @param int $scheduleId Schedule ID
     * @param string $date Trip date
     * @param int $capacity Declared capacity
     * @return int Remaining seats
     */
    public static function countRemainingSeats(int $scheduleId, string $date, int $capacity): int
    {
        try {
            $db = getDB();
            $pending = BOOKING_PENDING;
            $approved = BOOKING_APPROVED;
            $stmt = $db->prepare(
                'SELECT COUNT(*) AS taken FROM bookings
                 WHERE schedule_id = ? AND booking_date = ? AND status IN (?, ?)'
            );
            $stmt->bind_param('isss', $scheduleId, $date, $pending, $approved);
            $stmt->execute();
            $taken = (int) ($stmt->get_result()->fetch_assoc()['taken'] ?? 0);
            $stmt->close();
            return max(0, $capacity - $taken);
        } catch (Throwable $e) {
            error_log('BookingController::countRemainingSeats failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Ensure seat inventory rows exist for a schedule/date (creates A1..An if missing).
     *
     * @param int $scheduleId Schedule ID
     * @param string $date Trip date
     * @param int $capacity Number of seats to ensure
     * @return void
     */
    public static function ensureSeatsForScheduleDate(int $scheduleId, string $date, int $capacity): void
    {
        $capacity = max(1, min(60, $capacity));

        try {
            $db = getDB();
            $check = $db->prepare(
                'SELECT COUNT(*) AS total FROM seats WHERE schedule_id = ? AND trip_date = ?'
            );
            $check->bind_param('is', $scheduleId, $date);
            $check->execute();
            $total = (int) ($check->get_result()->fetch_assoc()['total'] ?? 0);
            $check->close();

            if ($total > 0) {
                return;
            }

            $stmt = $db->prepare(
                'INSERT INTO seats (schedule_id, seat_number, is_available, booking_id, trip_date, created_at)
                 VALUES (?, ?, 1, NULL, ?, NOW())'
            );

            for ($i = 1; $i <= $capacity; $i++) {
                $seat = 'A' . $i;
                $stmt->bind_param('iss', $scheduleId, $seat, $date);
                $stmt->execute();
            }
            $stmt->close();
        } catch (Throwable $e) {
            error_log('BookingController::ensureSeatsForScheduleDate failed: ' . $e->getMessage());
        }
    }

    /**
     * Compute Dijkstra shortest-path preview between pickup and destination.
     *
     * @param int $pickupId Pickup location ID
     * @param int $destId Destination location ID
     * @return array Path details payload
     */
    public static function pathPreview(int $pickupId, int $destId): array
    {
        if ($pickupId <= 0 || $destId <= 0) {
            return [
                'success' => false,
                'path' => [],
                'path_names' => [],
                'distance' => 0,
                'distance_formatted' => '—',
                'hops' => 0,
                'message' => 'Select pickup and destination.',
            ];
        }

        $dijkstra = new Dijkstra(true);
        $result = $dijkstra->shortestPath($pickupId, $destId);
        if (!$result['success']) {
            return array_merge($result, [
                'path_names' => [],
                'distance_formatted' => '—',
                'hops' => 0,
                'segments' => [],
            ]);
        }

        return $dijkstra->getPathDetails();
    }
}
