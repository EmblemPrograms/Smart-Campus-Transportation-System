<?php
/**
 * AJAX Seats Endpoint
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Returns available seats for a schedule + date combination.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
startAppSession();
requireLogin();

$scheduleId = (int) ($_REQUEST['schedule_id'] ?? 0);
$date = clean($_REQUEST['date'] ?? date('Y-m-d'));

if ($scheduleId <= 0) {
    jsonResponse(['success' => false, 'message' => 'schedule_id required'], 422);
}

$schedule = Schedule::findById($scheduleId);
if (!$schedule) {
    jsonResponse(['success' => false, 'message' => 'Schedule not found'], 404);
}

$capacity = (int) ($schedule['capacity'] ?? $schedule['available_seats'] ?? 20);
if ($capacity <= 0) {
    $capacity = (int) ($schedule['available_seats'] ?? 20);
}

$seats = [];
$taken = [];

try {
    $db = getDB();

    // Prefer seats table rows when present
    $stmt = $db->prepare(
        'SELECT seat_number, is_available, booking_id
         FROM seats
         WHERE schedule_id = ? AND trip_date = ?
         ORDER BY seat_number ASC'
    );
    $stmt->bind_param('is', $scheduleId, $date);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!empty($rows)) {
        foreach ($rows as $row) {
            $num = (string) $row['seat_number'];
            $available = (int) $row['is_available'] === 1 && empty($row['booking_id']);
            $seats[] = [
                'seat_number' => $num,
                'available' => $available,
            ];
            if (!$available) {
                $taken[] = $num;
            }
        }
    } else {
        // Fall back to bookings table for taken seats
        $pending = BOOKING_PENDING;
        $approved = BOOKING_APPROVED;
        $bStmt = $db->prepare(
            'SELECT seat_number FROM bookings
             WHERE schedule_id = ? AND booking_date = ? AND status IN (?, ?)'
        );
        $bStmt->bind_param('isss', $scheduleId, $date, $pending, $approved);
        $bStmt->execute();
        $takenRows = $bStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $bStmt->close();

        foreach ($takenRows as $tr) {
            $taken[] = (string) $tr['seat_number'];
        }

        for ($i = 1; $i <= $capacity; $i++) {
            $num = (string) $i;
            $available = !in_array($num, $taken, true);
            $seats[] = [
                'seat_number' => $num,
                'available' => $available,
            ];
        }
    }
} catch (Throwable $e) {
    error_log('ajax/seats failed: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to load seats'], 500);
}

$availableCount = count(array_filter($seats, static fn($s) => $s['available']));

jsonResponse([
    'success' => true,
    'schedule_id' => $scheduleId,
    'date' => $date,
    'capacity' => $capacity,
    'available_count' => $availableCount,
    'taken' => $taken,
    'seats' => $seats,
]);
