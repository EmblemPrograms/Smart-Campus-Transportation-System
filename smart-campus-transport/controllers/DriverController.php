<?php
/**
 * Driver Controller
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Handles trip start/end, status updates, and live location updates for drivers.
 */

declare(strict_types=1);

class DriverController
{
    /**
     * Resolve the driver profile for the authenticated user or return an error payload.
     *
     * @param int $userId Authenticated user ID
     * @return array{success:bool,driver?:array,message?:string}
     */
    public static function resolveDriver(int $userId): array
    {
        $driver = Driver::findByUserId($userId);
        if (!$driver) {
            return ['success' => false, 'message' => 'Driver profile not found for this account.'];
        }
        return ['success' => true, 'driver' => $driver];
    }

    /**
     * Start a trip for an assigned schedule (creates trip_tracking row).
     *
     * @param int $scheduleId Schedule ID to start
     * @param int $userId Authenticated driver user ID
     * @param string|null $tripDate Optional trip date (defaults to today)
     * @param string $initialStatus Initial status (boarding or in_progress)
     * @return array{success:bool,message:string,tracking_id?:int}
     */
    public static function startTrip(
        int $scheduleId,
        int $userId,
        ?string $tripDate = null,
        string $initialStatus = TRIP_BOARDING
    ): array {
        $resolved = self::resolveDriver($userId);
        if (!$resolved['success']) {
            return $resolved;
        }

        $driver = $resolved['driver'];
        $driverId = (int) $driver['id'];
        $tripDate = $tripDate ?: date('Y-m-d');

        $allowed = [TRIP_BOARDING, TRIP_IN_PROGRESS];
        if (!in_array($initialStatus, $allowed, true)) {
            $initialStatus = TRIP_BOARDING;
        }

        $active = Tracking::getCurrentTrip($driverId);
        if ($active) {
            return [
                'success' => false,
                'message' => 'You already have an active trip. End it before starting another.',
                'tracking_id' => (int) $active['id'],
            ];
        }

        $schedule = Schedule::findById($scheduleId);
        if (!$schedule) {
            return ['success' => false, 'message' => 'Schedule not found.'];
        }

        if ((int) ($schedule['driver_id'] ?? 0) !== $driverId) {
            return ['success' => false, 'message' => 'This schedule is not assigned to you.'];
        }

        $route = Route::findById((int) $schedule['route_id']);
        $startLocationId = $route ? (int) $route['start_location_id'] : null;
        $lat = null;
        $lng = null;
        if ($startLocationId) {
            $loc = Location::findById($startLocationId);
            if ($loc) {
                $lat = (float) $loc['latitude'];
                $lng = (float) $loc['longitude'];
            }
        }

        $trackingId = Tracking::startTrip([
            'schedule_id' => $scheduleId,
            'vehicle_id' => (int) $schedule['vehicle_id'],
            'driver_id' => $driverId,
            'trip_date' => $tripDate,
            'current_location_id' => $startLocationId,
            'latitude' => $lat,
            'longitude' => $lng,
            'status' => $initialStatus,
            'notes' => 'Trip started by driver',
        ]);

        if (!$trackingId) {
            return ['success' => false, 'message' => 'Unable to start trip. Please try again.'];
        }

        logActivity(
            $userId,
            'trip_start',
            'Started trip for schedule #' . $scheduleId . ' (tracking #' . $trackingId . ')',
            'trips'
        );

        createNotification(
            $userId,
            'Trip Started',
            'Your trip on ' . ($schedule['route_name'] ?? 'assigned route') . ' has started.',
            'success',
            url('views/driver/trip-action.php?id=' . $trackingId)
        );

        return [
            'success' => true,
            'message' => 'Trip started successfully.',
            'tracking_id' => $trackingId,
        ];
    }

