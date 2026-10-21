<?php
/**
 * AJAX Booking Endpoint
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Actions: schedules, seats, path, create, cancel
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
startAppSession();
attemptRememberLogin();

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Authentication required.'], 401);
}

$role = currentUserRole();
if (!in_array($role, [ROLE_STUDENT, ROLE_STAFF], true)) {
    jsonResponse(['success' => false, 'message' => 'Unauthorised.'], 403);
}

$isStaff = $role === ROLE_STAFF;
$userId = (int) currentUserId();
$action = clean($_REQUEST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
}

switch ($action) {
    case 'schedules':
        $date = clean($_GET['date'] ?? $_POST['date'] ?? date('Y-m-d'));
        $pickupId = (int) ($_GET['pickup_location_id'] ?? $_POST['pickup_location_id'] ?? 0) ?: null;
        $destId = (int) ($_GET['destination_location_id'] ?? $_POST['destination_location_id'] ?? 0) ?: null;
        jsonResponse(BookingController::getAvailableSchedules($date, $pickupId, $destId));
        break;

    case 'seats':
        $scheduleId = (int) ($_GET['schedule_id'] ?? $_POST['schedule_id'] ?? 0);
        $date = clean($_GET['date'] ?? $_POST['date'] ?? date('Y-m-d'));
        $schedule = Schedule::findById($scheduleId);
        if (!$schedule) {
            jsonResponse(['success' => false, 'seats' => [], 'message' => 'Schedule not found.']);
        }
        $seats = BookingController::getAvailableSeats(
            $scheduleId,
            $date,
            (int) ($schedule['available_seats'] ?? 14)
        );
        jsonResponse(['success' => true, 'seats' => $seats, 'message' => count($seats) . ' seat(s) available.']);
        break;

    case 'path':
        $pickupId = (int) ($_GET['pickup_location_id'] ?? $_POST['pickup_location_id'] ?? 0);
        $destId = (int) ($_GET['destination_location_id'] ?? $_POST['destination_location_id'] ?? 0);
        $path = BookingController::pathPreview($pickupId, $destId);
        jsonResponse(['success' => !empty($path['success']), 'path' => $path]);
        break;

    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'POST required.'], 405);
        }
        $result = BookingController::createBooking($_POST, $userId, $isStaff);
        jsonResponse($result, $result['success'] ? 200 : 422);
        break;

    case 'cancel':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'POST required.'], 405);
        }
        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $result = BookingController::cancelBooking($bookingId, $userId);
        jsonResponse($result, $result['success'] ? 200 : 422);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
