<?php
/**
 * Registration Page
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
    $auth->register();
}

$flashMessages = getFlash();
$old = [
    'full_name' => e($_POST['full_name'] ?? ''),
    'email' => e($_POST['email'] ?? ''),
    'phone' => e($_POST['phone'] ?? ''),
    'role' => e($_POST['role'] ?? 'student'),
    'matric_number' => e($_POST['matric_number'] ?? ''),
    'staff_id' => e($_POST['staff_id'] ?? ''),
    'department' => e($_POST['department'] ?? ''),
    'level' => e($_POST['level'] ?? ''),
    'faculty' => e($_POST['faculty'] ?? ''),
    'position' => e($_POST['position'] ?? ''),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/auth.css')) ?>" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-overlay"></div>

    <main class="auth-wrapper auth-wrapper-wide">
        <div class="auth-card card border-0 shadow-lg">
            <div class="card-body p-4 p-md-5">
                <div class="auth-brand text-center mb-4">
                    <div class="brand-icon mb-3">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <p class="institution mb-1"><?= e(APP_INSTITUTION) ?></p>
                    <h1 class="brand-title h4 mb-1">Create Account</h1>
                    <p class="text-muted small mb-0">Register as a student or staff member</p>
                </div>

                <?php foreach ($flashMessages as $msg): ?>
                    <div class="alert alert-<?= e($msg['type'] === 'error' ? 'danger' : $msg['type']) ?> alert-dismissible fade show" role="alert">
                        <?= e($msg['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>

                <form method="POST" action="" id="registerForm" class="auth-form" novalidate>
                    <?= csrfField() ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                   value="<?= $old['full_name'] ?>" placeholder="As it appears on your ID"
                                   required minlength="3" autocomplete="name">
                            <div class="invalid-feedback">Full name must be at least 3 characters.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= $old['email'] ?>" placeholder="you@fedpolyede.edu.ng"
                                   required autocomplete="email">
                            <div class="invalid-feedback">Please enter a valid email.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   value="<?= $old['phone'] ?>" placeholder="08012345678"
                                   required autocomplete="tel" pattern="[0-9+\-\s()]{7,20}">
                            <div class="invalid-feedback">Please enter a valid phone number.</div>
                        </div>

                        <div class="col-12">
                            <label for="role" class="form-label">I am a</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="student" <?= $old['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                                <option value="staff" <?= $old['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                            </select>
                            <div class="form-text">Drivers and administrators are created by the system admin.</div>
                        </div>

                        <!-- Student fields -->
                        <div class="col-md-6 role-field role-student">
                            <label for="matric_number" class="form-label">Matric Number</label>
                            <input type="text" class="form-control" id="matric_number" name="matric_number"
                                   value="<?= $old['matric_number'] ?>" placeholder="e.g. FPE/2024/ND/001">
                            <div class="invalid-feedback">Matriculation number is required.</div>
                        </div>

                        <div class="col-md-6 role-field role-student">
                            <label for="level" class="form-label">Level</label>
                            <select class="form-select" id="level" name="level">
                                <option value="">Select level</option>
                                <?php foreach (['ND1', 'ND2', 'HND1', 'HND2'] as $lvl): ?>
                                    <option value="<?= e($lvl) ?>" <?= $old['level'] === $lvl ? 'selected' : '' ?>><?= e($lvl) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select your level.</div>
                        </div>

                        <div class="col-md-6 role-field role-student">
                            <label for="faculty" class="form-label">Faculty</label>
                            <input type="text" class="form-control" id="faculty" name="faculty"
                                   value="<?= $old['faculty'] ?>" placeholder="e.g. School of Engineering">
                            <div class="invalid-feedback">Faculty is required.</div>
                        </div>

                        <!-- Staff fields -->
                        <div class="col-md-6 role-field role-staff d-none">
                            <label for="staff_id" class="form-label">Staff ID</label>
                            <input type="text" class="form-control" id="staff_id" name="staff_id"
                                   value="<?= $old['staff_id'] ?>" placeholder="e.g. STAFF/2020/045">
                            <div class="invalid-feedback">Staff ID is required.</div>
                        </div>

                        <div class="col-md-6 role-field role-staff d-none">
                            <label for="position" class="form-label">Position</label>
                            <input type="text" class="form-control" id="position" name="position"
                                   value="<?= $old['position'] ?>" placeholder="e.g. Lecturer II">
                            <div class="invalid-feedback">Position is required.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department"
                                   value="<?= $old['department'] ?>" placeholder="e.g. Computer Science" required>
                            <div class="invalid-feedback">Department is required.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password"
                                       required minlength="<?= (int) PASSWORD_MIN_LENGTH ?>"
                                       autocomplete="new-password" placeholder="Create a strong password">
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

                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                       required minlength="<?= (int) PASSWORD_MIN_LENGTH ?>"
                                       autocomplete="new-password" placeholder="Re-enter password">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="confirm_password" tabindex="-1" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Passwords must match.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-auth w-100 py-2 mt-4 mb-3" id="submitBtn">
                        <span class="btn-text">Create Account</span>
                        <span class="btn-spinner spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </form>

                <p class="text-center mb-0 text-muted small">
                    Already have an account?
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
