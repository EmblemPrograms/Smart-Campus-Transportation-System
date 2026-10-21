<?php
/**
 * Admin Activity Logs
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
requireRole('admin');

$pageTitle = 'Activity Logs';
$pageHeading = 'Activity Logs';

$q = clean($_GET['q'] ?? '');
$module = clean($_GET['module'] ?? '');
$userId = !empty($_GET['user_id']) ? (int) $_GET['user_id'] : null;
$startDate = clean($_GET['start'] ?? '');
$endDate = clean($_GET['end'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));

$result = ActivityLog::search(
    $q,
    $module !== '' ? $module : null,
    $userId,
    $startDate !== '' ? $startDate . ' 00:00:00' : null,
    $endDate !== '' ? $endDate . ' 23:59:59' : null,
    $page
);
$logs = $result['data'];
$pagination = $result['pagination'];
$users = User::all();

require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/layouts/sidebar.php';
require dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="card scts-card">
    <div class="card-header">Filter Logs</div>
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="search" name="q" class="form-control form-control-sm" value="<?= e($q) ?>" placeholder="Action, description...">
            </div>
            <div class="col-md-2">
                <label class="form-label">Module</label>
                <input type="text" name="module" class="form-control form-control-sm" value="<?= e($module) ?>" placeholder="e.g. bookings">
            </div>
            <div class="col-md-3">
                <label class="form-label">User</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">All users</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int) $u['id'] ?>" <?= $userId === (int) $u['id'] ? 'selected' : '' ?>>
                            <?= e($u['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">From</label>
                <input type="date" name="start" class="form-control form-control-sm" value="<?= e($startDate) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">To</label>
                <input type="date" name="end" class="form-control form-control-sm" value="<?= e($endDate) ?>">
            </div>
            <div class="col-12">
                <button class="btn btn-sm btn-primary" type="submit">Apply Filters</button>
                <a href="<?= e(url('views/admin/logs.php')) ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-scts align-middle">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Description</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No logs found.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="small text-nowrap"><?= e(formatDateTime($log['created_at'] ?? null)) ?></td>
                            <td>
                                <?= e($log['user_name'] ?? 'System') ?>
                                <?php if (!empty($log['user_role'])): ?>
                                    <div class="small text-muted"><?= e($log['user_role']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary"><?= e($log['action'] ?? '') ?></span></td>
                            <td><?= e($log['module'] ?? '—') ?></td>
                            <td><?= e($log['description'] ?? '') ?></td>
                            <td class="small"><?= e($log['ip_address'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
            <nav>
                <ul class="pagination pagination-sm justify-content-end mb-0">
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= e(http_build_query(array_merge($_GET, ['page' => $i]))) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
