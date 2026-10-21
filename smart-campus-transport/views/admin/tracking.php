<?php
/**
 * Admin Live Shuttle Tracking
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * SVG campus map with AJAX refresh every 15 seconds.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
requireRole('admin');

$pageTitle = 'Live Tracking';
$pageHeading = 'Live Tracking';
$locations = Location::getActive();
$roads = Road::all('active');
$activeTrips = Tracking::getActiveTrips();

require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/layouts/sidebar.php';
require dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="text-muted small">
        Auto-refresh every <?= (int) TRACKING_REFRESH_INTERVAL ?>s ·
        Last update: <span id="trackingUpdatedAt"><?= e(date('H:i:s')) ?></span>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary" id="refreshTrackingBtn">
        <i class="fa-solid fa-rotate me-1"></i> Refresh Now
    </button>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="campus-map-wrap">
            <svg id="campusMapSvg" class="campus-map-canvas"></svg>
            <div class="map-legend">
                <span class="legend-node">Campus stop</span>
                <span class="legend-shuttle">Active shuttle</span>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card scts-card h-100">
            <div class="card-header">Active Shuttles</div>
            <div class="card-body p-0" id="activeTripsList" style="max-height:480px;overflow:auto;">
                <?php if (empty($activeTrips)): ?>
                    <div class="text-muted small p-3">No active shuttles right now.</div>
                <?php else: ?>
                    <?php foreach ($activeTrips as $t): ?>
                        <div class="border-bottom p-3">
                            <div class="fw-semibold"><?= e($t['plate_number'] ?? 'Vehicle') ?></div>
                            <div class="small text-muted"><?= e($t['route_name'] ?? 'Route') ?> · <?= e($t['driver_name'] ?? 'Driver') ?></div>
                            <div class="small"><?= e($t['current_location_name'] ?? 'En route') ?> · <?= e($t['status'] ?? '') ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
window.CAMPUS_LOCATIONS = <?= json_encode($locations) ?>;
window.CAMPUS_ROADS = <?= json_encode($roads) ?>;
</script>

<?php
$extraJs = ['js/tracking.js'];
require dirname(__DIR__) . '/layouts/footer.php';
?>
