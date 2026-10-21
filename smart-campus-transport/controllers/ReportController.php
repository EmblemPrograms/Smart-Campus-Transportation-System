<?php
/**
 * Report Controller
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Generates report datasets for the admin reports page.
 */

declare(strict_types=1);

class ReportController
{
    /**
     * Generate report data based on type and date range.
     *
     * @param string $type Report type key
     * @param string $startDate Start date Y-m-d
     * @param string $endDate End date Y-m-d
     * @return array{title:string,columns:array,rows:array,summary:array,type:string}
     */
    public static function generate(string $type, string $startDate, string $endDate): array
    {
        $type = $type ?: 'daily_bookings';
        $startDate = $startDate ?: date('Y-m-01');
        $endDate = $endDate ?: date('Y-m-d');

        $result = match ($type) {
            'weekly_bookings' => self::wrap(
                'Weekly Bookings Report',
                ['Period', 'Week Start', 'Week End', 'Total', 'Completed', 'Cancelled'],
                array_map(static function ($r) {
                    return [
                        $r['period'] ?? '',
                        $r['week_start'] ?? '',
                        $r['week_end'] ?? '',
                        $r['total'] ?? 0,
                        $r['completed'] ?? 0,
                        $r['cancelled'] ?? 0,
                    ];
                }, Report::weeklyBookings($startDate, $endDate)),
                $type
            ),
            'monthly_bookings' => self::wrap(
                'Monthly Bookings Report (' . date('Y', strtotime($startDate)) . ')',
                ['Month', 'Period', 'Total', 'Pending', 'Approved', 'Completed', 'Cancelled'],
                array_map(static function ($r) {
                    return [
                        $r['month'] ?? '',
                        $r['period'] ?? '',
                        $r['total'] ?? 0,
                        $r['pending'] ?? 0,
                        $r['approved'] ?? 0,
                        $r['completed'] ?? 0,
                        $r['cancelled'] ?? 0,
                    ];
                }, Report::monthlyBookings((int) date('Y', strtotime($startDate)))),
                $type
            ),
            'vehicle_utilization' => self::wrap(
                'Vehicle Utilization Report',
                ['Plate', 'Make/Model', 'Capacity', 'Status', 'Trips', 'Bookings', 'Completed'],
                array_map(static function ($r) {
                    return [
                        $r['plate_number'] ?? '',
                        trim(($r['make'] ?? '') . ' ' . ($r['model'] ?? '')),
                        $r['capacity'] ?? 0,
                        $r['status'] ?? '',
                        $r['trips_count'] ?? 0,
                        $r['bookings_count'] ?? 0,
                        $r['completed_trips'] ?? 0,
                    ];
                }, Report::vehicleUtilization($startDate, $endDate)),
                $type
            ),
            'driver_performance' => self::wrap(
                'Driver Performance Report',
                ['Driver', 'Email', 'License', 'Status', 'Total Trips', 'Completed', 'Cancelled', 'Avg Minutes'],
                array_map(static function ($r) {
                    return [
                        $r['full_name'] ?? '',
                        $r['email'] ?? '',
                        $r['license_number'] ?? '',
                        $r['status'] ?? '',
                        $r['total_trips'] ?? 0,
                        $r['completed_trips'] ?? 0,
                        $r['cancelled_trips'] ?? 0,
                        $r['avg_trip_minutes'] !== null ? round((float) $r['avg_trip_minutes'], 1) : '—',
                    ];
                }, Report::driverPerformance($startDate, $endDate)),
                $type
            ),
            'popular_routes' => self::wrap(
                'Popular Routes Report',
                ['Route', 'Code', 'Start', 'End', 'Bookings', 'Completed', 'Distance'],
                array_map(static function ($r) {
                    return [
                        $r['name'] ?? '',
                        $r['code'] ?? '',
                        $r['start_name'] ?? '',
                        $r['end_name'] ?? '',
                        $r['bookings_count'] ?? 0,
                        $r['completed_count'] ?? 0,
                        formatDistance((float) ($r['total_distance'] ?? 0)),
                    ];
                }, Report::popularRoutes($startDate, $endDate, 25)),
                $type
            ),
            'cancelled' => self::cancelledReport($startDate, $endDate),
            default => self::wrap(
                'Daily Bookings Report',
                ['Date', 'Total', 'Pending', 'Approved', 'Completed', 'Cancelled'],
                array_map(static function ($r) {
                    return [
                        $r['period'] ?? '',
                        $r['total'] ?? 0,
                        $r['pending'] ?? 0,
                        $r['approved'] ?? 0,
                        $r['completed'] ?? 0,
                        $r['cancelled'] ?? 0,
                    ];
                }, Report::dailyBookings($startDate, $endDate)),
                'daily_bookings'
            ),
        };

        // Persist metadata
        if (isLoggedIn()) {
            Report::save(
                $result['type'],
                $result['title'],
                ['start' => $startDate, 'end' => $endDate],
                (int) currentUserId()
            );
            logActivity(currentUserId(), 'report', 'Generated ' . $result['title'], 'reports');
        }

        return $result;
    }

    /**
     * Build a cancelled trips/bookings combined report.
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Report payload
     */
    private static function cancelledReport(string $startDate, string $endDate): array
    {
        $data = Report::cancelledTrips($startDate, $endDate);
        $rows = [];
        foreach ($data['bookings'] as $b) {
            $rows[] = [
                'Booking',
                $b['booking_number'] ?? '',
                $b['user_name'] ?? '',
                $b['route_name'] ?? '',
                $b['booking_date'] ?? '',
                $b['cancelled_at'] ?? '',
            ];
        }
        foreach ($data['trips'] as $t) {
            $rows[] = [
                'Trip',
                '#' . ($t['id'] ?? ''),
                $t['driver_name'] ?? '',
                $t['route_name'] ?? '',
                $t['trip_date'] ?? '',
                $t['ended_at'] ?? $t['updated_at'] ?? '',
            ];
        }

        return [
            'title' => 'Cancelled Trips & Bookings',
            'columns' => ['Type', 'Reference', 'Person', 'Route', 'Date', 'Cancelled At'],
            'rows' => $rows,
            'summary' => $data['summary'],
            'type' => 'cancelled',
        ];
    }

    /**
     * Wrap raw rows into a standard report structure.
     *
     * @param string $title Report title
     * @param array $columns Column headers
     * @param array $rows Data rows
     * @param string $type Type key
     * @return array
     */
    private static function wrap(string $title, array $columns, array $rows, string $type): array
    {
        return [
            'title' => $title,
            'columns' => $columns,
            'rows' => $rows,
            'summary' => ['total_rows' => count($rows)],
            'type' => $type,
        ];
    }
}
