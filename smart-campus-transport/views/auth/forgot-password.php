<?php
/**
 * Forgot Password Page
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';

startAppSession();
attemptRememberLogin();

if (isLoggedIn()) {
    redirect(dashboardUrl());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new AuthController();
    $auth->forgotPassword();
}

$flashMessages = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — <?= e(APP_NAME) ?></title>
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
                        <i class="bi bi-key-fill"></i>
                    </div>
                    <p class="institution mb-1"><?= e(APP_INSTITUTION) ?></p>
                    <h1 class="brand-title h4 mb-1">Forgot Password</h1>
                    <p class="text-muted small mb-0">Enter your email and we'll send a reset link</p>
                </div>

                <?php foreach ($flashMessages as $msg): ?>
                    <div class="alert alert-<?= e($msg['type'] === 'error' ? 'danger' : $msg['type']) ?> alert-dismissible fade show" role="alert">
                        <?= e($msg['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>

                <form method="POST" action="" id="forgotForm" class="auth-form" novalidate>
                    <?= csrfField() ?>

                    <div class="mb-4">
                        <label for="email" class="form-label">Email address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="you@fedpolyede.edu.ng" required autocomplete="email" autofocus>
                        </div>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>

                    <button type="submit" class="btn btn-auth w-100 py-2 mb-3" id="submitBtn">
                        <span class="btn-text">Send Reset Link</span>
                        <span class="btn-spinner spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </form>

                <p class="text-center mb-0 text-muted small">
                    Remember your password?
                    <a href="<?= e(url('views/auth/login.php')) ?>" class="auth-link fw-semibold">Sign In</a>
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
