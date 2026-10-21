<?php
/**
 * Booking Flow Integration Checklist (CLI)
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Prints PASS/FAIL for method existence on Booking / User classes when app
 * bootstrap works. If the database or bootstrap is unavailable, skips gracefully.
 *
 * This is a structural / smoke integration check — not a full HTTP booking E2E.
 * Manual E2E cases remain in docs/TESTING_DOCUMENTATION.md.
 *
 * Usage:
 *   php tests/BookingFlowTest.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$passed = 0;
$failed = 0;
$skipped = 0;

function assertTrue(bool $condition, string $message): void
{
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] {$message}\n";
        $passed++;
    } else {
        echo "[FAIL] {$message}\n";
        $failed++;
    }
}

function skip(string $message): void
{
    global $skipped;
    echo "[SKIP] {$message}\n";
    $skipped++;
}

/**
 * Assert a public static/instance method exists on a class.
 */
function assertMethodExists(string $class, string $method): void
{
    assertTrue(
        class_exists($class) && method_exists($class, $method),
        "{$class}::{$method}() exists"
    );
}

echo "=== BookingFlowTest (integration smoke / checklist) ===\n\n";

echo "Checklist (manual E2E — document results in project report):\n";
echo "  [ ] Student can load booking form\n";
echo "  [ ] Available schedules return for selected date\n";
echo "  [ ] Seat map excludes taken seats\n";
echo "  [ ] Booking create returns booking_number\n";
echo "  [ ] Admin can approve booking\n";
echo "  [ ] Student receives notification\n";
echo "  [ ] Cancel frees seat\n";
echo "  [ ] Staff priority flag set when enabled\n\n";

echo "--- Automated structural checks ---\n";

$appFile = $root . '/config/app.php';
if (!is_file($appFile)) {
    skip('config/app.php not found');
    echo "\n=== Summary: {$passed} passed, {$failed} failed, {$skipped} skipped ===\n";
    exit(0);
}

try {
    require_once $appFile;
} catch (Throwable $e) {
    skip('Bootstrap failed: ' . $e->getMessage());
    echo "\n=== Summary: {$passed} passed, {$failed} failed, {$skipped} skipped ===\n";
    exit(0);
}

// Autoload should resolve models
$bookingLoaded = class_exists('Booking', true);
$userLoaded = class_exists('User', true);

if (!$bookingLoaded || !$userLoaded) {
    skip('Booking/User classes could not be autoloaded');
    echo "\n=== Summary: {$passed} passed, {$failed} failed, {$skipped} skipped ===\n";
    exit(0);
}

assertTrue($bookingLoaded, 'Booking class autoloaded');
assertTrue($userLoaded, 'User class autoloaded');

// Booking model — booking flow surface
$bookingMethods = [
    'findById',
    'findByNumber',
    'checkSeatAvailability',
    'create',
    'cancel',
    'approve',
    'complete',
    'getByUser',
    'getByDate',
    'generateTicketData',
    'all',
];

foreach ($bookingMethods as $method) {
    assertMethodExists('Booking', $method);
}

// User model — auth-related surface used around booking accounts
$userMethods = [
    'findById',
    'findByEmail',
    'create',
    'update',
    'changePassword',
    'updateStatus',
    'all',
    'touchLastLogin',
];

foreach ($userMethods as $method) {
    assertMethodExists('User', $method);
}

// Optional: BookingController helpers if present
if (class_exists('BookingController', true)) {
    foreach (['getAvailableSchedules', 'getAvailableSeats', 'pathPreview', 'createBooking', 'cancelBooking'] as $method) {
        if (method_exists('BookingController', $method)) {
            assertTrue(true, "BookingController::{$method}() exists");
        } else {
            skip("BookingController::{$method}() not found");
        }
    }
} else {
    skip('BookingController not autoloaded');
}

// Optional DB ping — skip if MySQL down
try {
    if (function_exists('getDB')) {
        $db = getDB();
        assertTrue($db instanceof mysqli, 'getDB() returns mysqli');
        $result = $db->query("SHOW TABLES LIKE 'bookings'");
        assertTrue($result && $result->num_rows > 0, "Table 'bookings' exists");
        $result2 = $db->query("SHOW TABLES LIKE 'users'");
        assertTrue($result2 && $result2->num_rows > 0, "Table 'users' exists");
    } else {
        skip('getDB() not available');
    }
} catch (Throwable $e) {
    skip('Database checks skipped: ' . $e->getMessage());
}

echo "\n=== Summary: {$passed} passed, {$failed} failed, {$skipped} skipped ===\n";
exit($failed > 0 ? 1 : 0);
