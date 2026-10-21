<?php
/**
 * Driver Trip Actions — Start / End / Update Status
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
$trackingId = (int) ($_GET['id'] ?? $_POST['tracking_id'] ?? 0);
$scheduleId = (int) ($_GET['schedule_id'] ?? $_POST['schedule_id'] ?? 0);
$tripDate = clean($_GET['date'] ?? $_POST['trip_date'] ?? date('Y-m-d'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = clean($_POST['action'] ?? '');

    if ($action === 'start') {
        $sid = (int) ($_POST['schedule_id'] ?? 0);
        $status = clean($_POST['initial_status'] ?? TRIP_BOARDING);
        $result = DriverController::startTrip($sid, $userId, $tripDate, $status);
        flash($result['success'] ? 'success' : 'error', $result['message']);
        if ($result['success'] && !empty($result['tracking_id'])) {
            redirect(url('views/driver/trip-action.php?id=' . (int) $result['tracking_id']));
        }
        redirect(url('views/driver/trips.php'));
    }

    if ($action === 'update_status') {
        $tid = (int) ($_POST['tracking_id'] ?? 0);
        $status = clean($_POST['status'] ?? '');
        $result = DriverController::updateStatus($tid, $status, $userId);
        flash($result['success'] ? 'success' : 'error', $result['message']);
        if ($status === TRIP_COMPLETED) {
            redirect(url('views/driver/trips.php'));
        }
        redirect(url('views/driver/trip-action.php?id=' . $tid));
    }

    if ($action === 'end') {
        $tid = (int) ($_POST['tracking_id'] ?? 0);
        $notes = clean($_POST['notes'] ?? '');
        $result = DriverController::endTrip($tid, $userId, $notes !== '' ? $notes : null);
        flash($result['success'] ? 'success' : 'error', $result['message']);
        redirect(url('views/driver/trips.php'));
    }
}

$trip = $trackingId > 0 ? Tracking::findById($trackingId) : null;
if ($trip && (int) $trip['driver_id'] !== $driverId) {
    flash('error', 'This trip is not assigned to you.');
    redirect(url('views/driver/trips.php'));
}

$schedule = null;
if ($trip) {
    $schedule = Schedule::findById((int) $trip['schedule_id']);
} elseif ($scheduleId > 0) {
    $schedule = Schedule::findById($scheduleId);
    if ($schedule && (int) ($schedule['driver_id'] ?? 0) !== $driverId) {
        flash('error', 'This schedule is not assigned to you.');
        redirect(url('views/driver/trips.php'));
    }
}

if (!$trip && !$schedule) {
    flash('error', 'Select a trip or schedule to manage.');
    redirect(url('views/driver/trips.php'));
}

$passengers = [];
if ($schedule) {
    $dateForPax = $trip ? (string) $trip['trip_date'] : $tripDate;
    $passengers = DriverController::getPassengers((int) $schedule['id'], $dateForPax);
}

$pageTitle = 'Trip Actions';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/navbar.php';
?>

<div>
  <div class="mb-4">
    <h1 class="h3 mb-1">Trip Control</h1>
    <p class="text-muted mb-0">Start, update, or end your assigned campus shuttle trip.</p>
  </div>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card scts-card">
        <div class="card-body">
          <h2 class="h5 mb-3">
            <?= e($schedule['route_name'] ?? 'Assigned Route') ?>
          </h2>
          <dl class="row mb-0">
            <dt class="col-sm-4 text-muted">Schedule</dt>
            <dd class="col-sm-8">
              <?= e(substr((string) ($schedule['departure_time'] ?? ''), 0, 5)) ?>
              – <?= e(substr((string) ($schedule['arrival_time'] ?? ''), 0, 5)) ?>
            </dd>
            <dt class="col-sm-4 text-muted">Vehicle</dt>
            <dd class="col-sm-8"><?= e($schedule['plate_number'] ?? ($trip['plate_number'] ?? '—')) ?></dd>
            <dt class="col-sm-4 text-muted">Trip Date</dt>
            <dd class="col-sm-8"><?= e(formatDate($trip['trip_date'] ?? $tripDate)) ?></dd>
            <?php if ($trip): ?>
              <dt class="col-sm-4 text-muted">Status</dt>
              <dd class="col-sm-8"><?= statusBadge($trip['status']) ?></dd>
              <dt class="col-sm-4 text-muted">Location</dt>
              <dd class="col-sm-8"><?= e($trip['current_location_name'] ?? '—') ?></dd>
              <dt class="col-sm-4 text-muted">Started</dt>
              <dd class="col-sm-8"><?= e(formatDateTime($trip['started_at'] ?? null)) ?></dd>
            <?php endif; ?>
          </dl>

          <hr>

          <?php if (!$trip): ?>
            <form method="post" class="row g-3">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="start">
              <input type="hidden" name="schedule_id" value="<?= (int) $schedule['id'] ?>">
              <input type="hidden" name="trip_date" value="<?= e($tripDate) ?>">
              <div class="col-md-6">
                <label class="form-label" for="initial_status">Start as</label>
                <select class="form-select" name="initial_status" id="initial_status">
                  <option value="<?= e(TRIP_BOARDING) ?>">Boarding</option>
                  <option value="<?= e(TRIP_IN_PROGRESS) ?>">In Progress</option>
                </select>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-lg btn-primary">Start Trip</button>
                <a href="<?= e(url('views/driver/trips.php')) ?>" class="btn btn-outline-secondary btn-lg">Cancel</a>
              </div>
            </form>
          <?php else: ?>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <?php if ($trip['status'] === TRIP_BOARDING): ?>
                <form method="post">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="tracking_id" value="<?= (int) $trip['id'] ?>">
                  <input type="hidden" name="status" value="<?= e(TRIP_IN_PROGRESS) ?>">
                  <button type="submit" class="btn btn-primary">Mark In Progress</button>
                </form>
              <?php endif; ?>

              <?php if (in_array($trip['status'], [TRIP_BOARDING, TRIP_IN_PROGRESS], true)): ?>
                <a href="<?= e(url('views/driver/navigation.php?id=' . (int) $trip['id'])) ?>"
                   class="btn btn-outline-secondary">Open Navigation</a>
                <a href="<?= e(url('views/driver/passengers.php?tracking_id=' . (int) $trip['id'])) ?>"
                   class="btn btn-outline-secondary">View Passengers</a>
              <?php endif; ?>
            </div>

            <?php if (in_array($trip['status'], [TRIP_BOARDING, TRIP_IN_PROGRESS], true)): ?>
              <form method="post" class="p-3 rounded-3 bg-danger-subtle border border-danger-subtle"
                    onsubmit="return confirm('End this trip? Passengers will be marked completed.');">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="end">
                <input type="hidden" name="tracking_id" value="<?= (int) $trip['id'] ?>">
                <label class="form-label" for="notes">End notes (optional)</label>
                <textarea class="form-control mb-2" name="notes" id="notes" rows="2"></textarea>
                <button type="submit" class="btn btn-danger">End Trip</button>
              </form>
            <?php else: ?>
              <div class="alert alert-info mb-0">This trip is <?= e(str_replace('_', ' ', $trip['status'])) ?>.</div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card scts-card">
        <div class="card-header">
          <h2 class="h5 mb-0">Passengers (<?= count($passengers) ?>)</h2>
        </div>
        <div class="card-body p-0">
          <div class="list-group list-group-flush">
            <?php if (empty($passengers)): ?>
              <div class="list-group-item text-muted">No booked passengers yet.</div>
            <?php else: ?>
              <?php foreach (array_slice($passengers, 0, 8) as $p): ?>
                <div class="list-group-item">
                  <div class="d-flex justify-content-between">
                    <strong><?= e($p['passenger_name']) ?></strong>
                    <span class="badge bg-primary">Seat <?= e($p['seat_number']) ?></span>
                  </div>
                  <div class="small text-muted">
                    <?= e($p['pickup_name']) ?> → <?= e($p['destination_name']) ?>
                    <?php if (!empty($p['is_priority'])): ?> · <span class="text-warning">Priority</span><?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
