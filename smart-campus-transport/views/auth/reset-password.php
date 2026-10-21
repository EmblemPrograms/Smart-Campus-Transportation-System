<?php
/**
 * Reset Password Page
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';

startAppSession();
attemptRememberLogin();

if (isLoggedIn()) {
    redirect(dashboardUrl());
}

$token = clean($_GET['token'] ?? $_POST['token'] ?? '');

if ($token === '') {
    flash('error', 'Invalid or missing reset token.');
    redirect(url('views/auth/forgot-password.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new AuthController();
    $auth->resetPassword();
}

$flashMessages = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/auth.css')) ?>" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-overlay"></div>

    <main class="auth-wrapper">
        <div class="auth-card card border-0 shadow-lg">
            <div class="card-body p-4 p-md-5">
                <div class="auth-brand text-center mb-4">
                    <div class="brand-icon mb-3">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <p class="institution mb-1"><?= e(APP_INSTITUTION) ?></p>
                    <h1 class="brand-title h4 mb-1">Reset Password</h1>
                    <p class="text-muted small mb-0">Choose a new secure password</p>
                </div>

                <?php foreach ($flashMessages as $msg): ?>
                    <div class="alert alert-<?= e($msg['type'] === 'error' ? 'danger' : $msg['type']) ?> alert-dismissible fade show" role="alert">
                        <?= e($msg['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>

                <form method="POST" action="" id="resetForm" class="auth-form" novalidate>
                    <?= csrfField() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">

                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                   required minlength="<?= (int) PASSWORD_MIN_LENGTH ?>"
                                   autocomplete="new-password" placeholder="Enter new password" autofocus>
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="password" tabindex="-1" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength mt-2" id="passwordStrength">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="strength-text text-muted">Password strength</small>
                        </div>
                        <div class="invalid-feedback">Password must be at least <?= (int) PASSWORD_MIN_LENGTH ?> characters with upper, lower, and a number.</div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   required minlength="<?= (int) PASSWORD_MIN_LENGTH ?>"
                                   autocomplete="new-password" placeholder="Re-enter new password">
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="confirm_password" tabindex="-1" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Passwords must match.</div>
                    </div>

                    <button type="submit" class="btn btn-auth w-100 py-2 mb-3" id="submitBtn">
                        <span class="btn-text">Update Password</span>
                        <span class="btn-spinner spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </form>

                <p class="text-center mb-0 text-muted small">
                    <a href="<?= e(url('views/auth/login.php')) ?>" class="auth-link fw-semibold">Back to Sign In</a>
                </p>
            </div>
        </div>

        <p class="auth-footer text-center mt-4 mb-0">
            &copy; <?= date('Y') ?> <?= e(APP_INSTITUTION) ?>. All rights reserved.
        </p>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= e(asset('js/auth.js')) ?>"></script>
</body>
</html>
