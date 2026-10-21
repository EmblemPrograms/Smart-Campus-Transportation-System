<?php
/**
 * AJAX Booking Actions Endpoint
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Actions: approve, cancel, complete
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
startAppSession();
requireRole('admin');
requireCsrf();

$action = clean($_POST['action'] ?? '');
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid booking ID'], 422);
}

$booking = Booking::findById($id);
if (!$booking) {
    jsonResponse(['success' => false, 'message' => 'Booking not found'], 404);
}

$uid = currentUserId();

if ($action === 'approve') {
    $ok = Booking::approve($id);
    if ($ok) {
        createNotification(
            (int) $booking['user_id'],
            'Booking Approved',
            'Your booking ' . $booking['booking_number'] . ' has been approved.',
            'success'
        );
        logActivity($uid, 'approve', "Approved booking #{$id}", 'bookings');
    }
    jsonResponse(['success' => $ok, 'message' => $ok ? 'Booking approved' : 'Approve failed', 'status' => BOOKING_APPROVED]);
}

if ($action === 'cancel') {
    $ok = Booking::cancel($id, $uid);
    if ($ok) {
        createNotification(
            (int) $booking['user_id'],
            'Booking Cancelled',
            'Your booking ' . $booking['booking_number'] . ' was cancelled.',
            'warning'
        );
        logActivity($uid, 'cancel', "Cancelled booking #{$id}", 'bookings');
    }
    jsonResponse(['success' => $ok, 'message' => $ok ? 'Booking cancelled' : 'Cancel failed', 'status' => BOOKING_CANCELLED]);
}

if ($action === 'complete') {
    $ok = Booking::complete($id);
    if ($ok) {
        logActivity($uid, 'complete', "Completed booking #{$id}", 'bookings');
    }
    jsonResponse(['success' => $ok, 'message' => $ok ? 'Booking completed' : 'Complete failed', 'status' => BOOKING_COMPLETED]);
}

jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
