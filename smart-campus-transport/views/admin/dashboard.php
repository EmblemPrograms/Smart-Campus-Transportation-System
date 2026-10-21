<?php
/**
 * Admin Dashboard
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
requireRole('admin');

$pageTitle = 'Admin Dashboard';
$pageHeading = 'Dashboard';

$summary = Report::dashboardSummary();
$today = date('Y-m-d');

$completedToday = 0;
try {
    $db = getDB();
    $st = TRIP_COMPLETED;
    $stmt = $db->prepare('SELECT COUNT(*) AS c FROM trip_tracking WHERE trip_date = ? AND status = ?');
    $stmt->bind_param('ss', $today, $st);
    $stmt->execute();
    $completedToday = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $stmt->close();
} catch (Throwable $e) {
    error_log($e->getMessage());
}

$todaysTrips = (int) ($summary['active_trips'] ?? 0) + $completedToday;
$recentBookings = Booking::all(null, 1, 8);
$activity = ActivityLog::recent(10);

require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/layouts/sidebar.php';
require dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Total Students', $summary['students'] ?? 0, 'fa-user-graduate'],
        ['Staff', $summary['staff'] ?? 0, 'fa-briefcase'],
        ['Drivers', $summary['drivers'] ?? 0, 'fa-id-card'],
        ['Vehicles', $summary['vehicles'] ?? 0, 'fa-bus'],
        ["Today's Trips", $todaysTrips, 'fa-route'],
        ["Today's Bookings", $summary['bookings_today'] ?? 0, 'fa-ticket'],
        ['Pending Bookings', $summary['pending_bookings'] ?? 0, 'fa-hourglass-half'],
        ['Completed Trips', $completedToday, 'fa-circle-check'],
    ];
    foreach ($cards as $c):
    ?>
    <div class="col-6 col-md-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-value"><?= (int) $c[1] ?></div>
                    <div class="stat-label"><?= e($c[0]) ?></div>
                </div>
                <div class="stat-icon"><i class="fa-solid <?= e($c[2]) ?>"></i></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card scts-card h-100">
            <div class="card-header">Monthly Bookings</div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="chartMonthly"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card scts-card h-100">
            <div class="card-header">Route Usage</div>
            <div class="card-body">
                <div class="chart-container sm">
                    <canvas id="chartRoutes"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card scts-card h-100">
            <div class="card-header">Vehicle Usage</div>
            <div class="card-body">
                <div class="chart-container sm">
                    <canvas id="chartVehicles"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card scts-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recent Bookings</span>
                <a href="<?= e(url('views/admin/bookings.php')) ?>" class="btn btn-sm btn-outline-primary">View all</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-scts mb-0">
                        <thead>
                            <tr>
                                <th>Booking #</th>
                                <th>User</th>
                                <th>Route</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentBookings['data'])): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No bookings yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentBookings['data'] as $b): ?>
                                <tr>
                                    <td><?= e($b['booking_number']) ?></td>
                                    <td><?= e($b['user_name'] ?? '') ?></td>
                                    <td><?= e($b['route_name'] ?? '') ?></td>
                                    <td><?= e(formatDate($b['booking_date'] ?? null)) ?></td>
                                    <td><?= statusBadge($b['status'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card scts-card">
            <div class="card-header">Activity Feed</div>
            <div class="card-body">
                <ul class="activity-feed">
                    <?php if (empty($activity)): ?>
                        <li class="text-muted small">No recent activity.</li>
                    <?php else: ?>
                        <?php foreach ($activity as $a): ?>
                            <li>
                                <span class="activity-dot"></span>
                                <div>
                                    <div class="small fw-semibold"><?= e($a['action'] ?? '') ?> · <?= e($a['module'] ?? '') ?></div>
                                    <div class="small text-muted"><?= e($a['description'] ?? '') ?></div>
                                    <div class="small text-muted"><?= e($a['user_name'] ?? 'System') ?> · <?= e(timeAgo($a['created_at'])) ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = ['js/dashboard-inline.js'];
// Inline chart init via footer script override
$extraJs = '<script>
$(function () {
    SCTS.get(SCTS.ajax.dashboardStats).done(function (res) {
        if (!res || !res.success) return;
        var c = res.charts || {};
        if (c.monthly_bookings) {
            SCTS.initLineChart("chartMonthly", c.monthly_bookings.labels, c.monthly_bookings.data, "Bookings");
        }
        if (c.route_usage) {
            SCTS.initDoughnutChart("chartRoutes", c.route_usage.labels, c.route_usage.data);
        }
        if (c.vehicle_usage) {
            SCTS.initBarChart("chartVehicles", c.vehicle_usage.labels, c.vehicle_usage.data, "Trips");
        }
    });
});
</script>';
require dirname(__DIR__) . '/layouts/footer.php';
?>
