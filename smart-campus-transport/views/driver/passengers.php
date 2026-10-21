<?php
/**
 * Driver Passengers List for Active / Selected Trip
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
$trackingId = (int) ($_GET['tracking_id'] ?? 0);
$scheduleId = (int) ($_GET['schedule_id'] ?? 0);
$date = clean($_GET['date'] ?? date('Y-m-d'));

$trip = null;
$schedule = null;

if ($trackingId > 0) {
    $trip = Tracking::findById($trackingId);
    if (!$trip || (int) $trip['driver_id'] !== $driverId) {
        flash('error', 'Trip not found or not assigned to you.');
        redirect(url('views/driver/trips.php'));
    }
    $scheduleId = (int) $trip['schedule_id'];
    $date = (string) $trip['trip_date'];
    $schedule = Schedule::findById($scheduleId);
} elseif ($scheduleId > 0) {
    $schedule = Schedule::findById($scheduleId);
    if (!$schedule || (int) ($schedule['driver_id'] ?? 0) !== $driverId) {
        flash('error', 'Schedule not found or not assigned to you.');
        redirect(url('views/driver/trips.php'));
    }
} else {
    $active = Tracking::getCurrentTrip($driverId);
    if ($active) {
        $trip = $active;
        $scheduleId = (int) $active['schedule_id'];
        $date = (string) $active['trip_date'];
        $schedule = Schedule::findById($scheduleId);
    }
}

if (!$schedule) {
    flash('warning', 'Select a trip to view passengers.');
    redirect(url('views/driver/trips.php'));
}

$passengers = DriverController::getPassengers($scheduleId, $date);
$priority = array_values(array_filter($passengers, static fn(array $p): bool => (int) ($p['is_priority'] ?? 0) === 1));
$regular = array_values(array_filter($passengers, static fn(array $p): bool => (int) ($p['is_priority'] ?? 0) !== 1));

$pageTitle = 'Passengers';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/navbar.php';
?>

<div>
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
      <h1 class="h3 mb-1">Passengers</h1>
      <p class="text-muted mb-0">
        <?= e($schedule['route_name'] ?? 'Route') ?>
        · <?= e(formatDate($date)) ?>
        · <?= e(substr((string) ($schedule['departure_time'] ?? ''), 0, 5)) ?>
        <?php if ($trip): ?> · <?= statusBadge($trip['status']) ?><?php endif; ?>
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= e(url('views/driver/trips.php')) ?>" class="btn btn-outline-secondary">Back</a>
      <?php if ($trip): ?>
        <a href="<?= e(url('views/driver/trip-action.php?id=' . (int) $trip['id'])) ?>"
           class="btn btn-primary">Trip Control</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="p-3 rounded-3 text-bg-primary">
        <div class="small opacity-75">Total Passengers</div>
        <div class="fs-3 fw-bold"><?= count($passengers) ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="p-3 rounded-3 bg-warning-subtle border border-warning-subtle">
        <div class="small text-muted">Priority</div>
        <div class="fs-3 fw-bold"><?= count($priority) ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="p-3 rounded-3 bg-body-tertiary border">
        <div class="small text-muted">Standard</div>
        <div class="fs-3 fw-bold"><?= count($regular) ?></div>
      </div>
    </div>
  </div>

  <div class="card scts-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-scts align-middle mb-0">
          <thead>
            <tr>
              <th>Seat</th>
              <th>Passenger</th>
              <th>Phone</th>
              <th>Pickup → Destination</th>
              <th>Booking #</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($passengers)): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">No passengers booked for this trip.</td></tr>
            <?php else: ?>
              <?php foreach ($passengers as $p): ?>
                <tr class="<?= !empty($p['is_priority']) ? 'table-warning' : '' ?>">
                  <td><span class="badge bg-primary"><?= e($p['seat_number']) ?></span></td>
                  <td>
                    <strong><?= e($p['passenger_name']) ?></strong>
                    <?php if (!empty($p['is_priority'])): ?>
                      <span class="badge bg-warning text-dark">Priority</span>
                    <?php endif; ?>
                  </td>
                  <td><?= e($p['passenger_phone'] ?? '—') ?></td>
                  <td><?= e($p['pickup_name']) ?> → <?= e($p['destination_name']) ?></td>
                  <td><code><?= e($p['booking_number']) ?></code></td>
                  <td><?= statusBadge($p['status']) ?></td>
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
