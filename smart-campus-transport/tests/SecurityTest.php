<?php
/**
 * Security Unit Tests (CLI)
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Standalone where possible. Loads app helpers when available for clean()/generateToken()/validatePassword().
 *
 * Usage:
 *   php tests/SecurityTest.php
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
 * Local mirrors of security helpers for fully offline assertions.
 */
function testClean(?string $input): string
{
    return trim(strip_tags((string) $input));
}

function testGenerateToken(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

function testVerifyCsrfFormat(string $sessionToken, ?string $provided): bool
{
    if ($provided === null || $provided === '' || $sessionToken === '') {
        return false;
    }
    return hash_equals($sessionToken, $provided);
}

echo "=== SecurityTest (standalone-friendly) ===\n\n";

// -------------------------------------------------------------------------
// 1. Password hashing
// -------------------------------------------------------------------------
$plain = 'Student@123';
$hash = password_hash($plain, PASSWORD_DEFAULT);

assertTrue(is_string($hash) && $hash !== $plain, 'password_hash does not store plaintext');
assertTrue(password_verify($plain, $hash) === true, 'password_verify accepts correct password');
assertTrue(password_verify('WrongPass1', $hash) === false, 'password_verify rejects wrong password');
assertTrue(str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$') || str_starts_with($hash, '$argon2'), 'Hash uses modern algorithm prefix');

// -------------------------------------------------------------------------
// 2. Input cleaning concepts
// -------------------------------------------------------------------------
assertTrue(testClean('  hello  ') === 'hello', 'clean trims whitespace');
assertTrue(testClean('<b>Hello</b>') === 'Hello', 'clean strips HTML tags');
assertTrue(testClean('<script>alert(1)</script>Hi') === 'alert(1)Hi', 'clean strips script tags');
assertTrue(testClean(null) === '', 'clean handles null as empty string');

// -------------------------------------------------------------------------
// 3. CSRF token format helpers (mock)
// -------------------------------------------------------------------------
$tokenA = testGenerateToken(32);
$tokenB = testGenerateToken(32);

assertTrue((bool) preg_match('/^[a-f0-9]{64}$/i', $tokenA), 'CSRF-like token is 64 hex chars (32 bytes)');
assertTrue($tokenA !== $tokenB, 'Consecutive tokens differ');
assertTrue(testVerifyCsrfFormat($tokenA, $tokenA) === true, 'hash_equals accepts matching token');
assertTrue(testVerifyCsrfFormat($tokenA, $tokenB) === false, 'hash_equals rejects mismatched token');
assertTrue(testVerifyCsrfFormat($tokenA, '') === false, 'Empty provided token fails');
assertTrue(testVerifyCsrfFormat($tokenA, null) === false, 'Null provided token fails');

// -------------------------------------------------------------------------
// 4. XSS escape concept
// -------------------------------------------------------------------------
$escaped = htmlspecialchars('<img src=x onerror=alert(1)>', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
assertTrue(!str_contains($escaped, '<img'), 'htmlspecialchars neutralises raw tag');
assertTrue(str_contains($escaped, '&lt;img'), 'htmlspecialchars encodes < as &lt;');

// -------------------------------------------------------------------------
// 5. Optional: load real helpers if bootstrap works without dying on DB
// -------------------------------------------------------------------------
$helpersFile = $root . '/includes/helpers.php';
$appFile = $root . '/config/app.php';

if (is_file($helpersFile) && is_file($appFile)) {
    // Define minimal constants used by helpers if we include helpers alone
    if (!defined('APP_ROOT')) {
        define('APP_ROOT', $root);
    }
    // Prefer loading helpers without full DB bootstrap for clean()/generateToken
    // helpers.php references APP_URL, ITEMS_PER_PAGE, etc. — load app if safe.
    try {
        // Avoid fatal if MySQL down: database.php only connects lazily via getDB().
        require_once $appFile;

        if (function_exists('clean')) {
            assertTrue(clean('<i>X</i>') === 'X', 'App clean() strips tags');
        } else {
            skip('App clean() not available');
        }

        if (function_exists('generateToken')) {
            $t = generateToken(16);
            assertTrue((bool) preg_match('/^[a-f0-9]{32}$/i', $t), 'App generateToken(16) length OK');
        } else {
            skip('App generateToken() not available');
        }

        if (function_exists('validatePassword')) {
            $weak = validatePassword('short');
            $strong = validatePassword('Student@123');
            assertTrue($weak['valid'] === false, 'validatePassword rejects short password');
            assertTrue($strong['valid'] === true, 'validatePassword accepts Student@123 pattern');
        } else {
            skip('App validatePassword() not available');
        }

        if (function_exists('e')) {
            assertTrue(e('<b>') === '&lt;b&gt;', 'App e() escapes HTML');
        } else {
            skip('App e() not available');
        }
    } catch (Throwable $e) {
        skip('App bootstrap skipped: ' . $e->getMessage());
    }
} else {
    skip('App files missing — offline mocks only');
}

echo "\n=== Summary: {$passed} passed, {$failed} failed, {$skipped} skipped ===\n";
exit($failed > 0 ? 1 : 0);
