<?php
/**
 * AJAX Tracking Endpoint
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Actions: active (get active trips), update (driver location update)
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
startAppSession();
requireLogin();

$action = clean($_REQUEST['action'] ?? 'active');

if ($action === 'active') {
    $trips = Tracking::getActiveTrips();
    jsonResponse([
        'success' => true,
        'trips' => $trips,
        'count' => count($trips),
        'refreshed_at' => date('c'),
    ]);
}

if ($action === 'update') {
    requireCsrf();
    $role = currentUserRole();
    if (!in_array($role, ['admin', 'driver'], true)) {
        jsonResponse(['success' => false, 'message' => 'Unauthorised'], 403);
    }

    $trackingId = (int) ($_POST['tracking_id'] ?? 0);
    $lat = (float) ($_POST['latitude'] ?? 0);
    $lng = (float) ($_POST['longitude'] ?? 0);
    $locationId = isset($_POST['location_id']) && $_POST['location_id'] !== ''
        ? (int) $_POST['location_id']
        : null;

    if ($trackingId <= 0) {
        jsonResponse(['success' => false, 'message' => 'tracking_id required'], 422);
    }

    $ok = Tracking::updateLocation($trackingId, $lat, $lng, $locationId);
    jsonResponse([
        'success' => $ok,
        'message' => $ok ? 'Location updated' : 'Update failed',
    ]);
}

if ($action === 'by_date') {
    $date = clean($_REQUEST['date'] ?? date('Y-m-d'));
    jsonResponse([
        'success' => true,
        'trips' => Tracking::getByDate($date),
    ]);
}

jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
