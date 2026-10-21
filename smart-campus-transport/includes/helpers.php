<?php
/**
 * Helper Functions
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

/**
 * Escape output for XSS protection.
 *
 * @param mixed $value Value to escape
 * @return string Escaped string
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Redirect to a URL and exit.
 *
 * @param string $url Target URL
 * @return void
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Get base application URL with optional path.
 *
 * @param string $path Relative path
 * @return string Full URL
 */
function url(string $path = ''): string
{
    $base = rtrim(APP_URL, '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base : $base . '/' . $path;
}

/**
 * Get asset URL.
 *
 * @param string $path Asset relative path
 * @return string Full asset URL
 */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Flash a session message.
 *
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message text
 * @return void
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash messages.
 *
 * @return array List of flash messages
 */
function getFlash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Check if request is AJAX.
 *
 * @return bool
 */
function isAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Send JSON response and exit.
 *
 * @param array $data Response payload
 * @param int $status HTTP status code
 * @return void
 */
function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Sanitize string input.
 *
 * @param string|null $input Raw input
 * @return string Cleaned string
 */
function clean(?string $input): string
{
    return trim(strip_tags((string) $input));
}

/**
 * Validate email address.
 *
 * @param string $email Email to validate
 * @return bool
 */
function isValidEmail(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate a unique booking number.
 *
 * @return string Booking number e.g. BK-20260729-A1B2
 */
function generateBookingNumber(): string
{
    return 'BK-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
}

/**
 * Format date for display.
 *
 * @param string|null $date Date string
 * @param string $format PHP date format
 * @return string Formatted date
 */
function formatDate(?string $date, string $format = 'd M Y'): string
{
    if (empty($date) || $date === '0000-00-00') {
        return '—';
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime for display.
 *
 * @param string|null $datetime Datetime string
 * @return string Formatted datetime
 */
function formatDateTime(?string $datetime): string
{
    if (empty($datetime)) {
        return '—';
    }
    return date('d M Y, h:i A', strtotime($datetime));
}

/**
 * Format distance in kilometres.
 *
 * @param float|int $metres Distance in metres
 * @return string Formatted distance
 */
function formatDistance($metres): string
{
    $km = (float) $metres / 1000;
    if ($km < 1) {
        return number_format((float) $metres, 0) . ' m';
    }
    return number_format($km, 2) . ' km';
}

/**
 * Get pagination offset.
 *
 * @param int $page Current page
 * @param int $perPage Items per page
 * @return int SQL OFFSET
 */
function paginateOffset(int $page, int $perPage = ITEMS_PER_PAGE): int
{
    $page = max(1, $page);
    return ($page - 1) * $perPage;
}

/**
 * Build pagination metadata.
 *
 * @param int $total Total records
 * @param int $page Current page
 * @param int $perPage Items per page
 * @return array Pagination info
 */
function paginate(int $total, int $page, int $perPage = ITEMS_PER_PAGE): array
{
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'offset' => paginateOffset($page, $perPage),
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages,
    ];
}

/**
 * Log activity to activity_logs table.
 *
 * @param int|null $userId User ID
 * @param string $action Action performed
 * @param string $description Description
 * @param string|null $module Module name
 * @return void
 */
function logActivity(?int $userId, string $action, string $description, ?string $module = null): void
{
    try {
        $db = getDB();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $stmt = $db->prepare(
            'INSERT INTO activity_logs (user_id, action, description, module, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->bind_param('isssss', $userId, $action, $description, $module, $ip, $ua);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('Activity log failed: ' . $e->getMessage());
    }
}

/**
 * Create an in-app notification.
 *
 * @param int $userId Recipient user ID
 * @param string $title Notification title
 * @param string $message Notification body
 * @param string $type Notification type
 * @param string|null $link Optional link
 * @return bool Success
 */
function createNotification(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): bool
{
    try {
        $db = getDB();
        $stmt = $db->prepare(
            'INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at)
             VALUES (?, ?, ?, ?, ?, 0, NOW())'
        );
        $stmt->bind_param('issss', $userId, $title, $message, $type, $link);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    } catch (Throwable $e) {
        error_log('Notification create failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get status badge HTML class.
 *
 * @param string $status Status value
 * @return string Bootstrap badge class
 */
function statusBadgeClass(string $status): string
{
    $map = [
        'pending' => 'bg-warning text-dark',
        'approved' => 'bg-success',
        'completed' => 'bg-primary',
        'cancelled' => 'bg-danger',
        'scheduled' => 'bg-info text-dark',
        'boarding' => 'bg-warning text-dark',
        'in_progress' => 'bg-primary',
        'active' => 'bg-success',
        'inactive' => 'bg-secondary',
        'available' => 'bg-success',
        'maintenance' => 'bg-warning text-dark',
        'unavailable' => 'bg-danger',
    ];
    return $map[strtolower($status)] ?? 'bg-secondary';
}

/**
 * Render a status badge.
 *
 * @param string $status Status value
 * @return string HTML badge
 */
function statusBadge(string $status): string
{
    $class = statusBadgeClass($status);
    return '<span class="badge ' . e($class) . '">' . e(ucwords(str_replace('_', ' ', $status))) . '</span>';
}

/**
 * Get client IP address.
 *
 * @return string
 */
function getClientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Generate a random token.
 *
 * @param int $length Token byte length
 * @return string Hex token
 */
function generateToken(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

/**
 * Check password strength.
 *
 * @param string $password Password to check
 * @return array{valid:bool,message:string}
 */
function validatePassword(string $password): array
{
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return ['valid' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.'];
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one uppercase letter.'];
    }
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one lowercase letter.'];
    }
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one number.'];
    }
    return ['valid' => true, 'message' => 'Password is strong.'];
}

/**
 * Relative time ago string.
 *
 * @param string $datetime Datetime
 * @return string Human-readable relative time
 */
function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) {
        return 'just now';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' min ago';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . ' hrs ago';
    }
    if ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    }
    return formatDate($datetime);
}
