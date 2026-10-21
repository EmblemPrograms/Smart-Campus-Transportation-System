<?php
/**
 * Role-based Sidebar Navigation
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Renders sidebar menus for admin, student, staff, and driver roles.
 * Highlights the active link based on the current script name.
 */

declare(strict_types=1);

$role = currentUserRole() ?? 'guest';
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$currentPath = $_SERVER['PHP_SELF'] ?? '';

/**
 * Determine whether a sidebar link is active.
 *
 * @param string|array $matches Script name(s) or path fragment(s)
 * @return string CSS class when active
 */
if (!function_exists('sidebarActive')) {
    function sidebarActive($matches): string
    {
        global $currentScript, $currentPath;
        $matches = (array) $matches;
        foreach ($matches as $match) {
            if ($currentScript === $match || str_contains((string) $currentPath, (string) $match)) {
                return 'active';
            }
        }
        return '';
    }
}

$adminMenu = [
    ['label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'url' => url('views/admin/dashboard.php'), 'match' => 'dashboard.php'],
    ['label' => 'Students', 'icon' => 'fa-user-graduate', 'url' => url('views/admin/students.php'), 'match' => 'students.php'],
    ['label' => 'Staff', 'icon' => 'fa-briefcase', 'url' => url('views/admin/staff.php'), 'match' => 'staff.php'],
    ['label' => 'Drivers', 'icon' => 'fa-id-card', 'url' => url('views/admin/drivers.php'), 'match' => 'drivers.php'],
    ['label' => 'Vehicles', 'icon' => 'fa-bus', 'url' => url('views/admin/vehicles.php'), 'match' => 'vehicles.php'],
    ['label' => 'Locations', 'icon' => 'fa-map-marker-alt', 'url' => url('views/admin/locations.php'), 'match' => 'locations.php'],
    ['label' => 'Roads', 'icon' => 'fa-road', 'url' => url('views/admin/roads.php'), 'match' => 'roads.php'],
    ['label' => 'Routes', 'icon' => 'fa-route', 'url' => url('views/admin/routes.php'), 'match' => 'routes.php'],
    ['label' => 'Schedules', 'icon' => 'fa-calendar-days', 'url' => url('views/admin/schedules.php'), 'match' => 'schedules.php'],
    ['label' => 'Bookings', 'icon' => 'fa-ticket', 'url' => url('views/admin/bookings.php'), 'match' => 'bookings.php'],
    ['label' => 'Live Tracking', 'icon' => 'fa-location-crosshairs', 'url' => url('views/admin/tracking.php'), 'match' => 'tracking.php'],
    ['label' => 'Notifications', 'icon' => 'fa-bell', 'url' => url('views/admin/notifications.php'), 'match' => 'notifications.php'],
    ['label' => 'Reports', 'icon' => 'fa-chart-line', 'url' => url('views/admin/reports.php'), 'match' => 'reports.php'],
    ['label' => 'Activity Logs', 'icon' => 'fa-clock-rotate-left', 'url' => url('views/admin/logs.php'), 'match' => 'logs.php'],
    ['label' => 'Settings', 'icon' => 'fa-gear', 'url' => url('views/admin/settings.php'), 'match' => 'settings.php'],
    ['label' => 'Profile', 'icon' => 'fa-user', 'url' => url('views/admin/profile.php'), 'match' => 'profile.php'],
];

$studentMenu = [
    ['label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'url' => url('views/student/dashboard.php'), 'match' => 'dashboard.php'],
    ['label' => 'Book Ride', 'icon' => 'fa-ticket', 'url' => url('views/student/book.php'), 'match' => 'book.php'],
    ['label' => 'My Bookings', 'icon' => 'fa-list', 'url' => url('views/student/bookings.php'), 'match' => 'bookings.php'],
    ['label' => 'Live Tracking', 'icon' => 'fa-location-crosshairs', 'url' => url('views/student/track.php'), 'match' => 'track.php'],
    ['label' => 'Find Route', 'icon' => 'fa-route', 'url' => url('views/student/routes.php'), 'match' => 'routes.php'],
    ['label' => 'Notifications', 'icon' => 'fa-bell', 'url' => url('views/student/notifications.php'), 'match' => 'notifications.php'],
    ['label' => 'Profile', 'icon' => 'fa-user', 'url' => url('views/student/profile.php'), 'match' => 'profile.php'],
];

$staffMenu = [
    ['label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'url' => url('views/staff/dashboard.php'), 'match' => 'dashboard.php'],
    ['label' => 'Priority Booking', 'icon' => 'fa-star', 'url' => url('views/staff/book.php'), 'match' => 'book.php'],
    ['label' => 'My Bookings', 'icon' => 'fa-list', 'url' => url('views/staff/bookings.php'), 'match' => 'bookings.php'],
    ['label' => 'Live Tracking', 'icon' => 'fa-location-crosshairs', 'url' => url('views/staff/track.php'), 'match' => 'track.php'],
    ['label' => 'Find Route', 'icon' => 'fa-route', 'url' => url('views/staff/routes.php'), 'match' => 'routes.php'],
    ['label' => 'Notifications', 'icon' => 'fa-bell', 'url' => url('views/staff/notifications.php'), 'match' => 'notifications.php'],
    ['label' => 'Profile', 'icon' => 'fa-user', 'url' => url('views/staff/profile.php'), 'match' => 'profile.php'],
];

$driverMenu = [
    ['label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'url' => url('views/driver/dashboard.php'), 'match' => 'dashboard.php'],
    ['label' => 'My Trips', 'icon' => 'fa-bus', 'url' => url('views/driver/trips.php'), 'match' => 'trips.php'],
    ['label' => 'Trip Actions', 'icon' => 'fa-play', 'url' => url('views/driver/trip-action.php'), 'match' => 'trip-action.php'],
    ['label' => 'Passengers', 'icon' => 'fa-users', 'url' => url('views/driver/passengers.php'), 'match' => 'passengers.php'],
    ['label' => 'Navigation', 'icon' => 'fa-location-dot', 'url' => url('views/driver/navigation.php'), 'match' => 'navigation.php'],
    ['label' => 'Notifications', 'icon' => 'fa-bell', 'url' => url('views/driver/notifications.php'), 'match' => 'notifications.php'],
    ['label' => 'Profile', 'icon' => 'fa-user', 'url' => url('views/driver/profile.php'), 'match' => 'profile.php'],
];

$menus = [
    'admin' => $adminMenu,
    'student' => $studentMenu,
    'staff' => $staffMenu,
    'driver' => $driverMenu,
];

$menu = $menus[$role] ?? [];
?>
<aside class="scts-sidebar" id="sctsSidebar">
    <div class="sidebar-brand">
        <a href="<?= e(dashboardUrl()) ?>" class="brand-link">
            <span class="brand-mark">SCTS</span>
            <span class="brand-text d-none d-lg-inline"><?= e(APP_NAME) ?></span>
        </a>
        <button type="button" class="btn btn-sm sidebar-close d-lg-none" id="sidebarClose" aria-label="Close sidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <i class="fa-solid fa-user"></i>
        </div>
        <div class="sidebar-user-meta">
            <div class="sidebar-user-name"><?= e(currentUserName()) ?></div>
            <div class="sidebar-user-role"><?= e(ucfirst((string) $role)) ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <?php foreach ($menu as $item): ?>
                <li class="nav-item">
                    <a class="nav-link <?= sidebarActive($item['match']) ?>" href="<?= e($item['url']) ?>">
                        <i class="fa-solid <?= e($item['icon']) ?>"></i>
                        <span><?= e($item['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= e(url('views/auth/logout.php')) ?>" class="nav-link text-danger">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
<div class="sidebar-overlay d-lg-none" id="sidebarOverlay"></div>
