<?php
/**
 * Admin System Settings
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
requireRole('admin');

AdminController::handle('settings');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect(url('views/admin/settings.php'));
}

$pageTitle = 'Settings';
$pageHeading = 'System Settings';
$settings = Setting::getAll(true);

/**
 * Get setting with fallback.
 *
 * @param string $key Setting key
 * @param string $default Default value
 * @return string
 */
function settingVal(string $key, string $default = ''): string
{
    global $settings;
    return (string) ($settings[$key] ?? $default);
}

require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/layouts/sidebar.php';
require dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="card scts-card">
    <div class="card-header">Application Settings</div>
    <div class="card-body">
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">App Name</label>
                    <input type="text" name="app_name" class="form-control" value="<?= e(settingVal('app_name', APP_NAME)) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Institution</label>
                    <input type="text" name="institution_name" class="form-control" value="<?= e(settingVal('institution_name', APP_INSTITUTION)) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Support Email</label>
                    <input type="email" name="support_email" class="form-control" value="<?= e(settingVal('support_email', MAIL_FROM)) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Support Phone</label>
                    <input type="text" name="support_phone" class="form-control" value="<?= e(settingVal('support_phone')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Booking Window (hours ahead)</label>
                    <input type="number" name="booking_window_hours" class="form-control" min="1" value="<?= e(settingVal('booking_window_hours', '48')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Max Seats Per Booking</label>
                    <input type="number" name="max_seats_per_booking" class="form-control" min="1" value="<?= e(settingVal('max_seats_per_booking', '1')) ?>">
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="auto_approve_staff" id="auto_approve_staff" value="1"
                            <?= Setting::getBool('auto_approve_staff', true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="auto_approve_staff">Auto-approve staff bookings</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="tracking_enabled" id="tracking_enabled" value="1"
                            <?= Setting::getBool('tracking_enabled', true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="tracking_enabled">Live tracking enabled</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="notification_email_enabled" id="notification_email_enabled" value="1"
                            <?= Setting::getBool('notification_email_enabled', false) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notification_email_enabled">Email notifications</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1"
                            <?= Setting::getBool('maintenance_mode', false) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="maintenance_mode">Maintenance mode</label>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
