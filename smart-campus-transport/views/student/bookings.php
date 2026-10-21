<?php
/**
 * Student Bookings History
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
attemptRememberLogin();
requireRole('student');

$userId = (int) currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    requireCsrf();
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $result = BookingController::cancelBooking($bookingId, $userId);
    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect(url('views/student/bookings.php'));
}

$statusFilter = clean($_GET['status'] ?? '');
$bookings = Booking::getByUser($userId, $statusFilter !== '' ? $statusFilter : null);
$now = time();

$pageTitle = 'My Bookings';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/navbar.php';
?>

<div>
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
      <h1 class="h3 mb-1">My Bookings</h1>
      <p class="text-muted mb-0">View history, tickets, and cancel upcoming rides.</p>
    </div>
    <a href="<?= e(url('views/student/book.php')) ?>" class="btn btn-primary">New Booking</a>
  </div>

  <div class="mb-3 d-flex flex-wrap gap-2">
    <?php
    $filters = ['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    foreach ($filters as $key => $label):
        $active = $statusFilter === $key;
    ?>
      <a href="?status=<?= e($key) ?>"
         class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-outline-primary' ?>"
         ><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="card scts-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-scts table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Booking #</th>
              <th>Route</th>
              <th>Trip</th>
              <th>Seat</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($bookings)): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">No bookings found.</td></tr>
            <?php else: ?>
              <?php foreach ($bookings as $b):
                  $tripTs = strtotime($b['booking_date'] . ' ' . ($b['departure_time'] ?? '00:00:00'));
                  $canCancel = in_array($b['status'], [BOOKING_PENDING, BOOKING_APPROVED], true)
                      && $tripTs !== false && $tripTs > $now;
              ?>
                <tr>
                  <td>
                    <strong><?= e($b['booking_number']) ?></strong>
                    <?php if (!empty($b['is_priority'])): ?>
                      <span class="badge bg-warning text-dark">Priority</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?= e($b['route_name']) ?>
                    <div class="small text-muted"><?= e($b['pickup_name']) ?> → <?= e($b['destination_name']) ?></div>
                  </td>
                  <td>
                    <?= e(formatDate($b['booking_date'])) ?>
                    <div class="small text-muted"><?= e(substr((string) $b['departure_time'], 0, 5)) ?></div>
                  </td>
                  <td><?= e($b['seat_number']) ?></td>
                  <td><?= statusBadge($b['status']) ?></td>
                  <td class="text-end text-nowrap">
                    <a href="<?= e(url('views/student/ticket.php?id=' . (int) $b['id'])) ?>"
                       class="btn btn-sm btn-outline-secondary">View Ticket</a>
                    <?php if ($canCancel): ?>
                      <form method="post" class="d-inline" onsubmit="return confirm('Cancel this booking?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="booking_id" value="<?= (int) $b['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                      </form>
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
