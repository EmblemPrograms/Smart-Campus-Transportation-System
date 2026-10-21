<?php
/**
 * Admin Profile + Change Password
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
requireRole('admin');

AdminController::handle('profile');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hash = ($_POST['action'] ?? '') === 'change_password' ? '#password' : '';
    redirect(url('views/admin/profile.php' . $hash));
}

$pageTitle = 'Profile';
$pageHeading = 'My Profile';
$user = User::findById((int) currentUserId());

require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/layouts/sidebar.php';
require dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card scts-card">
            <div class="card-header">Profile Details</div>
            <div class="card-body">
                <form method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required
                               value="<?= e($user['full_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required
                               value="<?= e($user['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?= e($user['phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?= e(ucfirst((string) ($user['role'] ?? ''))) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Login</label>
                        <input type="text" class="form-control" value="<?= e(formatDateTime($user['last_login'] ?? null)) ?>" disabled>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6" id="password">
        <div class="card scts-card">
            <div class="card-header">Change Password</div>
            <div class="card-body">
                <form method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="<?= PASSWORD_MIN_LENGTH ?>">
                        <div class="form-text">Min <?= PASSWORD_MIN_LENGTH ?> chars, include upper, lower, and number.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
