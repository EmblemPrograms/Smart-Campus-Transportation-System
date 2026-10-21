<?php
/**
 * Staff Dashboard
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
attemptRememberLogin();
requireRole('staff');

$userId = (int) currentUserId();
$allBookings = Booking::getByUser($userId);
$upcoming = array_values(array_filter($allBookings, static function (array $b): bool {
    if (!in_array($b['status'], [BOOKING_PENDING, BOOKING_APPROVED], true)) {
        return false;
    }
    return strtotime((string) $b['booking_date']) >= strtotime(date('Y-m-d'));
}));
$upcoming = array_slice($upcoming, 0, 5);
$priorityCount = count(array_filter($allBookings, static fn(array $b): bool => (int) ($b['is_priority'] ?? 0) === 1));
$notifications = Notification::getUnread($userId, 5);
$routes = array_slice(Route::all('active'), 0, 4);
$staff = Staff::findByUserId($userId);

$pageTitle = 'Staff Dashboard';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1">Welcome, <?= e(currentUserName()) ?></h1>
        <p class="text-muted mb-0">
            <?= e($staff['staff_id'] ?? 'Staff') ?>
            <?php if (!empty($staff['department'])): ?> &middot; <?= e($staff['department']) ?><?php endif; ?>
            <?php if (!empty($staff['position'])): ?> &middot; <?= e($staff['position']) ?><?php endif; ?>
            <span class="badge bg-warning text-dark ms-1"><i class="fa-solid fa-star me-1"></i>Priority enabled</span>
        </p>
    </div>
    <a href="<?= e(url('views/staff/book.php')) ?>" class="btn btn-primary">
        <i class="fa-solid fa-star me-2"></i>Priority Book
    </a>
</div>

<div class="row g-3 mb-4">
    <?php
    $stats = [
        ['Upcoming', count($upcoming), 'fa-calendar-check'],
        ['Priority Trips', $priorityCount, 'fa-star'],
        ['Total Bookings', count($allBookings), 'fa-ticket'],
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

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card scts-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Upcoming Bookings</span>
                <a href="<?= e(url('views/staff/bookings.php')) ?>" class="btn btn-sm btn-outline-primary">View all</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($upcoming)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fa-solid fa-calendar-xmark fs-3 d-block mb-2 opacity-50"></i>
                        <p class="mb-3">No upcoming rides.</p>
                        <a href="<?= e(url('views/staff/book.php')) ?>" class="btn btn-sm btn-primary">Book now</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-scts align-middle mb-0">
                            <thead>
                                <tr><th>Booking</th><th>Route</th><th>Date</th><th>Status</th><th></th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming as $b): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($b['booking_number']) ?></strong>
                                            <?php if (!empty($b['is_priority'])): ?><span class="badge bg-warning text-dark">Priority</span><?php endif; ?>
                                            <div class="small text-muted">Seat <?= e($b['seat_number']) ?></div>
                                        </td>
                                        <td>
                                            <?= e($b['route_name']) ?>
                                            <div class="small text-muted"><?= e($b['pickup_name']) ?> &rarr; <?= e($b['destination_name']) ?></div>
                                        </td>
                                        <td>
                                            <?= e(formatDate($b['booking_date'])) ?>
                                            <div class="small text-muted"><?= e(substr((string) $b['departure_time'], 0, 5)) ?></div>
                                        </td>
                                        <td><?= statusBadge($b['status']) ?></td>
                                        <td><a href="<?= e(url('views/staff/ticket.php?id=' . (int) $b['id'])) ?>" class="btn btn-sm btn-outline-primary">Ticket</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card scts-card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Notifications</span>
                <a href="<?= e(url('views/staff/notifications.php')) ?>" class="btn btn-sm btn-outline-primary">All</a>
            </div>
            <div class="card-body">
                <?php if (empty($notifications)): ?>
                    <p class="text-muted mb-0">No new notifications.</p>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($notifications as $n): ?>
                            <li class="py-2 border-bottom">
                                <div class="fw-semibold"><?= e($n['title']) ?></div>
                                <div class="small text-muted"><?= e($n['message']) ?></div>
                                <div class="small text-muted mt-1"><?= e(timeAgo($n['created_at'])) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card scts-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recommended Routes</span>
                <a href="<?= e(url('views/staff/routes.php')) ?>" class="btn btn-sm btn-outline-primary">Explore</a>
            </div>
            <div class="card-body">
                <?php if (empty($routes)): ?>
                    <p class="text-muted mb-0">No routes configured.</p>
                <?php else: ?>
                    <?php foreach ($routes as $i => $r): ?>
                        <div class="d-flex justify-content-between align-items-center py-2<?= $i < count($routes) - 1 ? ' border-bottom' : '' ?>">
                            <div>
                                <strong><?= e($r['name']) ?></strong>
                                <div class="small text-muted"><?= e($r['start_name']) ?> &rarr; <?= e($r['end_name']) ?></div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary-emphasis"><?= e(formatDistance($r['total_distance'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
