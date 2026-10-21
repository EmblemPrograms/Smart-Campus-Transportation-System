<?php
/**
 * Tracking Model
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Live trip tracking for vehicles and drivers.
 */

declare(strict_types=1);

class Tracking
{
    /**
     * Find a trip tracking record by ID.
     *
     * @param int $id Tracking ID
     * @return array|null Tracking row or null
     */
    public static function findById(int $id): ?array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT t.*,
                        v.plate_number, u.full_name AS driver_name,
                        l.name AS current_location_name,
                        r.name AS route_name
                 FROM trip_tracking t
                 LEFT JOIN vehicles v ON v.id = t.vehicle_id
                 LEFT JOIN drivers d ON d.id = t.driver_id
                 LEFT JOIN users u ON u.id = d.user_id
                 LEFT JOIN locations l ON l.id = t.current_location_id
                 LEFT JOIN schedules s ON s.id = t.schedule_id
                 LEFT JOIN routes r ON r.id = s.route_id
                 WHERE t.id = ? LIMIT 1'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Tracking::findById failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Start a new trip for a schedule on a given date.
     *
     * @param array $data Trip fields (schedule_id, vehicle_id, driver_id, trip_date, ...)
     * @return int|false New tracking ID or false
     */
    public static function startTrip(array $data)
    {
        try {
            $db = getDB();
            $scheduleId = (int) ($data['schedule_id'] ?? 0);
            $vehicleId = (int) ($data['vehicle_id'] ?? 0);
            $driverId = (int) ($data['driver_id'] ?? 0);
            $tripDate = (string) ($data['trip_date'] ?? date('Y-m-d'));
            $locationId = isset($data['current_location_id']) && $data['current_location_id'] !== ''
                ? (int) $data['current_location_id'] : null;
            $lat = isset($data['latitude']) ? (float) $data['latitude'] : null;
            $lng = isset($data['longitude']) ? (float) $data['longitude'] : null;
            $status = (string) ($data['status'] ?? TRIP_IN_PROGRESS);
            $notes = (string) ($data['notes'] ?? '');

            $stmt = $db->prepare(
                'INSERT INTO trip_tracking
                    (schedule_id, vehicle_id, driver_id, current_location_id, latitude, longitude,
                     trip_date, status, started_at, notes, updated_at, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW(), NOW())'
            );
            $stmt->bind_param(
                'iiiiddsss',
                $scheduleId,
                $vehicleId,
                $driverId,
                $locationId,
                $lat,
                $lng,
                $tripDate,
                $status,
                $notes
            );
            $ok = $stmt->execute();
            $id = $ok ? (int) $stmt->insert_id : false;
            $stmt->close();

            if ($id) {
                Driver::updateDriverStatus($driverId, 'on_trip');
                Vehicle::updateStatus($vehicleId, 'in_use');
            }

            return $id;
        } catch (Throwable $e) {
            error_log('Tracking::startTrip failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update the live GPS / location of an active trip.
     *
     * @param int $trackingId Tracking ID
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param int|null $locationId Optional mapped campus location ID
     * @return bool Success
     */
    public static function updateLocation(
        int $trackingId,
        float $latitude,
        float $longitude,
        ?int $locationId = null
    ): bool {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'UPDATE trip_tracking
                 SET latitude = ?, longitude = ?, current_location_id = ?, updated_at = NOW()
                 WHERE id = ? AND status IN (?, ?)'
            );
            $boarding = TRIP_BOARDING;
            $inProgress = TRIP_IN_PROGRESS;
            $stmt->bind_param('ddiiss', $latitude, $longitude, $locationId, $trackingId, $boarding, $inProgress);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Tracking::updateLocation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * End an active trip.
     *
     * @param int $trackingId Tracking ID
     * @param string|null $notes Optional end notes
     * @return bool Success
     */
    public static function endTrip(int $trackingId, ?string $notes = null): bool
    {
        try {
            $trip = self::findById($trackingId);
            if (!$trip) {
                return false;
            }

            $db = getDB();
            $status = TRIP_COMPLETED;
            $noteVal = $notes ?? ($trip['notes'] ?? '');

            $stmt = $db->prepare(
                'UPDATE trip_tracking
                 SET status = ?, ended_at = NOW(), notes = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->bind_param('ssi', $status, $noteVal, $trackingId);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                if (!empty($trip['driver_id'])) {
                    Driver::updateDriverStatus((int) $trip['driver_id'], 'available');
                }
                if (!empty($trip['vehicle_id'])) {
                    Vehicle::updateStatus((int) $trip['vehicle_id'], 'available');
                }
            }

            return $ok;
        } catch (Throwable $e) {
            error_log('Tracking::endTrip failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the current active trip for a driver.
     *
     * @param int $driverId Driver ID
     * @return array|null Active trip or null
     */
    public static function getCurrentTrip(int $driverId): ?array
    {
        try {
            $db = getDB();
            $boarding = TRIP_BOARDING;
            $inProgress = TRIP_IN_PROGRESS;
            $stmt = $db->prepare(
                'SELECT t.*,
                        v.plate_number, l.name AS current_location_name,
                        r.name AS route_name, s.departure_time
                 FROM trip_tracking t
                 LEFT JOIN vehicles v ON v.id = t.vehicle_id
                 LEFT JOIN locations l ON l.id = t.current_location_id
                 LEFT JOIN schedules s ON s.id = t.schedule_id
                 LEFT JOIN routes r ON r.id = s.route_id
                 WHERE t.driver_id = ? AND t.status IN (?, ?)
                 ORDER BY t.started_at DESC
                 LIMIT 1'
            );
            $stmt->bind_param('iss', $driverId, $boarding, $inProgress);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('Tracking::getCurrentTrip failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all currently active trips (boarding or in progress).
     *
     * @return array List of active trips
     */
    public static function getActiveTrips(): array
    {
        try {
            $db = getDB();
            $boarding = TRIP_BOARDING;
            $inProgress = TRIP_IN_PROGRESS;
            $stmt = $db->prepare(
                'SELECT t.*,
                        v.plate_number, u.full_name AS driver_name,
                        l.name AS current_location_name,
                        r.name AS route_name
                 FROM trip_tracking t
                 LEFT JOIN vehicles v ON v.id = t.vehicle_id
                 LEFT JOIN drivers d ON d.id = t.driver_id
                 LEFT JOIN users u ON u.id = d.user_id
                 LEFT JOIN locations l ON l.id = t.current_location_id
                 LEFT JOIN schedules s ON s.id = t.schedule_id
                 LEFT JOIN routes r ON r.id = s.route_id
                 WHERE t.status IN (?, ?)
                 ORDER BY t.updated_at DESC'
            );
            $stmt->bind_param('ss', $boarding, $inProgress);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Tracking::getActiveTrips failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update trip status (scheduled/boarding/in_progress/completed/cancelled).
     *
     * @param int $trackingId Tracking ID
     * @param string $status New status
     * @return bool Success
     */
    public static function updateStatus(int $trackingId, string $status): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'UPDATE trip_tracking SET status = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->bind_param('si', $status, $trackingId);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('Tracking::updateStatus failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get trips for a specific date.
     *
     * @param string $date Trip date (Y-m-d)
     * @return array List of trips
     */
    public static function getByDate(string $date): array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT t.*, v.plate_number, u.full_name AS driver_name, r.name AS route_name
                 FROM trip_tracking t
                 LEFT JOIN vehicles v ON v.id = t.vehicle_id
                 LEFT JOIN drivers d ON d.id = t.driver_id
                 LEFT JOIN users u ON u.id = d.user_id
                 LEFT JOIN schedules s ON s.id = t.schedule_id
                 LEFT JOIN routes r ON r.id = s.route_id
                 WHERE t.trip_date = ?
                 ORDER BY t.started_at DESC'
            );
            $stmt->bind_param('s', $date);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Tracking::getByDate failed: ' . $e->getMessage());
            return [];
        }
    }
}