    /**
     * End an active trip owned by the driver.
     *
     * @param int $trackingId Trip tracking ID
     * @param int $userId Authenticated driver user ID
     * @param string|null $notes Optional end notes
     * @return array{success:bool,message:string}
     */
    public static function endTrip(int $trackingId, int $userId, ?string $notes = null): array
    {
        $resolved = self::resolveDriver($userId);
        if (!$resolved['success']) {
            return $resolved;
        }

        $driverId = (int) $resolved['driver']['id'];
        $trip = Tracking::findById($trackingId);

        if (!$trip) {
            return ['success' => false, 'message' => 'Trip not found.'];
        }

        if ((int) $trip['driver_id'] !== $driverId) {
            return ['success' => false, 'message' => 'You can only end your own trips.'];
        }

        if (in_array($trip['status'], [TRIP_COMPLETED, TRIP_CANCELLED], true)) {
            return ['success' => false, 'message' => 'This trip is already finished.'];
        }

        if (!Tracking::endTrip($trackingId, $notes)) {
            return ['success' => false, 'message' => 'Unable to end trip. Please try again.'];
        }

        // Mark approved bookings on this schedule/date as completed
        self::completeTripBookings((int) $trip['schedule_id'], (string) $trip['trip_date']);

        logActivity(
            $userId,
            'trip_end',
            'Ended trip tracking #' . $trackingId,
            'trips'
        );

        createNotification(
            $userId,
            'Trip Completed',
            'You have successfully completed your trip.',
            'success',
            url('views/driver/trips.php')
        );

        return ['success' => true, 'message' => 'Trip ended successfully.'];
    }

    /**
     * Update the status of an active trip (boarding / in_progress / completed).
     *
     * @param int $trackingId Trip tracking ID
     * @param string $status New trip status
     * @param int $userId Authenticated driver user ID
     * @return array{success:bool,message:string}
     */
    public static function updateStatus(int $trackingId, string $status, int $userId): array
    {
        $resolved = self::resolveDriver($userId);
        if (!$resolved['success']) {
            return $resolved;
        }

        $driverId = (int) $resolved['driver']['id'];
        $trip = Tracking::findById($trackingId);

        if (!$trip || (int) $trip['driver_id'] !== $driverId) {
            return ['success' => false, 'message' => 'Trip not found or not assigned to you.'];
        }

        $allowed = [TRIP_BOARDING, TRIP_IN_PROGRESS, TRIP_COMPLETED, TRIP_CANCELLED];
        if (!in_array($status, $allowed, true)) {
            return ['success' => false, 'message' => 'Invalid trip status.'];
        }

        if ($status === TRIP_COMPLETED) {
            return self::endTrip($trackingId, $userId, 'Completed via status update');
        }

        if (!Tracking::updateStatus($trackingId, $status)) {
            return ['success' => false, 'message' => 'Unable to update trip status.'];
        }

        logActivity(
            $userId,
            'trip_status',
            'Updated trip #' . $trackingId . ' status to ' . $status,
            'trips'
        );

        return ['success' => true, 'message' => 'Trip status updated to ' . str_replace('_', ' ', $status) . '.'];
    }

