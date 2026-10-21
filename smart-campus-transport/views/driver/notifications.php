<?php
/**
 * Driver Notifications
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
attemptRememberLogin();
requireRole('driver');

$userId = (int) currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = clean($_POST['action'] ?? '');
    if ($action === 'mark_read') {
        Notification::markRead((int) ($_POST['notification_id'] ?? 0), $userId);
        flash('success', 'Notification marked as read.');
    } elseif ($action === 'mark_all') {
        Notification::markAllRead($userId);
        flash('success', 'All notifications marked as read.');
    } elseif ($action === 'delete') {
        Notification::delete((int) ($_POST['notification_id'] ?? 0), $userId);
        flash('success', 'Notification deleted.');
    }
    redirect(url('views/driver/notifications.php'));
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$result = Notification::getForUser($userId, $page);
$notifications = $result['data'];
$pagination = $result['pagination'];
$unread = (int) $result['unread_count'];

$pageTitle = 'Notifications';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/navbar.php';
?>

<div>
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
      <h1 class="h3 mb-1">Notifications</h1>
      <p class="text-muted mb-0"><?= $unread ?> unread</p>
    </div>
    <?php if ($unread > 0): ?>
      <form method="post">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="mark_all">
        <button type="submit" class="btn btn-outline-secondary btn-sm">Mark all as read</button>
      </form>
    <?php endif; ?>
  </div>

  <div class="card scts-card">
    <div class="list-group list-group-flush">
      <?php if (empty($notifications)): ?>
        <div class="list-group-item text-muted py-4 text-center">No notifications yet.</div>
      <?php else: ?>
        <?php foreach ($notifications as $n):
            $isRead = (int) $n['is_read'] === 1;
            $typeClass = [
                'success' => 'border-success',
                'warning' => 'border-warning',
                'danger' => 'border-danger',
                'info' => 'border-info',
            ][$n['type'] ?? 'info'] ?? 'border-info';
        ?>
          <div class="list-group-item <?= $isRead ? '' : 'bg-light' ?> border-start border-4 <?= e($typeClass) ?>">
            <div class="d-flex justify-content-between gap-3">
              <div>
                <div class="fw-semibold">
                  <?= e($n['title']) ?>
                  <?php if (!$isRead): ?><span class="badge bg-primary">New</span><?php endif; ?>
                </div>
                <div class="text-muted"><?= e($n['message']) ?></div>
                <div class="small text-muted mt-1"><?= e(timeAgo($n['created_at'])) ?></div>
                <?php if (!empty($n['link'])): ?>
                  <a href="<?= e($n['link']) ?>" class="small">Open related page</a>
                <?php endif; ?>
              </div>
              <div class="text-nowrap">
                <?php if (!$isRead): ?>
                  <form method="post" class="d-inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="mark_read">
                    <input type="hidden" name="notification_id" value="<?= (int) $n['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Mark read</button>
                  </form>
                <?php endif; ?>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete this notification?');">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="notification_id" value="<?= (int) $n['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($pagination['total_pages'] > 1): ?>
    <nav class="mt-3">
      <ul class="pagination">
        <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
          <li class="page-item <?= $p === $pagination['current_page'] ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
  <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
