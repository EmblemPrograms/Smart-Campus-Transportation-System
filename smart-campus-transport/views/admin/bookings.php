<?php
/**
 * Admin Bookings Management
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
requireRole('admin');

AdminController::handle('bookings');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qs = !empty($_GET['status']) ? '?status=' . urlencode((string) $_GET['status']) : '';
    redirect(url('views/admin/bookings.php' . $qs));
}

$pageTitle = 'Bookings';
$pageHeading = 'Bookings';
$status = clean($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$result = Booking::all($status !== '' ? $status : null, $page);
$bookings = $result['data'];
$pagination = $result['pagination'];

require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/layouts/sidebar.php';
require dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="card scts-card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>All Bookings</span>
        <form method="get" class="d-flex gap-2">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All statuses</option>
                <?php foreach (['pending', 'approved', 'completed', 'cancelled'] as $st): ?>
                    <option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-scts align-middle">
                <thead>
                    <tr>
                        <th>Booking #</th>
                        <th>User</th>
                        <th>Route</th>
                        <th>Date</th>
                        <th>Seat</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($bookings)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No bookings found.</td></tr>
                <?php else: ?>
                    <?php foreach ($bookings as $b): ?>
                        <tr data-booking-id="<?= (int) $b['id'] ?>">
                            <td class="fw-semibold"><?= e($b['booking_number']) ?></td>
                            <td><?= e($b['user_name'] ?? '') ?></td>
                            <td><?= e($b['route_name'] ?? '') ?></td>
                            <td><?= e(formatDate($b['booking_date'] ?? null)) ?> <?= e(substr((string) ($b['departure_time'] ?? ''), 0, 5)) ?></td>
                            <td><?= e($b['seat_number'] ?? '') ?></td>
                            <td class="booking-status"><?= statusBadge($b['status'] ?? '') ?></td>
                            <td class="text-end text-nowrap">
                                <?php if (($b['status'] ?? '') === 'pending'): ?>
                                    <button class="btn btn-sm btn-success btn-approve" data-id="<?= (int) $b['id'] ?>">Approve</button>
                                    <button class="btn btn-sm btn-outline-danger btn-cancel" data-id="<?= (int) $b['id'] ?>">Cancel</button>
                                <?php elseif (($b['status'] ?? '') === 'approved'): ?>
                                    <form method="post" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="complete">
                                        <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                        <button class="btn btn-sm btn-primary" type="submit">Complete</button>
                                    </form>
                                    <button class="btn btn-sm btn-outline-danger btn-cancel" data-id="<?= (int) $b['id'] ?>">Cancel</button>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
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
                            <a class="page-link" href="?status=<?= e(urlencode($status)) ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php
$extraJs = <<<'JS'
<script>
$(function () {
    function act(action, id) {
        if (action === 'cancel' && !confirm('Cancel this booking?')) return;
        SCTS.post(SCTS.ajax.bookingActions, { action: action, id: id })
            .done(function (res) {
                if (res && res.success) {
                    SCTS.toast(res.message || 'Done', 'success');
                    setTimeout(function () { location.reload(); }, 600);
                } else {
                    SCTS.toast((res && res.message) || 'Action failed', 'error');
                }
            })
            .fail(function () { SCTS.toast('Request failed', 'error'); });
    }
    $(document).on('click', '.btn-approve', function () { act('approve', $(this).data('id')); });
    $(document).on('click', '.btn-cancel', function () { act('cancel', $(this).data('id')); });
});
</script>
JS;
require dirname(__DIR__) . '/layouts/footer.php';
?>
