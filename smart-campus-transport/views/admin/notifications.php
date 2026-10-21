<?php
/**
 * Admin Notifications / Announcements
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
requireRole('admin');

AdminController::handle('notifications');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect(url('views/admin/notifications.php'));
}

$pageTitle = 'Notifications';
$pageHeading = 'Notifications';
$page = max(1, (int) ($_GET['page'] ?? 1));
$mine = Notification::getForUser((int) currentUserId(), $page);

// Recent system notifications (admin's own inbox + count)
$recent = $mine['data'];
$pagination = $mine['pagination'];

require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/layouts/sidebar.php';
require dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card scts-card">
            <div class="card-header">Send Announcement</div>
            <div class="card-body">
                <form method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="announce">
                    <div class="mb-3">
                        <label class="form-label">Audience</label>
                        <select name="audience" class="form-select" required>
                            <option value="all">All Users</option>
                            <option value="student">Students</option>
                            <option value="staff">Staff</option>
                            <option value="driver">Drivers</option>
                            <option value="admin">Admins</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="info">Info</option>
                            <option value="success">Success</option>
                            <option value="warning">Warning</option>
                            <option value="danger">Danger</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-paper-plane me-1"></i> Send Announcement
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card scts-card">
            <div class="card-header d-flex justify-content-between">
                <span>Your Notifications</span>
                <span class="badge bg-danger"><?= (int) $mine['unread_count'] ?> unread</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent)): ?>
                    <div class="text-muted text-center py-4">No notifications.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recent as $n): ?>
                            <li class="list-group-item <?= (int) $n['is_read'] === 0 ? 'fw-semibold' : '' ?>">
                                <div class="d-flex justify-content-between gap-2">
                                    <div>
                                        <div><?= e($n['title']) ?></div>
                                        <div class="small text-muted"><?= e($n['message']) ?></div>
                                        <div class="small text-muted"><?= e(formatDateTime($n['created_at'] ?? null)) ?></div>
                                    </div>
                                    <form method="post">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" data-confirm="Delete notification?">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