    /**
     * Update live GPS / campus location for the driver's active trip.
     *
     * @param int $trackingId Trip tracking ID
     * @param int $userId Authenticated driver user ID
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param int|null $locationId Optional campus location ID
     * @return array{success:bool,message:string,location_name?:string}
     */
    public static function updateLocation(
        int $trackingId,
        int $userId,
        float $latitude,
        float $longitude,
        ?int $locationId = null
    ): array {
        $resolved = self::resolveDriver($userId);
        if (!$resolved['success']) {
            return $resolved;
        }

        $driverId = (int) $resolved['driver']['id'];
        $trip = Tracking::findById($trackingId);

        if (!$trip || (int) $trip['driver_id'] !== $driverId) {
            return ['success' => false, 'message' => 'Trip not found or not assigned to you.'];
        }

        if (!in_array($trip['status'], [TRIP_BOARDING, TRIP_IN_PROGRESS], true)) {
            return ['success' => false, 'message' => 'Location can only be updated on an active trip.'];
        }

        // If only location ID provided, fill lat/lng from location
        $locationName = null;
        if ($locationId && $locationId > 0) {
            $loc = Location::findById($locationId);
            if ($loc) {
                $latitude = (float) $loc['latitude'];
                $longitude = (float) $loc['longitude'];
                $locationName = (string) $loc['name'];
            }
        }

        if (!Tracking::updateLocation($trackingId, $latitude, $longitude, $locationId)) {
            return ['success' => false, 'message' => 'Unable to update location.'];
        }

        return [
            'success' => true,
            'message' => 'Location updated' . ($locationName ? ' to ' . $locationName : '') . '.',
            'location_name' => $locationName,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    /**
     * Get schedules assigned to the driver for a given date (day-of-week match).
     *
     * @param int $driverId Driver profile ID
     * @param string|null $date Date Y-m-d (defaults today)
     * @return array List of schedules
     */
    public static function getAssignedSchedules(int $driverId, ?string $date = null): array
    {
        $date = $date ?: date('Y-m-d');
        $dayName = date('D', strtotime($date));

        try {
            $db = getDB();
            $like = '%' . $dayName . '%';
            $status = 'active';
            $stmt = $db->prepare(
                'SELECT s.*,
                        r.name AS route_name, r.code AS route_code, r.path_json,
                        r.start_location_id, r.end_location_id,
                        sl.name AS start_name, el.name AS end_name,
                        v.plate_number, v.capacity,
                        t.id AS tracking_id, t.status AS trip_status
                 FROM schedules s
                 INNER JOIN routes r ON r.id = s.route_id
                 INNER JOIN locations sl ON sl.id = r.start_location_id
                 INNER JOIN locations el ON el.id = r.end_location_id
                 LEFT JOIN vehicles v ON v.id = s.vehicle_id
                 LEFT JOIN trip_tracking t ON t.schedule_id = s.id
                      AND t.driver_id = s.driver_id
                      AND t.trip_date = ?
                      AND t.status IN (?, ?, ?)
                 WHERE s.driver_id = ?
                   AND s.status = ?
                   AND s.days_of_week LIKE ?
                 ORDER BY s.departure_time ASC'
            );
            $boarding = TRIP_BOARDING;
            $inProgress = TRIP_IN_PROGRESS;
            $scheduled = TRIP_SCHEDULED;
            $stmt->bind_param(
                'sssisss',
                $date,
                $boarding,
                $inProgress,
                $scheduled,
                $driverId,
                $status,
                $like
            );
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('DriverController::getAssignedSchedules failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get approved/pending passengers for a schedule on a date.
     *
     * @param int $scheduleId Schedule ID
     * @param string $date Trip date
     * @return array List of passenger bookings
     */
    public static function getPassengers(int $scheduleId, string $date): array
    {
        try {
            $db = getDB();
            $pending = BOOKING_PENDING;
            $approved = BOOKING_APPROVED;
            $stmt = $db->prepare(
                'SELECT b.*, u.full_name AS passenger_name, u.phone AS passenger_phone,
                        pl.name AS pickup_name, dl.name AS destination_name
                 FROM bookings b
                 INNER JOIN users u ON u.id = b.user_id
                 INNER JOIN locations pl ON pl.id = b.pickup_location_id
                 INNER JOIN locations dl ON dl.id = b.destination_location_id
                 WHERE b.schedule_id = ?
                   AND b.booking_date = ?
                   AND b.status IN (?, ?)
                 ORDER BY b.is_priority DESC, b.seat_number ASC'
            );
            $stmt->bind_param('isss', $scheduleId, $date, $pending, $approved);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('DriverController::getPassengers failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Mark approved bookings for a completed trip as completed.
     *
     * @param int $scheduleId Schedule ID
     * @param string $date Trip date
     * @return void
     */
    private static function completeTripBookings(int $scheduleId, string $date): void
    {
        try {
            $db = getDB();
            $completed = BOOKING_COMPLETED;
            $approved = BOOKING_APPROVED;
            $stmt = $db->prepare(
                'UPDATE bookings SET status = ?, updated_at = NOW()
                 WHERE schedule_id = ? AND booking_date = ? AND status = ?'
            );
            $stmt->bind_param('siss', $completed, $scheduleId, $date, $approved);
            $stmt->execute();
            $stmt->close();
        } catch (Throwable $e) {
            error_log('DriverController::completeTripBookings failed: ' . $e->getMessage());
        }
    }
}
