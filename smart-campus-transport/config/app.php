<?php
/**
 * Application Configuration
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Application meta
define('APP_NAME', 'Smart Campus Transport');
define('APP_FULL_NAME', 'Smart Campus Transportation System');
define('APP_INSTITUTION', 'Federal Polytechnic Ede');
define('APP_VERSION', '1.0.0');

/*
 * Base application URL.
 *
 * Auto-detected from the incoming request so that every link, asset and AJAX
 * endpoint keeps working no matter what the containing folder is named or how
 * deeply it is nested under the web root (e.g. it correctly resolves the
 * "Smart Campus Transportation System/smart-campus-transport" path used here).
 *
 * The web path is derived by subtracting the server DOCUMENT_ROOT from this
 * application's root directory, then URL-encoding each path segment so folder
 * names containing spaces produce valid URLs. Falls back to a fixed URL when
 * run from the command line (installer, seeders, tests).
 */
if (!defined('APP_URL')) {
    if (PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST'])) {
        define('APP_URL', 'http://localhost/smart-campus-transport');
    } else {
        $scheme = (
            (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? null) == 443)
            || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
        ) ? 'https' : 'http';

        $host = $_SERVER['HTTP_HOST'];

        // Derive the web-facing base path from document root vs. application root.
        $docRoot = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));
        $appRoot = str_replace('\\', '/', APP_ROOT);
        $basePath = '';

        // Case-insensitive prefix match (Windows paths are case-insensitive).
        if ($docRoot !== '' && stripos($appRoot, $docRoot) === 0) {
            $basePath = substr($appRoot, strlen($docRoot));
        } else {
            // Fallback: derive from the running script's URL path.
            $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
            $scriptFile = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
            $relFromApp = '';
            if ($scriptFile !== '' && stripos($scriptFile, $appRoot) === 0) {
                $relFromApp = substr($scriptFile, strlen($appRoot)); // e.g. /views/auth/login.php
            }
            if ($relFromApp !== '' && $scriptName !== '' && str_ends_with($scriptName, $relFromApp)) {
                $basePath = substr($scriptName, 0, -strlen($relFromApp));
            } else {
                $basePath = rtrim(dirname($scriptName), '/');
            }
        }

        // URL-encode each segment so spaces and other characters are valid.
        $basePath = implode('/', array_map('rawurlencode', explode('/', trim($basePath, '/'))));
        $basePath = $basePath === '' ? '' : '/' . $basePath;

        define('APP_URL', $scheme . '://' . $host . $basePath);
    }
}

define('APP_DEBUG', true);

// Timezone
date_default_timezone_set('Africa/Lagos');

// Session settings
define('SESSION_NAME', 'SCTS_SESSION');
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_TIMEOUT', 1800);  // 30 minutes idle timeout
define('REMEMBER_ME_DAYS', 30);

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

// Upload settings
define('UPLOAD_PATH', APP_ROOT . '/uploads/');
define('UPLOAD_MAX_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Pagination
define('ITEMS_PER_PAGE', 10);

// Tracking refresh interval (seconds)
define('TRACKING_REFRESH_INTERVAL', 15);

// Email settings (verification-ready)
define('MAIL_FROM', 'noreply@fedpolyede.edu.ng');
define('MAIL_FROM_NAME', APP_NAME);
define('MAIL_ENABLED', false); // Set true when SMTP is configured

// Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_STUDENT', 'student');
define('ROLE_STAFF', 'staff');
define('ROLE_DRIVER', 'driver');

// Booking statuses
define('BOOKING_PENDING', 'pending');
define('BOOKING_APPROVED', 'approved');
define('BOOKING_COMPLETED', 'completed');
define('BOOKING_CANCELLED', 'cancelled');

// Trip statuses
define('TRIP_SCHEDULED', 'scheduled');
define('TRIP_BOARDING', 'boarding');
define('TRIP_IN_PROGRESS', 'in_progress');
define('TRIP_COMPLETED', 'completed');
define('TRIP_CANCELLED', 'cancelled');

// Autoload core files
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/helpers.php';
require_once APP_ROOT . '/includes/csrf.php';
require_once APP_ROOT . '/includes/session.php';
require_once APP_ROOT . '/includes/auth.php';

/**
 * Autoload classes from /classes and /models directories.
 *
 * @param string $class Class name
 * @return void
 */
spl_autoload_register(function (string $class): void {
    $paths = [
        APP_ROOT . '/classes/' . $class . '.php',
        APP_ROOT . '/models/' . $class . '.php',
        APP_ROOT . '/controllers/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});
