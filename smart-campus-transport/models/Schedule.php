<?php
/**
 * Schedule Model
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * CRUD and assignment helpers for transport schedules.
 */

declare(strict_types=1);

class Schedule
{
    /**
     * Find a schedule by primary key with related details.
     *
     * @param int $id Schedule ID
     * @return array|null Schedule row or null
     */
    public static function findById(int $id): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT s.*,
                        r.name AS route_name, r.code AS route_code,
                        v.plate_number, v.capacity,
                        u.full_name AS driver_name
                 FROM schedules s
                 INNER JOIN routes r ON r.id = s.route_id
                 LEFT JOIN vehicles v ON v.id = s.vehicle_id
                 LEFT JOIN drivers d ON d.id = s.driver_id
                 LEFT JOIN users u ON u.id = d.user_id
                 WHERE s.id = ? LIMIT 1'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Schedule::findById failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new schedule.
     *
     * @param array $data Schedule fields
     * @return int|false New schedule ID or false
     */
    public static function create(array $data)
    {
        try {
            $db = getDB();
            $routeId = (int) ($data['route_id'] ?? 0);
            $vehicleId = isset($data['vehicle_id']) && $data['vehicle_id'] !== '' && $data['vehicle_id'] !== null
                ? (int) $data['vehicle_id'] : null;
            $driverId = isset($data['driver_id']) && $data['driver_id'] !== '' && $data['driver_id'] !== null
                ? (int) $data['driver_id'] : null;
            $departure = (string) ($data['departure_time'] ?? '00:00:00');
            $arrival = (string) ($data['arrival_time'] ?? '00:00:00');
            $days = (string) ($data['days_of_week'] ?? '');
            $seats = (int) ($data['available_seats'] ?? 0);
            $status = (string) ($data['status'] ?? 'active');

            $stmt = $db->prepare(
                'INSERT INTO schedules
                    (route_id, vehicle_id, driver_id, departure_time, arrival_time,
                     days_of_week, available_seats, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->bind_param(
                'iiisssis',
                $routeId,
                $vehicleId,
                $driverId,
                $departure,
                $arrival,
                $days,
                $seats,
                $status
            );
            $ok = $stmt->execute();
            $id = $ok ? (int) $stmt->insert_id : false;
            $stmt->close();
            return $id;
        } catch (Throwable $e) {
            error_log('Schedule::create failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a schedule.
     *
     * @param int $id Schedule ID
     * @param array $data Fields to update
     * @return bool Success
     */
    public static function update(int $id, array $data): bool
    {
        try {
            $db = getDB();
            $routeId = (int) ($data['route_id'] ?? 0);
            $vehicleId = isset($data['vehicle_id']) && $data['vehicle_id'] !== '' && $data['vehicle_id'] !== null
                ? (int) $data['vehicle_id'] : null;
            $driverId = isset($data['driver_id']) && $data['driver_id'] !== '' && $data['driver_id'] !== null
                ? (int) $data['driver_id'] : null;
            $departure = (string) ($data['departure_time'] ?? '00:00:00');
            $arrival = (string) ($data['arrival_time'] ?? '00:00:00');
            $days = (string) ($data['days_of_week'] ?? '');
            $seats = (int) ($data['available_seats'] ?? 0);
            $status = (string) ($data['status'] ?? 'active');

            $stmt = $db->prepare(
                'UPDATE schedules
                 SET route_id = ?, vehicle_id = ?, driver_id = ?, departure_time = ?,
                     arrival_time = ?, days_of_week = ?, available_seats = ?, status = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->bind_param(
                'iiisssisi',
                $routeId,
                $vehicleId,
                $driverId,
                $departure,
                $arrival,
                $days,
                $seats,
                $status,
                $id
            );
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Schedule::update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Assign a vehicle to a schedule.
     *
     * @param int $scheduleId Schedule ID
     * @param int $vehicleId Vehicle ID
     * @return bool Success
     */
    public static function assignVehicle(int $scheduleId, int $vehicleId): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'UPDATE schedules SET vehicle_id = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->bind_param('ii', $vehicleId, $scheduleId);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Schedule::assignVehicle failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Assign a driver to a schedule.
     *
     * @param int $scheduleId Schedule ID
     * @param int $driverId Driver ID
     * @return bool Success
     */
    public static function assignDriver(int $scheduleId, int $driverId): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'UPDATE schedules SET driver_id = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->bind_param('ii', $driverId, $scheduleId);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Schedule::assignDriver failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get schedules available for a given date (matching day-of-week and active status).
     *
     * @param string $date Date in Y-m-d format
     * @return array List of available schedules
     */
    public static function getAvailableForDate(string $date): array
    {
        try {
            $db = getDB();
            $dayName = date('D', strtotime($date)); // Mon, Tue, etc.
            $like = '%' . $dayName . '%';
            $status = 'active';

            $stmt = $db->prepare(
                'SELECT s.*,
                        r.name AS route_name, r.code AS route_code,
                        r.start_location_id, r.end_location_id,
                        sl.name AS start_name, el.name AS end_name,
                        v.plate_number, v.capacity,
                        u.full_name AS driver_name,
                        (s.available_seats - COALESCE((
                            SELECT COUNT(*) FROM bookings b
                            WHERE b.schedule_id = s.id
                              AND b.booking_date = ?
                              AND b.status IN (?, ?)
                        ), 0)) AS seats_remaining
                 FROM schedules s
                 INNER JOIN routes r ON r.id = s.route_id
                 INNER JOIN locations sl ON sl.id = r.start_location_id
                 INNER JOIN locations el ON el.id = r.end_location_id
                 LEFT JOIN vehicles v ON v.id = s.vehicle_id
                 LEFT JOIN drivers d ON d.id = s.driver_id
                 LEFT JOIN users u ON u.id = d.user_id
                 WHERE s.status = ?
                   AND s.days_of_week LIKE ?
                 ORDER BY s.departure_time ASC'
            );
            $approved = BOOKING_APPROVED;
            $pending = BOOKING_PENDING;
            $stmt->bind_param('sssss', $date, $approved, $pending, $status, $like);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Schedule::getAvailableForDate failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete a schedule.
     *
     * @param int $id Schedule ID
     * @return bool Success
     */
    public static function delete(int $id): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare('DELETE FROM schedules WHERE id = ?');
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Schedule::delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all schedules with related details.
     *
     * @param string|null $status Optional status filter
     * @return array List of schedules
     */
    public static function all(?string $status = null): array
    {
        try {
            $db = getDB();
            if ($status !== null && $status !== '') {
                $stmt = $db->prepare(
                    'SELECT s.*, r.name AS route_name, v.plate_number, u.full_name AS driver_name
                     FROM schedules s
                     INNER JOIN routes r ON r.id = s.route_id
                     LEFT JOIN vehicles v ON v.id = s.vehicle_id
                     LEFT JOIN drivers d ON d.id = s.driver_id
                     LEFT JOIN users u ON u.id = d.user_id
                     WHERE s.status = ?
                     ORDER BY s.departure_time ASC'
                );
                $stmt->bind_param('s', $status);
            } else {
                $stmt = $db->prepare(
                    'SELECT s.*, r.name AS route_name, v.plate_number, u.full_name AS driver_name
                     FROM schedules s
                     INNER JOIN routes r ON r.id = s.route_id
                     LEFT JOIN vehicles v ON v.id = s.vehicle_id
                     LEFT JOIN drivers d ON d.id = s.driver_id
                     LEFT JOIN users u ON u.id = d.user_id
                     ORDER BY s.departure_time ASC'
                );
            }
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Schedule::all failed: ' . $e->getMessage());
            return [];
        }
    }
}
