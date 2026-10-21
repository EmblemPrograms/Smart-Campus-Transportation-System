<?php
/**
 * AJAX Dijkstra Shortest Path Endpoint
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Computes shortest path between two campus locations.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
startAppSession();
requireLogin();

$action = clean($_REQUEST['action'] ?? 'shortest_path');

if ($action === 'shortest_path' || $action === 'compute') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireCsrf();
    }

    $startId = (int) ($_REQUEST['start_id'] ?? $_REQUEST['start_location_id'] ?? 0);
    $endId = (int) ($_REQUEST['end_id'] ?? $_REQUEST['end_location_id'] ?? 0);

    if ($startId <= 0 || $endId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Start and end location IDs are required.'], 422);
    }

    $dijkstra = new Dijkstra(true);
    $result = $dijkstra->shortestPath($startId, $endId);

    if (!$result['success']) {
        jsonResponse([
            'success' => false,
            'message' => $result['message'] ?? 'No path found.',
            'path' => [],
            'path_names' => [],
            'distance' => 0,
        ]);
    }

    $details = $dijkstra->getPathDetails();

    jsonResponse([
        'success' => true,
        'message' => 'Shortest path found.',
        'path' => $details['path'],
        'path_names' => $details['path_names'],
        'distance' => $details['distance'],
        'distance_formatted' => $details['distance_formatted'],
        'hops' => $details['hops'],
        'locations' => $details['locations'],
        'segments' => $details['segments'],
    ]);
}

if ($action === 'graph') {
    $locations = Location::getActive();
    $roads = Road::all('active');
    jsonResponse([
        'success' => true,
        'locations' => $locations,
        'roads' => $roads,
    ]);
}

jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
