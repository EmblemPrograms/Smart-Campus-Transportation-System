<?php
/**
 * Report Model
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Analytical report queries for admin dashboards.
 */

declare(strict_types=1);

class Report
{
    /**
     * Get daily booking statistics for a date range.
     *
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Daily aggregates
     */
    public static function dailyBookings(string $startDate, string $endDate): array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT booking_date AS period,
                        COUNT(*) AS total,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS pending,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS approved,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cancelled
                 FROM bookings
                 WHERE booking_date BETWEEN ? AND ?
                 GROUP BY booking_date
                 ORDER BY booking_date ASC'
            );
            $pending = BOOKING_PENDING;
            $approved = BOOKING_APPROVED;
            $completed = BOOKING_COMPLETED;
            $cancelled = BOOKING_CANCELLED;
            $stmt->bind_param(
                'ssssss',
                $pending,
                $approved,
                $completed,
                $cancelled,
                $startDate,
                $endDate
            );
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Report::dailyBookings failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get weekly booking statistics (ISO week groups).
     *
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Weekly aggregates
     */
    public static function weeklyBookings(string $startDate, string $endDate): array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT YEARWEEK(booking_date, 1) AS period,
                        MIN(booking_date) AS week_start,
                        MAX(booking_date) AS week_end,
                        COUNT(*) AS total,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cancelled
                 FROM bookings
                 WHERE booking_date BETWEEN ? AND ?
                 GROUP BY YEARWEEK(booking_date, 1)
                 ORDER BY period ASC'
            );
            $completed = BOOKING_COMPLETED;
            $cancelled = BOOKING_CANCELLED;
            $stmt->bind_param('ssss', $completed, $cancelled, $startDate, $endDate);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Report::weeklyBookings failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get monthly booking statistics.
     *
     * @param int $year Year (e.g. 2026)
     * @return array Monthly aggregates
     */
    public static function monthlyBookings(int $year): array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                "SELECT MONTH(booking_date) AS month,
                        DATE_FORMAT(booking_date, '%Y-%m') AS period,
                        COUNT(*) AS total,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS pending,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS approved,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cancelled
                 FROM bookings
                 WHERE YEAR(booking_date) = ?
                 GROUP BY MONTH(booking_date), DATE_FORMAT(booking_date, '%Y-%m')
                 ORDER BY month ASC"
            );
            $pending = BOOKING_PENDING;
            $approved = BOOKING_APPROVED;
            $completed = BOOKING_COMPLETED;
            $cancelled = BOOKING_CANCELLED;
            $stmt->bind_param('ssssi', $pending, $approved, $completed, $cancelled, $year);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Report::monthlyBookings failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get vehicle utilization based on completed/in-progress trips and bookings.
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Vehicle utilization rows
     */
    public static function vehicleUtilization(string $startDate, string $endDate): array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT v.id, v.plate_number, v.model, v.make, v.capacity, v.status,
                        COUNT(DISTINCT t.id) AS trips_count,
                        COUNT(DISTINCT b.id) AS bookings_count,
                        SUM(CASE WHEN t.status = ? THEN 1 ELSE 0 END) AS completed_trips
                 FROM vehicles v
                 LEFT JOIN trip_tracking t
                        ON t.vehicle_id = v.id AND t.trip_date BETWEEN ? AND ?
                 LEFT JOIN schedules s ON s.vehicle_id = v.id
                 LEFT JOIN bookings b
                        ON b.schedule_id = s.id AND b.booking_date BETWEEN ? AND ?
                 GROUP BY v.id, v.plate_number, v.model, v.make, v.capacity, v.status
                 ORDER BY trips_count DESC, bookings_count DESC'
            );
            $completed = TRIP_COMPLETED;
            $stmt->bind_param('sssss', $completed, $startDate, $endDate, $startDate, $endDate);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Report::vehicleUtilization failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get driver performance metrics.
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Driver performance rows
     */
    public static function driverPerformance(string $startDate, string $endDate): array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT d.id, u.full_name, u.email, d.license_number, d.status,
                        COUNT(t.id) AS total_trips,
                        SUM(CASE WHEN t.status = ? THEN 1 ELSE 0 END) AS completed_trips,
                        SUM(CASE WHEN t.status = ? THEN 1 ELSE 0 END) AS cancelled_trips,
                        AVG(CASE
                            WHEN t.started_at IS NOT NULL AND t.ended_at IS NOT NULL
                            THEN TIMESTAMPDIFF(MINUTE, t.started_at, t.ended_at)
                            ELSE NULL
                        END) AS avg_trip_minutes
                 FROM drivers d
                 INNER JOIN users u ON u.id = d.user_id
                 LEFT JOIN trip_tracking t
                        ON t.driver_id = d.id AND t.trip_date BETWEEN ? AND ?
                 GROUP BY d.id, u.full_name, u.email, d.license_number, d.status
                 ORDER BY completed_trips DESC, total_trips DESC'
            );
            $completed = TRIP_COMPLETED;
            $cancelled = TRIP_CANCELLED;
            $stmt->bind_param('ssss', $completed, $cancelled, $startDate, $endDate);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Report::driverPerformance failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the most popular routes by booking volume.
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @param int $limit Maximum routes to return
     * @return array Popular route rows
     */
    public static function popularRoutes(string $startDate, string $endDate, int $limit = 10): array
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'SELECT r.id, r.name, r.code, r.total_distance, r.estimated_time,
                        sl.name AS start_name, el.name AS end_name,
                        COUNT(b.id) AS bookings_count,
                        SUM(CASE WHEN b.status = ? THEN 1 ELSE 0 END) AS completed_count
                 FROM routes r
                 INNER JOIN locations sl ON sl.id = r.start_location_id
                 INNER JOIN locations el ON el.id = r.end_location_id
                 INNER JOIN schedules s ON s.route_id = r.id
                 LEFT JOIN bookings b
                        ON b.schedule_id = s.id AND b.booking_date BETWEEN ? AND ?
                 GROUP BY r.id, r.name, r.code, r.total_distance, r.estimated_time, sl.name, el.name
                 ORDER BY bookings_count DESC
                 LIMIT ?'
            );
            $completed = BOOKING_COMPLETED;
            $stmt->bind_param('sssi', $completed, $startDate, $endDate, $limit);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('Report::popularRoutes failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get cancelled trips and related cancellation stats.
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array{trips:array,bookings:array,summary:array}
     */
    public static function cancelledTrips(string $startDate, string $endDate): array
    {
        $result = [
            'trips' => [],
            'bookings' => [],
            'summary' => [
                'cancelled_trips' => 0,
                'cancelled_bookings' => 0,
            ],
        ];

        try {
            $db = getDB();
            $tripStatus = TRIP_CANCELLED;
            $bookingStatus = BOOKING_CANCELLED;

            $tripStmt = $db->prepare(
                'SELECT t.*, v.plate_number, u.full_name AS driver_name, r.name AS route_name
                 FROM trip_tracking t
                 LEFT JOIN vehicles v ON v.id = t.vehicle_id
                 LEFT JOIN drivers d ON d.id = t.driver_id
                 LEFT JOIN users u ON u.id = d.user_id
                 LEFT JOIN schedules s ON s.id = t.schedule_id
                 LEFT JOIN routes r ON r.id = s.route_id
                 WHERE t.status = ? AND t.trip_date BETWEEN ? AND ?
                 ORDER BY t.trip_date DESC'
            );
            $tripStmt->bind_param('sss', $tripStatus, $startDate, $endDate);
            $tripStmt->execute();
            $result['trips'] = $tripStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $tripStmt->close();

            $bookStmt = $db->prepare(
                'SELECT b.*, u.full_name AS user_name, r.name AS route_name
                 FROM bookings b
                 INNER JOIN users u ON u.id = b.user_id
                 INNER JOIN schedules s ON s.id = b.schedule_id
                 INNER JOIN routes r ON r.id = s.route_id
                 WHERE b.status = ? AND b.booking_date BETWEEN ? AND ?
                 ORDER BY b.cancelled_at DESC'
            );
            $bookStmt->bind_param('sss', $bookingStatus, $startDate, $endDate);
            $bookStmt->execute();
            $result['bookings'] = $bookStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $bookStmt->close();

            $result['summary'] = [
                'cancelled_trips' => count($result['trips']),
                'cancelled_bookings' => count($result['bookings']),
            ];
        } catch (Throwable $e) {
            error_log('Report::cancelledTrips failed: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Get a high-level dashboard summary snapshot.
     *
     * @return array Summary counts
     */
    public static function dashboardSummary(): array
    {
        $summary = [
            'users' => 0,
            'students' => 0,
            'staff' => 0,
            'drivers' => 0,
            'vehicles' => 0,
            'routes' => 0,
            'schedules' => 0,
            'bookings_today' => 0,
            'active_trips' => 0,
            'pending_bookings' => 0,
        ];

        try {
            $db = getDB();
            $today = date('Y-m-d');
            $pending = BOOKING_PENDING;
            $boarding = TRIP_BOARDING;
            $inProgress = TRIP_IN_PROGRESS;

            $queries = [
                'users' => 'SELECT COUNT(*) AS c FROM users',
                'students' => 'SELECT COUNT(*) AS c FROM students',
                'staff' => 'SELECT COUNT(*) AS c FROM staff',
                'drivers' => 'SELECT COUNT(*) AS c FROM drivers',
                'vehicles' => 'SELECT COUNT(*) AS c FROM vehicles',
                'routes' => 'SELECT COUNT(*) AS c FROM routes',
                'schedules' => 'SELECT COUNT(*) AS c FROM schedules',
            ];

            foreach ($queries as $key => $sql) {
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $summary[$key] = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
                $stmt->close();
            }

            $stmt = $db->prepare('SELECT COUNT(*) AS c FROM bookings WHERE booking_date = ?');
            $stmt->bind_param('s', $today);
            $stmt->execute();
            $summary['bookings_today'] = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt->close();

            $stmt = $db->prepare('SELECT COUNT(*) AS c FROM bookings WHERE status = ?');
            $stmt->bind_param('s', $pending);
            $stmt->execute();
            $summary['pending_bookings'] = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt->close();

            $stmt = $db->prepare(
                'SELECT COUNT(*) AS c FROM trip_tracking WHERE status IN (?, ?)'
            );
            $stmt->bind_param('ss', $boarding, $inProgress);
            $stmt->execute();
            $summary['active_trips'] = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt->close();
        } catch (Throwable $e) {
            error_log('Report::dashboardSummary failed: ' . $e->getMessage());
        }

        return $summary;
    }

    /**
     * Persist a generated report metadata record.
     *
     * @param string $type Report type key
     * @param string $title Report title
     * @param array $parameters Report parameters
     * @param int $generatedBy User ID
     * @param string|null $filePath Optional file path
     * @return int|false New report ID or false
     */
    public static function save(
        string $type,
        string $title,
        array $parameters,
        int $generatedBy,
        ?string $filePath = null
    ) {
        try {
            $db = getDB();
            $json = json_encode($parameters);
            $stmt = $db->prepare(
                'INSERT INTO reports (report_type, title, parameters_json, generated_by, file_path, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $stmt->bind_param('sssis', $type, $title, $json, $generatedBy, $filePath);
            $ok = $stmt->execute();
            $id = $ok ? (int) $stmt->insert_id : false;
            $stmt->close();
            return $id;
        } catch (Throwable $e) {
            error_log('Report::save failed: ' . $e->getMessage());
            return false;
        }
    }
}
