<?php
/**
 * Driver Assigned Trips / Schedules
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
$date = clean($_GET['date'] ?? date('Y-m-d'));
if (strtotime($date) === false) {
    $date = date('Y-m-d');
}

$schedules = DriverController::getAssignedSchedules($driverId, $date);
$activeTrip = Tracking::getCurrentTrip($driverId);

$pageTitle = 'My Trips';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/navbar.php';
?>

<div>
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
      <h1 class="h3 mb-1">Assigned Trips</h1>
      <p class="text-muted mb-0">Schedules assigned to you for the selected date.</p>
    </div>
    <form method="get" class="d-flex gap-2 align-items-center">
      <label class="form-label mb-0 small text-muted" for="date">Date</label>
      <input type="date" class="form-control form-control-sm" name="date" id="date" value="<?= e($date) ?>">
      <button type="submit" class="btn btn-sm btn-primary">Go</button>
    </form>
  </div>

  <div class="card scts-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-scts table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Route</th>
              <th>Schedule</th>
              <th>Vehicle</th>
              <th>Days</th>
              <th>Trip Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($schedules)): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">No assigned schedules for this date.</td></tr>
            <?php else: ?>
              <?php foreach ($schedules as $s): ?>
                <tr>
                  <td>
                    <strong><?= e($s['route_name']) ?></strong>
                    <div class="small text-muted"><?= e($s['code'] ?? $s['route_code'] ?? '') ?></div>
                    <div class="small text-muted"><?= e($s['start_name']) ?> → <?= e($s['end_name']) ?></div>
                  </td>
                  <td>
                    <?= e(substr((string) $s['departure_time'], 0, 5)) ?>
                    – <?= e(substr((string) $s['arrival_time'], 0, 5)) ?>
                  </td>
                  <td><?= e($s['plate_number'] ?? '—') ?></td>
                  <td><span class="small"><?= e($s['days_of_week']) ?></span></td>
                  <td><?= statusBadge($s['trip_status'] ?? 'scheduled') ?></td>
                  <td class="text-end text-nowrap">
                    <?php if (!empty($s['tracking_id'])): ?>
                      <a href="<?= e(url('views/driver/trip-action.php?id=' . (int) $s['tracking_id'])) ?>"
                         class="btn btn-sm btn-primary">Manage Trip</a>
                      <a href="<?= e(url('views/driver/passengers.php?tracking_id=' . (int) $s['tracking_id'])) ?>"
                         class="btn btn-sm btn-outline-secondary">Passengers</a>
                      <a href="<?= e(url('views/driver/navigation.php?id=' . (int) $s['tracking_id'])) ?>"
                         class="btn btn-sm btn-outline-secondary">Navigate</a>
                    <?php else: ?>
                      <?php if ($activeTrip): ?>
                        <span class="small text-muted">Finish active trip first</span>
                      <?php else: ?>
                        <a href="<?= e(url('views/driver/trip-action.php?schedule_id=' . (int) $s['id'] . '&date=' . urlencode($date))) ?>"
                           class="btn btn-sm btn-primary">Start Trip</a>
                      <?php endif; ?>
                      <a href="<?= e(url('views/driver/passengers.php?schedule_id=' . (int) $s['id'] . '&date=' . urlencode($date))) ?>"
                         class="btn btn-sm btn-outline-secondary">Passengers</a>
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

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
