<?php
/**
 * Driver Dashboard
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
attemptRememberLogin();
requireRole('driver');

$userId = (int) currentUserId();
$driver = Driver::findByUserId($userId);

if (!$driver) {
    flash('error', 'Driver profile not found.');
    redirect(url('views/auth/login.php'));
}

$driverId = (int) $driver['id'];
$today = date('Y-m-d');
$schedules = DriverController::getAssignedSchedules($driverId, $today);
$activeTrip = Tracking::getCurrentTrip($driverId);
$notifications = Notification::getUnread($userId, 5);

$statusCounts = [
    'scheduled' => 0,
    'boarding' => 0,
    'in_progress' => 0,
    'completed' => 0,
];
foreach ($schedules as $s) {
    $st = $s['trip_status'] ?? null;
    if ($st && isset($statusCounts[$st])) {
        $statusCounts[$st]++;
    } else {
        $statusCounts['scheduled']++;
    }
}

// Also count completed trips today
try {
    $db = getDB();
    $completed = TRIP_COMPLETED;
    $stmt = $db->prepare(
        'SELECT COUNT(*) AS total FROM trip_tracking
         WHERE driver_id = ? AND trip_date = ? AND status = ?'
    );
    $stmt->bind_param('iss', $driverId, $today, $completed);
    $stmt->execute();
    $statusCounts['completed'] = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();
} catch (Throwable $e) {
    // ignore
}

$pageTitle = 'Driver Dashboard';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1">Hello, <?= e(currentUserName()) ?></h1>
        <p class="text-muted mb-0">
            License <?= e($driver['license_number'] ?? '—') ?>
            &middot; Status <?= statusBadge($driver['status'] ?? 'available') ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(url('views/driver/trips.php')) ?>" class="btn btn-outline-primary">
            <i class="fa-solid fa-bus me-2"></i>My Trips
        </a>
        <?php if ($activeTrip): ?>
            <a href="<?= e(url('views/driver/trip-action.php?id=' . (int) $activeTrip['id'])) ?>" class="btn btn-primary">
                <i class="fa-solid fa-satellite-dish me-2"></i>Active Trip
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php
    $stats = [
        ["Today's Assignments", count($schedules), 'fa-list-check'],
        ['Boarding / Active', $statusCounts['boarding'] + $statusCounts['in_progress'], 'fa-person-walking-luggage'],
        ['Completed Today', $statusCounts['completed'], 'fa-circle-check'],
        ['Unread Alerts', count($notifications), 'fa-bell'],
    ];
    foreach ($stats as $s):
    ?>
    <div class="col-6 col-md-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="stat-value"><?= (int) $s[1] ?></div>
                    <div class="stat-label"><?= e($s[0]) ?></div>
                </div>
                <div class="stat-icon"><i class="fa-solid <?= e($s[2]) ?>"></i></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($activeTrip): ?>
    <div class="card scts-card mb-4" style="border-left: 4px solid var(--scts-primary);">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <span class="badge bg-primary me-2"><i class="fa-solid fa-satellite-dish me-1"></i>Active trip</span>
                <strong><?= e($activeTrip['route_name'] ?? 'Route') ?></strong>
                &middot; <?= statusBadge($activeTrip['status']) ?>
                &middot; Location: <?= e($activeTrip['current_location_name'] ?? 'Updating…') ?>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= e(url('views/driver/passengers.php?tracking_id=' . (int) $activeTrip['id'])) ?>" class="btn btn-sm btn-outline-primary">Passengers</a>
                <a href="<?= e(url('views/driver/navigation.php?id=' . (int) $activeTrip['id'])) ?>" class="btn btn-sm btn-primary">Navigate</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card scts-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Assigned Trips Today</span>
                <span class="small text-muted"><?= e(formatDate($today)) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-scts align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Route</th>
                                <th>Time</th>
                                <th>Vehicle</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($schedules)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No trips assigned for today.</td></tr>
                            <?php else: ?>
                                <?php foreach ($schedules as $s): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($s['route_name']) ?></strong>
                                            <div class="small text-muted"><?= e($s['start_name']) ?> &rarr; <?= e($s['end_name']) ?></div>
                                        </td>
                                        <td>
                                            <?= e(substr((string) $s['departure_time'], 0, 5)) ?>
                                            &ndash; <?= e(substr((string) $s['arrival_time'], 0, 5)) ?>
                                        </td>
                                        <td><?= e($s['plate_number'] ?? '—') ?></td>
                                        <td><?= statusBadge($s['trip_status'] ?? 'scheduled') ?></td>
                                        <td class="text-end">
                                            <?php if (!empty($s['tracking_id'])): ?>
                                                <a href="<?= e(url('views/driver/trip-action.php?id=' . (int) $s['tracking_id'])) ?>" class="btn btn-sm btn-primary">Manage</a>
                                            <?php else: ?>
                                                <a href="<?= e(url('views/driver/trip-action.php?schedule_id=' . (int) $s['id'])) ?>" class="btn btn-sm btn-outline-primary">Start</a>
                                            <?php endif; ?>
                                        </td>
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Notifications</span>
                <a href="<?= e(url('views/driver/notifications.php')) ?>" class="btn btn-sm btn-outline-primary">All</a>
            </div>
            <div class="card-body">
                <?php if (empty($notifications)): ?>
                    <p class="text-muted mb-0">No new notifications.</p>
                <?php else: ?>
                    <?php foreach ($notifications as $i => $n): ?>
                        <div class="py-2<?= $i < count($notifications) - 1 ? ' border-bottom' : '' ?>">
                            <div class="fw-semibold"><?= e($n['title']) ?></div>
                            <div class="small text-muted"><?= e($n['message']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
