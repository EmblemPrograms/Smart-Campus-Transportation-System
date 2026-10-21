<?php
/**
 * AJAX Dashboard Stats Endpoint
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Returns chart-ready JSON for the admin dashboard.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
startAppSession();
requireRole('admin');

$year = (int) ($_GET['year'] ?? date('Y'));
$start = date('Y-01-01');
$end = date('Y-12-31');

$monthly = Report::monthlyBookings($year);
$monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$monthlyData = array_fill(0, 12, 0);
foreach ($monthly as $row) {
    $m = (int) ($row['month'] ?? 0);
    if ($m >= 1 && $m <= 12) {
        $monthlyData[$m - 1] = (int) ($row['total'] ?? 0);
    }
}

$routes = Report::popularRoutes($start, $end, 8);
$routeLabels = [];
$routeData = [];
foreach ($routes as $r) {
    $routeLabels[] = $r['name'] ?? ('Route #' . $r['id']);
    $routeData[] = (int) ($r['bookings_count'] ?? 0);
}

$vehicles = Report::vehicleUtilization($start, $end);
$vehicleLabels = [];
$vehicleData = [];
foreach (array_slice($vehicles, 0, 10) as $v) {
    $vehicleLabels[] = $v['plate_number'] ?? ('V#' . $v['id']);
    $vehicleData[] = (int) ($v['trips_count'] ?? 0);
}

$summary = Report::dashboardSummary();
$today = date('Y-m-d');
$completedToday = 0;
try {
    $db = getDB();
    $status = TRIP_COMPLETED;
    $stmt = $db->prepare('SELECT COUNT(*) AS c FROM trip_tracking WHERE trip_date = ? AND status = ?');
    $stmt->bind_param('ss', $today, $status);
    $stmt->execute();
    $completedToday = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();
} catch (Throwable $e) {
    error_log('dashboard-stats completedToday: ' . $e->getMessage());
}

jsonResponse([
    'success' => true,
    'summary' => array_merge($summary, [
        'completed_trips_today' => $completedToday,
        'todays_trips' => $summary['active_trips'] + $completedToday,
    ]),
    'charts' => [
        'monthly_bookings' => [
            'labels' => $monthLabels,
            'data' => $monthlyData,
        ],
        'route_usage' => [
            'labels' => $routeLabels,
            'data' => $routeData,
        ],
        'vehicle_usage' => [
            'labels' => $vehicleLabels,
            'data' => $vehicleData,
        ],
    ],
]);
