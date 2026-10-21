<?php
/**
 * Staff Book a Ride (with priority option)
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
attemptRememberLogin();
requireRole('staff');

$userId = (int) currentUserId();
$isStaff = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_booking') {
    requireCsrf();
    $result = BookingController::createBooking($_POST, $userId, true);
    if ($result['success']) {
        flash('success', $result['message']);
        redirect($result['ticket_url'] ?? url('views/staff/bookings.php'));
    }
    flash('error', $result['message']);
    redirect(url('views/staff/book.php'));
}

$locations = Location::getActive();
$pageTitle = 'Priority Booking';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/navbar.php';
?>

<div>
  <div class="mb-4">
    <h1 class="h3 mb-1">Book a Campus Shuttle</h1>
    <p class="text-muted mb-0">Staff priority booking is available and auto-approved when selected.</p>
  </div>

  <div class="card scts-card">
    <div class="card-body p-4">
      <?php require dirname(__DIR__, 2) . '/includes/booking_form.php'; ?>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
