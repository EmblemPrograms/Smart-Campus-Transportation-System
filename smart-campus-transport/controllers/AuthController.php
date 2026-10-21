<?php
/**
 * Authentication Controller
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Handles registration, login, logout, password reset, and email verification.
 */

declare(strict_types=1);

class AuthController
{
    /**
     * Register a new student or staff account.
     *
     * @return void
     */
    public function register(): void
    {
        requireCsrf();

        $fullName = clean($_POST['full_name'] ?? '');
        $email = strtolower(clean($_POST['email'] ?? ''));
        $phone = clean($_POST['phone'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        $role = clean($_POST['role'] ?? '');
        $matricNumber = clean($_POST['matric_number'] ?? '');
        $staffId = clean($_POST['staff_id'] ?? '');
        $department = clean($_POST['department'] ?? '');
        $level = clean($_POST['level'] ?? '');
        $faculty = clean($_POST['faculty'] ?? '');
        $position = clean($_POST['position'] ?? '');

        $errors = $this->validateRegistration([
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'confirm_password' => $confirm,
            'role' => $role,
            'matric_number' => $matricNumber,
            'staff_id' => $staffId,
            'department' => $department,
            'level' => $level,
            'faculty' => $faculty,
            'position' => $position,
        ]);

        if (!empty($errors)) {
            foreach ($errors as $error) {
                flash('error', $error);
            }
            redirect(url('views/auth/register.php'));
        }

        if (User::findByEmail($email) !== null) {
            flash('error', 'An account with this email already exists.');
            redirect(url('views/auth/register.php'));
        }

        if ($role === ROLE_STUDENT && Student::findByMatric($matricNumber) !== null) {
            flash('error', 'This matriculation number is already registered.');
            redirect(url('views/auth/register.php'));
        }

        if ($role === ROLE_STAFF && Staff::findByStaffId($staffId) !== null) {
            flash('error', 'This staff ID is already registered.');
            redirect(url('views/auth/register.php'));
        }

        $userId = User::create([
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'role' => $role,
            'status' => 'active',
        ]);

        if ($userId === false) {
            flash('error', 'Registration failed. Please try again.');
            redirect(url('views/auth/register.php'));
        }

        $profileOk = false;
        if ($role === ROLE_STUDENT) {
            $profileOk = Student::create([
                'user_id' => $userId,
                'matric_number' => $matricNumber,
                'department' => $department,
                'level' => $level,
                'faculty' => $faculty,
            ]) !== false;
        } else {
            $profileOk = Staff::create([
                'user_id' => $userId,
                'staff_id' => $staffId,
                'department' => $department,
                'position' => $position,
                'priority_booking' => 1,
            ]) !== false;
        }

        if (!$profileOk) {
            User::delete($userId);
            flash('error', 'Registration failed while creating your profile. Please try again.');
            redirect(url('views/auth/register.php'));
        }

        $verifyToken = generateToken(32);
        $this->setVerificationToken($userId, $verifyToken);

        // When mail is disabled, auto-verify so accounts remain usable in development.
        if (!MAIL_ENABLED) {
            $this->markEmailVerified($userId);
        }

        logActivity($userId, 'register', 'New ' . $role . ' account registered: ' . $email, 'auth');
        createNotification(
            $userId,
            'Welcome to ' . APP_NAME,
            'Hello ' . $fullName . '! Your account has been created successfully. You can now book campus transport.',
            'success',
            dashboardUrl($role)
        );

        if (MAIL_ENABLED) {
            $verifyUrl = url('views/auth/login.php?verify=' . urlencode($verifyToken));
            // Mail sending would go here when SMTP is configured.
            flash('success', 'Registration successful! Please check your email to verify your account before logging in.');
        } else {
            flash('success', 'Registration successful! You can now log in with your credentials.');
        }

        redirect(url('views/auth/login.php'));
    }

    /**
     * Authenticate a user and start a session.
     *
     * @return void
     */
    public function login(): void
    {
        requireCsrf();

        if ($this->isLoginLocked()) {
            $remaining = $this->lockoutRemainingMinutes();
            flash('error', 'Too many failed login attempts. Please try again in ' . $remaining . ' minute(s).');
            redirect(url('views/auth/login.php'));
        }

        $email = strtolower(clean($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $remember = !empty($_POST['remember']);

        if ($email === '' || $password === '') {
            flash('error', 'Please enter both email and password.');
            redirect(url('views/auth/login.php'));
        }

        if (!isValidEmail($email)) {
            flash('error', 'Please enter a valid email address.');
            redirect(url('views/auth/login.php'));
        }

        $user = User::findByEmail($email);

        if ($user === null || !verifyPassword($password, (string) $user['password'])) {
            $this->recordFailedLogin();
            $attempts = (int) ($_SESSION['login_attempts'] ?? 0);
            $remaining = max(0, MAX_LOGIN_ATTEMPTS - $attempts);
            if ($remaining > 0) {
                flash('error', 'Invalid email or password. ' . $remaining . ' attempt(s) remaining.');
            } else {
                flash('error', 'Too many failed login attempts. Your account login is locked for ' . LOGIN_LOCKOUT_MINUTES . ' minutes.');
            }
            redirect(url('views/auth/login.php'));
        }

        if (($user['status'] ?? '') !== 'active') {
            flash('error', 'Your account is ' . ($user['status'] ?? 'inactive') . '. Please contact the administrator.');
            redirect(url('views/auth/login.php'));
        }

        $this->clearLoginAttempts();
        loginUser($user);
        User::touchLastLogin((int) $user['id']);

        if ($remember) {
            $rawToken = generateToken(32);
            $hashed = hash('sha256', $rawToken);
            $this->setRememberToken((int) $user['id'], $hashed);
            setRememberCookie((int) $user['id'], $rawToken);
        } else {
            $this->setRememberToken((int) $user['id'], null);
            clearRememberCookie();
        }

        logActivity((int) $user['id'], 'login', 'User logged in: ' . $email, 'auth');
        flash('success', 'Welcome back, ' . $user['full_name'] . '!');
        redirect(dashboardUrl($user['role']));
    }

    /**
     * Log out the current user.
     *
     * @return void
     */
    public function logout(): void
    {
        // logoutUser() already logs activity and clears remember token/cookie.
        logoutUser();
        flash('success', 'You have been logged out successfully.');
        redirect(url('views/auth/login.php'));
    }

    /**
     * Request a password reset link.
     *
     * @return void
     */
    public function forgotPassword(): void
    {
        requireCsrf();

        $email = strtolower(clean($_POST['email'] ?? ''));

        if ($email === '' || !isValidEmail($email)) {
            flash('error', 'Please enter a valid email address.');
            redirect(url('views/auth/forgot-password.php'));
        }

        $user = User::findByEmail($email);

        // Always show a generic success message to avoid email enumeration.
        $genericMessage = 'If an account exists with that email, a password reset link has been sent.';

        if ($user === null || ($user['status'] ?? '') !== 'active') {
            flash('success', $genericMessage);
            redirect(url('views/auth/forgot-password.php'));
        }

        $rawToken = generateToken(32);
        $hashed = hash('sha256', $rawToken);
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        if (!$this->setResetToken((int) $user['id'], $hashed, $expires)) {
            flash('error', 'Unable to process your request. Please try again.');
            redirect(url('views/auth/forgot-password.php'));
        }

        $resetUrl = url('views/auth/reset-password.php?token=' . urlencode($rawToken));

        if (MAIL_ENABLED) {
            // Mail sending would go here when SMTP is configured.
            flash('success', $genericMessage);
        } elseif (APP_DEBUG) {
            flash('success', 'Password reset link (development mode): ' . $resetUrl);
        } else {
            flash('success', $genericMessage);
        }

        logActivity((int) $user['id'], 'forgot_password', 'Password reset requested for ' . $email, 'auth');
        redirect(url('views/auth/forgot-password.php'));
    }

    /**
     * Reset password using a valid token.
     *
     * @return void
     */
    public function resetPassword(): void
    {
        requireCsrf();

        $token = clean($_POST['token'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if ($token === '') {
            flash('error', 'Invalid or missing reset token.');
            redirect(url('views/auth/forgot-password.php'));
        }

        $passwordCheck = validatePassword($password);
        if (!$passwordCheck['valid']) {
            flash('error', $passwordCheck['message']);
            redirect(url('views/auth/reset-password.php?token=' . urlencode($token)));
        }

        if ($password !== $confirm) {
            flash('error', 'Password confirmation does not match.');
            redirect(url('views/auth/reset-password.php?token=' . urlencode($token)));
        }

        $user = $this->findByResetToken($token);
        if ($user === null) {
            flash('error', 'This password reset link is invalid or has expired.');
            redirect(url('views/auth/forgot-password.php'));
        }

        if (!User::changePassword((int) $user['id'], $password)) {
            flash('error', 'Unable to update your password. Please try again.');
            redirect(url('views/auth/reset-password.php?token=' . urlencode($token)));
        }

        $this->clearResetToken((int) $user['id']);
        $this->setRememberToken((int) $user['id'], null);

        logActivity((int) $user['id'], 'reset_password', 'Password reset completed for ' . $user['email'], 'auth');
        createNotification(
            (int) $user['id'],
            'Password Changed',
            'Your password was reset successfully. If you did not request this, contact support immediately.',
            'warning',
            url('views/auth/login.php')
        );

        flash('success', 'Your password has been reset. You can now log in.');
        redirect(url('views/auth/login.php'));
    }

    /**
     * Verify email address using a verification token.
     *
     * @param string|null $token Verification token from query string
     * @return void
     */
    public function verifyEmail(?string $token = null): void
    {
        $token = clean($token ?? ($_GET['token'] ?? $_GET['verify'] ?? ''));

        if ($token === '') {
            flash('error', 'Invalid verification link.');
            redirect(url('views/auth/login.php'));
        }

        $user = $this->findByVerificationToken($token);
        if ($user === null) {
            flash('error', 'This verification link is invalid or has already been used.');
            redirect(url('views/auth/login.php'));
        }

        if (!empty($user['email_verified_at'])) {
            flash('info', 'Your email is already verified. You can log in.');
            redirect(url('views/auth/login.php'));
        }

        if (!$this->markEmailVerified((int) $user['id'])) {
            flash('error', 'Unable to verify your email. Please try again.');
            redirect(url('views/auth/login.php'));
        }

        logActivity((int) $user['id'], 'verify_email', 'Email verified for ' . $user['email'], 'auth');
        createNotification(
            (int) $user['id'],
            'Email Verified',
            'Your email address has been verified successfully.',
            'success',
            dashboardUrl($user['role'])
        );

        flash('success', 'Email verified successfully! You can now log in.');
        redirect(url('views/auth/login.php'));
    }

    /**
     * Validate registration form input.
     *
     * @param array $data Form data
     * @return array List of error messages
     */
    private function validateRegistration(array $data): array
    {
        $errors = [];

        if ($data['full_name'] === '' || strlen($data['full_name']) < 3) {
            $errors[] = 'Full name must be at least 3 characters.';
        }

        if ($data['email'] === '' || !isValidEmail($data['email'])) {
            $errors[] = 'A valid email address is required.';
        }

        if ($data['phone'] === '' || !preg_match('/^[0-9+\-\s()]{7,20}$/', $data['phone'])) {
            $errors[] = 'Please enter a valid phone number.';
        }

        $passwordCheck = validatePassword($data['password']);
        if (!$passwordCheck['valid']) {
            $errors[] = $passwordCheck['message'];
        }

        if ($data['password'] !== $data['confirm_password']) {
            $errors[] = 'Password confirmation does not match.';
        }

        if (!in_array($data['role'], [ROLE_STUDENT, ROLE_STAFF], true)) {
            $errors[] = 'Please select a valid role (Student or Staff). Drivers and admins are created by administrators.';
        }

        if ($data['department'] === '') {
            $errors[] = 'Department is required.';
        }

        if ($data['role'] === ROLE_STUDENT) {
            if ($data['matric_number'] === '') {
                $errors[] = 'Matriculation number is required for students.';
            }
            if ($data['level'] === '') {
                $errors[] = 'Level is required for students.';
            }
            if ($data['faculty'] === '') {
                $errors[] = 'Faculty is required for students.';
            }
        }

        if ($data['role'] === ROLE_STAFF) {
            if ($data['staff_id'] === '') {
                $errors[] = 'Staff ID is required for staff members.';
            }
            if ($data['position'] === '') {
                $errors[] = 'Position is required for staff members.';
            }
        }

        return $errors;
    }

    /**
     * Check whether login is currently locked due to failed attempts.
     *
     * @return bool
     */
    private function isLoginLocked(): bool
    {
        $attempts = (int) ($_SESSION['login_attempts'] ?? 0);
        $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);

        if ($lockedUntil > 0 && time() < $lockedUntil) {
            return true;
        }

        if ($lockedUntil > 0 && time() >= $lockedUntil) {
            $this->clearLoginAttempts();
            return false;
        }

        return $attempts >= MAX_LOGIN_ATTEMPTS;
    }

    /**
     * Record a failed login attempt and lock if threshold reached.
     *
     * @return void
     */
    private function recordFailedLogin(): void
    {
        $_SESSION['login_attempts'] = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $_SESSION['login_locked_until'] = time() + (LOGIN_LOCKOUT_MINUTES * 60);
        }
    }

    /**
     * Clear login attempt counters.
     *
     * @return void
     */
    private function clearLoginAttempts(): void
    {
        unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
    }

    /**
     * Minutes remaining on the current lockout.
     *
     * @return int
     */
    private function lockoutRemainingMinutes(): int
    {
        $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);
        if ($lockedUntil <= time()) {
            return 0;
        }
        return (int) max(1, ceil(($lockedUntil - time()) / 60));
    }

    /**
     * Persist a verification token for a user.
     *
     * @param int $userId User ID
     * @param string $token Raw verification token
     * @return bool
     */
    private function setVerificationToken(int $userId, string $token): bool
    {
        try {
            $db = getDB();
            $hashed = hash('sha256', $token);
            $stmt = $db->prepare(
                'UPDATE users SET verification_token = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->bind_param('si', $hashed, $userId);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('AuthController::setVerificationToken failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark a user's email as verified and clear the token.
     *
     * @param int $userId User ID
     * @return bool
     */
    private function markEmailVerified(int $userId): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'UPDATE users
                 SET email_verified_at = NOW(), verification_token = NULL, updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->bind_param('i', $userId);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('AuthController::markEmailVerified failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find a user by raw verification token.
     *
     * @param string $token Raw token
     * @return array|null
     */
    private function findByVerificationToken(string $token): ?array
    {
        try {
            $db = getDB();
            $hashed = hash('sha256', $token);
            $stmt = $db->prepare(
                'SELECT id, full_name, email, role, status, email_verified_at
                 FROM users WHERE verification_token = ? LIMIT 1'
            );
            $stmt->bind_param('s', $hashed);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('AuthController::findByVerificationToken failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Set or clear the remember-me token hash.
     *
     * @param int $userId User ID
     * @param string|null $hashedToken Hashed token or null to clear
     * @return bool
     */
    private function setRememberToken(int $userId, ?string $hashedToken): bool
    {
        try {
            $db = getDB();
            if ($hashedToken === null) {
                $stmt = $db->prepare(
                    'UPDATE users SET remember_token = NULL, updated_at = NOW() WHERE id = ?'
                );
                $stmt->bind_param('i', $userId);
            } else {
                $stmt = $db->prepare(
                    'UPDATE users SET remember_token = ?, updated_at = NOW() WHERE id = ?'
                );
                $stmt->bind_param('si', $hashedToken, $userId);
            }
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('AuthController::setRememberToken failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Store a password reset token and expiry.
     *
     * @param int $userId User ID
     * @param string $hashedToken Hashed reset token
     * @param string $expires Expiry datetime
     * @return bool
     */
    private function setResetToken(int $userId, string $hashedToken, string $expires): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'UPDATE users
                 SET reset_token = ?, reset_token_expires = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->bind_param('ssi', $hashedToken, $expires, $userId);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('AuthController::setResetToken failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear password reset token fields.
     *
     * @param int $userId User ID
     * @return bool
     */
    private function clearResetToken(int $userId): bool
    {
        try {
            $db = getDB();
            $stmt = $db->prepare(
                'UPDATE users
                 SET reset_token = NULL, reset_token_expires = NULL, updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->bind_param('i', $userId);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        } catch (Throwable $e) {
            error_log('AuthController::clearResetToken failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find an active user by raw password-reset token.
     *
     * @param string $token Raw token from URL
     * @return array|null
     */
    private function findByResetToken(string $token): ?array
    {
        try {
            $db = getDB();
            $hashed = hash('sha256', $token);
            $stmt = $db->prepare(
                'SELECT id, full_name, email, role, status, reset_token_expires
                 FROM users
                 WHERE reset_token = ? AND reset_token_expires > NOW() AND status = ?
                 LIMIT 1'
            );
            $active = 'active';
            $stmt->bind_param('ss', $hashed, $active);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('AuthController::findByResetToken failed: ' . $e->getMessage());
            return null;
        }
    }
}
