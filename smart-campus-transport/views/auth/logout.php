<?php
/**
 * Logout Page
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Processes logout and redirects to the login page.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';

startAppSession();
attemptRememberLogin();

$auth = new AuthController();
$auth->logout();
