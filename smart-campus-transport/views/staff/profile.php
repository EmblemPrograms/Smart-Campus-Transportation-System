<?php
/**
 * Staff Profile
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
attemptRememberLogin();
requireRole('staff');

$userId = (int) currentUserId();
$user = User::findById($userId);
$staff = Staff::findByUserId($userId);

if (!$user) {
    flash('error', 'User profile not found.');
    redirect(url('views/auth/login.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = clean($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $fullName = clean($_POST['full_name'] ?? '');
        $email = clean($_POST['email'] ?? '');
        $phone = clean($_POST['phone'] ?? '');
        $department = clean($_POST['department'] ?? '');
        $position = clean($_POST['position'] ?? '');
        $staffIdNumber = clean($_POST['staff_id'] ?? ($staff['staff_id'] ?? ''));

        if ($fullName === '' || !isValidEmail($email)) {
            flash('error', 'Please provide a valid name and email.');
            redirect(url('views/staff/profile.php'));
        }

        $existing = User::findByEmail($email);
        if ($existing && (int) $existing['id'] !== $userId) {
            flash('error', 'Email is already in use by another account.');
            redirect(url('views/staff/profile.php'));
        }

        $ok = User::update($userId, [
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'role' => ROLE_STAFF,
            'status' => $user['status'],
            'avatar' => $user['avatar'] ?? null,
        ]);

        if ($ok && $staff) {
            Staff::update((int) $staff['id'], [
                'staff_id' => $staffIdNumber,
                'department' => $department,
                'position' => $position,
                'priority_booking' => (int) ($staff['priority_booking'] ?? 1),
            ]);
        }

        if ($ok) {
            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_email'] = $email;
            logActivity($userId, 'profile_update', 'Updated staff profile', 'profile');
            flash('success', 'Profile updated successfully.');
        } else {
            flash('error', 'Unable to update profile.');
        }
        redirect(url('views/staff/profile.php'));
    }

    if ($action === 'change_password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $newPass = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if (!verifyPassword($current, (string) $user['password'])) {
            flash('error', 'Current password is incorrect.');
            redirect(url('views/staff/profile.php'));
        }

        $check = validatePassword($newPass);
        if (!$check['valid']) {
            flash('error', $check['message']);
            redirect(url('views/staff/profile.php'));
        }

        if ($newPass !== $confirm) {
            flash('error', 'New password confirmation does not match.');
            redirect(url('views/staff/profile.php'));
        }

        if (User::changePassword($userId, $newPass)) {
            logActivity($userId, 'password_change', 'Changed account password', 'profile');
            flash('success', 'Password changed successfully.');
        } else {
            flash('error', 'Unable to change password.');
        }
        redirect(url('views/staff/profile.php'));
    }
}

$user = User::findById($userId);
$staff = Staff::findByUserId($userId);

$pageTitle = 'My Profile';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/navbar.php';
?>

<div>
  <div class="mb-4">
    <h1 class="h3 mb-1">My Profile</h1>
    <p class="text-muted mb-0">Update your staff details and password.</p>
  </div>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card scts-card">
        <div class="card-body">
          <h2 class="h5 mb-3">Profile Information</h2>
          <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_profile">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="full_name">Full Name</label>
                <input type="text" class="form-control" name="full_name" id="full_name"
                       value="<?= e($user['full_name']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control" name="email" id="email"
                       value="<?= e($user['email']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="phone">Phone</label>
                <input type="text" class="form-control" name="phone" id="phone"
                       value="<?= e($user['phone'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="staff_id">Staff ID</label>
                <input type="text" class="form-control" name="staff_id" id="staff_id"
                       value="<?= e($staff['staff_id'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="department">Department</label>
                <input type="text" class="form-control" name="department" id="department"
                       value="<?= e($staff['department'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="position">Position</label>
                <input type="text" class="form-control" name="position" id="position"
                       value="<?= e($staff['position'] ?? '') ?>">
              </div>
              <div class="col-12">
                <div class="alert alert-light border mb-0">
                  Priority booking: <strong><?= !empty($staff['priority_booking']) ? 'Enabled' : 'Disabled' ?></strong>
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Save Changes</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card scts-card">
        <div class="card-body">
          <h2 class="h5 mb-3">Change Password</h2>
          <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="change_password">
            <div class="mb-3">
              <label class="form-label" for="current_password">Current Password</label>
              <input type="password" class="form-control" name="current_password" id="current_password" required>
            </div>
            <div class="mb-3">
              <label class="form-label" for="new_password">New Password</label>
              <input type="password" class="form-control" name="new_password" id="new_password" required>
              <div class="form-text">Min <?= PASSWORD_MIN_LENGTH ?> chars, include upper, lower, and a number.</div>
            </div>
            <div class="mb-3">
              <label class="form-label" for="confirm_password">Confirm New Password</label>
              <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-outline-secondary">Update Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
