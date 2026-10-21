<?php
/**
 * CSRF Protection
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

/**
 * Generate or retrieve CSRF token.
 *
 * @return string CSRF token
 */
function csrfToken(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = generateToken(32);
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Render hidden CSRF input field.
 *
 * @return string HTML input
 */
function csrfField(): string
{
    return '<input type="hidden" name="' . e(CSRF_TOKEN_NAME) . '" value="' . e(csrfToken()) . '">';
}

/**
 * Verify CSRF token from POST/request.
 *
 * @param string|null $token Token to verify
 * @return bool True if valid
 */
function verifyCsrf(?string $token = null): bool
{
    $token = $token ?? ($_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($token) || empty($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Require valid CSRF or abort with 403.
 *
 * @return void
 */
function requireCsrf(): void
{
    if (!verifyCsrf()) {
        if (isAjax()) {
            jsonResponse(['success' => false, 'message' => 'Invalid security token. Please refresh the page.'], 403);
        }
        flash('error', 'Invalid security token. Please try again.');
        redirect($_SERVER['HTTP_REFERER'] ?? url('index.php'));
    }
}
