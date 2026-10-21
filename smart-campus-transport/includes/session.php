<?php
/**
 * Session Management
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

/**
 * Start a secure application session.
 *
 * @return void
 */
function startAppSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    // Session fixation protection
    if (empty($_SESSION['_initiated'])) {
        session_regenerate_id(true);
        $_SESSION['_initiated'] = true;
        $_SESSION['_created'] = time();
    }

    // Idle timeout check
    if (!empty($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity']) > SESSION_TIMEOUT) {
        $hadUser = !empty($_SESSION['user_id']);
        destroySession();
        startAppSession();
        if ($hadUser) {
            $_SESSION['flash'][] = ['type' => 'warning', 'message' => 'Your session expired due to inactivity. Please log in again.'];
        }
        return;
    }

    $_SESSION['_last_activity'] = time();

    // Regenerate ID periodically (every 30 minutes)
    if (!empty($_SESSION['_created']) && (time() - $_SESSION['_created']) > 1800) {
        session_regenerate_id(true);
        $_SESSION['_created'] = time();
    }
}

/**
 * Destroy the current session completely.
 *
 * @return void
 */
function destroySession(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Set remember-me cookie.
 *
 * @param int $userId User ID
 * @param string $token Remember token
 * @return void
 */
function setRememberCookie(int $userId, string $token): void
{
    $expiry = time() + (REMEMBER_ME_DAYS * 86400);
    $value = $userId . ':' . $token;
    setcookie('scts_remember', $value, [
        'expires' => $expiry,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Clear remember-me cookie.
 *
 * @return void
 */
function clearRememberCookie(): void
{
    setcookie('scts_remember', '', time() - 3600, '/');
}
