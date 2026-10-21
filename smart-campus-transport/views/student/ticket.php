<?php
/**
 * Student Printable Ticket
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
attemptRememberLogin();
requireRole('student');

$userId = (int) currentUserId();
$bookingId = (int) ($_GET['id'] ?? 0);
$ticket = Booking::generateTicketData($bookingId);

if (!$ticket || (int) (Booking::findById($bookingId)['user_id'] ?? 0) !== $userId) {
    flash('error', 'Ticket not found or access denied.');
    redirect(url('views/student/bookings.php'));
}

$pageTitle = 'Ticket ' . $ticket['booking_number'];
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/navbar.php';
?>

<style>
@media print {
  .sidebar, .navbar, .no-print, .layout-sidebar, aside, nav { display:none !important; }
  .ticket-card { box-shadow:none !important; border:2px solid #0d9488 !important; }
  body { background:#fff !important; }
}
.ticket-card {
  max-width:520px; margin:0 auto; border:2px dashed #0d9488; border-radius:1rem;
  background:linear-gradient(180deg,#ffffff 0%,#f0fdfa 100%); overflow:hidden;
}
.ticket-header { background:linear-gradient(135deg,#0d9488,#14b8a6); color:#fff; padding:1.25rem 1.5rem; }
.qr-block {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  letter-spacing:.12em; font-size:1.05rem; font-weight:700; color:#0f766e;
  background:#ecfdf5; border:2px solid #99f6e4; border-radius:.75rem;
  padding:1rem; text-align:center; word-break:break-all;
}
.qr-grid {
  width:120px; height:120px; margin:0 auto .75rem; display:grid;
  grid-template-columns:repeat(5,1fr); gap:3px;
}
.qr-grid span { background:#0f766e; border-radius:2px; }
.qr-grid span.off { background:#ccfbf1; }
</style>

<div>
  <div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h1 class="h3 mb-0">Boarding Ticket</h1>
    <div class="d-flex gap-2">
      <a href="<?= e(url('views/student/bookings.php')) ?>" class="btn btn-outline-secondary">Back</a>
      <button type="button" class="btn btn-primary" onclick="window.print()">Print Ticket</button>
    </div>
  </div>

  <div class="ticket-card shadow-sm">
    <div class="ticket-header">
      <div class="small opacity-75"><?= e(APP_INSTITUTION) ?></div>
      <div class="fs-5 fw-bold"><?= e(APP_FULL_NAME) ?></div>
      <div class="mt-2 d-flex justify-content-between align-items-center">
        <span><?= statusBadge($ticket['status']) ?></span>
        <?php if (!empty($ticket['is_priority'])): ?>
          <span class="badge bg-warning text-dark">Priority</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="p-4">
      <div class="text-center mb-4">
        <?php
        // Deterministic “QR-like” pattern from booking number
        $hash = md5($ticket['booking_number']);
        ?>
        <div class="qr-grid" aria-hidden="true">
          <?php for ($i = 0; $i < 25; $i++):
              $on = hexdec($hash[$i % strlen($hash)]) % 2 === 0;
          ?>
            <span class="<?= $on ? '' : 'off' ?>"></span>
          <?php endfor; ?>
        </div>
        <div class="qr-block"><?= e($ticket['booking_number']) ?></div>
        <div class="small text-muted mt-2">Present this code when boarding</div>
      </div>

      <dl class="row mb-0">
        <dt class="col-5 text-muted">Passenger</dt>
        <dd class="col-7 fw-semibold"><?= e($ticket['passenger_name']) ?></dd>

        <dt class="col-5 text-muted">Route</dt>
        <dd class="col-7"><?= e($ticket['route_name']) ?> <span class="text-muted">(<?= e($ticket['route_code']) ?>)</span></dd>

        <dt class="col-5 text-muted">From</dt>
        <dd class="col-7"><?= e($ticket['pickup']) ?></dd>

        <dt class="col-5 text-muted">To</dt>
        <dd class="col-7"><?= e($ticket['destination']) ?></dd>

        <dt class="col-5 text-muted">Date</dt>
        <dd class="col-7"><?= e(formatDate($ticket['booking_date'])) ?></dd>

        <dt class="col-5 text-muted">Time</dt>
        <dd class="col-7">
          <?= e(substr((string) $ticket['departure_time'], 0, 5)) ?>
          – <?= e(substr((string) $ticket['arrival_time'], 0, 5)) ?>
        </dd>

        <dt class="col-5 text-muted">Seat</dt>
        <dd class="col-7"><span class="badge fs-6 bg-primary"><?= e($ticket['seat_number']) ?></span></dd>

        <dt class="col-5 text-muted">Vehicle</dt>
        <dd class="col-7"><?= e($ticket['vehicle']) ?></dd>

        <dt class="col-5 text-muted">Driver</dt>
        <dd class="col-7"><?= e($ticket['driver']) ?></dd>
      </dl>

      <hr>
      <div class="small text-muted text-center">
        Issued <?= e(formatDateTime($ticket['issued_at'])) ?> · <?= e($ticket['system']) ?>
      </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
