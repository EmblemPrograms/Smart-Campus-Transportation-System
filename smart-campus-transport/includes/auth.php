<?php
/**
 * Authentication Helpers
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

/**
 * Check if a user is logged in.
 *
 * @return bool
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

/**
 * Get current logged-in user ID.
 *
 * @return int|null
 */
function currentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Get current user role.
 *
 * @return string|null
 */
function currentUserRole(): ?string
{
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get current user display name.
 *
 * @return string
 */
function currentUserName(): string
{
    return $_SESSION['user_name'] ?? 'Guest';
}

/**
 * Get current user email.
 *
 * @return string
 */
function currentUserEmail(): string
{
    return $_SESSION['user_email'] ?? '';
}

/**
 * Require authentication; redirect to login if not authenticated.
 *
 * @return void
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        flash('warning', 'Please log in to continue.');
        redirect(url('views/auth/login.php'));
    }
}

/**
 * Require one of the allowed roles.
 *
 * @param string|array $roles Allowed role(s)
 * @return void
 */
function requireRole($roles): void
{
    requireLogin();
    $roles = (array) $roles;
    if (!in_array(currentUserRole(), $roles, true)) {
        flash('error', 'You are not authorised to access that page.');
        redirect(dashboardUrl());
    }
}

/**
 * Get dashboard URL for current (or given) role.
 *
 * @param string|null $role Role override
 * @return string Dashboard URL
 */
function dashboardUrl(?string $role = null): string
{
    $role = $role ?? currentUserRole() ?? 'guest';
    $map = [
        'admin' => url('views/admin/dashboard.php'),
        'student' => url('views/student/dashboard.php'),
        'staff' => url('views/staff/dashboard.php'),
        'driver' => url('views/driver/dashboard.php'),
    ];
    return $map[$role] ?? url('index.php');
}

/**
 * Attempt remember-me auto-login.
 *
 * @return bool True if logged in via cookie
 */
function attemptRememberLogin(): bool
{
    if (isLoggedIn() || empty($_COOKIE['scts_remember'])) {
        return false;
    }

    $parts = explode(':', $_COOKIE['scts_remember'], 2);
    if (count($parts) !== 2) {
        clearRememberCookie();
        return false;
    }

    [$userId, $token] = $parts;
    $userId = (int) $userId;

    try {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT id, full_name, email, role, status, remember_token
             FROM users WHERE id = ? AND status = ? LIMIT 1'
        );
        $active = 'active';
        $stmt->bind_param('is', $userId, $active);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || empty($user['remember_token']) || !hash_equals($user['remember_token'], hash('sha256', $token))) {
            clearRememberCookie();
            return false;
        }

        loginUser($user);
        return true;
    } catch (Throwable $e) {
        error_log('Remember login failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Establish authenticated session for a user.
 *
 * @param array $user User row from database
 * @return void
 */
function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['_created'] = time();
    $_SESSION['_last_activity'] = time();
}

/**
 * Log out the current user.
 *
 * @return void
 */
function logoutUser(): void
{
    $userId = currentUserId();
    if ($userId) {
        try {
            $db = getDB();
            $stmt = $db->prepare('UPDATE users SET remember_token = NULL WHERE id = ?');
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        } catch (Throwable $e) {
            error_log('Logout token clear failed: ' . $e->getMessage());
        }
        logActivity($userId, 'logout', 'User logged out', 'auth');
    }
    clearRememberCookie();
    destroySession();
}

/**
 * Hash a password securely.
 *
 * @param string $password Plain password
 * @return string Password hash
 */
function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify a password against a hash.
 *
 * @param string $password Plain password
 * @param string $hash Stored hash
 * @return bool
 */
function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}
