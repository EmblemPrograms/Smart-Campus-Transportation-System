<?php
/**
 * Admin Staff Management
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
requireRole('admin');

AdminController::handle('staff');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect(url('views/admin/staff.php'));
}

$pageTitle = 'Staff';
$pageHeading = 'Staff';
$q = clean($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$result = Staff::search($q, $page);
$staffList = $result['data'];
$pagination = $result['pagination'];

require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/layouts/sidebar.php';
require dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="card scts-card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Manage Staff</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#staffModal" onclick="resetStaffForm()">
            <i class="fa-solid fa-plus me-1"></i> Add Staff
        </button>
    </div>
    <div class="card-body">
        <div class="table-toolbar">
            <form method="get" class="d-flex gap-2">
                <input type="search" name="q" id="tableSearch" class="form-control form-control-sm" placeholder="Search staff..." value="<?= e($q) ?>">
                <button class="btn btn-sm btn-outline-primary">Search</button>
            </form>
            <span class="small text-muted"><?= (int) $pagination['total'] ?> record(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-scts table-filterable align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Staff ID</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($staffList)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No staff found.</td></tr>
                <?php else: ?>
                    <?php foreach ($staffList as $s): ?>
                        <tr>
                            <td><?= (int) $s['id'] ?></td>
                            <td><?= e($s['full_name'] ?? '') ?></td>
                            <td><?= e($s['staff_id'] ?? '') ?></td>
                            <td><?= e($s['department'] ?? '') ?></td>
                            <td><?= e($s['position'] ?? '') ?></td>
                            <td><?= (int) ($s['priority_booking'] ?? 0) ? 'Yes' : 'No' ?></td>
                            <td><?= statusBadge($s['user_status'] ?? 'inactive') ?></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" type="button"
                                    onclick='editStaff(<?= json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>)'>
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form method="post" class="d-inline" data-confirm="Toggle staff status?">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="fa-solid fa-toggle-on"></i></button>
                                </form>
                                <form method="post" class="d-inline" data-confirm="Delete this staff member?">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-solid fa-trash"></i></button>
                                </form>
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
                            <a class="page-link" href="?q=<?= e(urlencode($q)) ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="staffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content" id="staffForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" id="staffAction" value="create">
            <input type="hidden" name="id" id="staffRowId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="staffModalTitle">Add Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="s_full_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="s_email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="s_phone" class="form-control">
                    </div>
                    <div class="col-md-6" id="s_passwordWrap">
                        <label class="form-label">Password</label>
                        <input type="text" name="password" class="form-control" value="Password1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Staff ID</label>
                        <input type="text" name="staff_id" id="s_staff_id" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" id="s_department" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Position</label>
                        <input type="text" name="position" id="s_position" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Priority Booking</label>
                        <select name="priority_booking" id="s_priority" class="form-select">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="s_status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<?php
$extraJs = <<<'JS'
<script>
function resetStaffForm() {
    document.getElementById('staffForm').reset();
    document.getElementById('staffAction').value = 'create';
    document.getElementById('staffRowId').value = '';
    document.getElementById('staffModalTitle').textContent = 'Add Staff';
    document.getElementById('s_passwordWrap').style.display = '';
}
function editStaff(s) {
    resetStaffForm();
    document.getElementById('staffAction').value = 'update';
    document.getElementById('staffRowId').value = s.id;
    document.getElementById('staffModalTitle').textContent = 'Edit Staff';
    document.getElementById('s_full_name').value = s.full_name || '';
    document.getElementById('s_email').value = s.email || '';
    document.getElementById('s_phone').value = s.phone || '';
    document.getElementById('s_staff_id').value = s.staff_id || '';
    document.getElementById('s_department').value = s.department || '';
    document.getElementById('s_position').value = s.position || '';
    document.getElementById('s_priority').value = String(s.priority_booking ?? 1);
    document.getElementById('s_status').value = s.user_status || 'active';
    document.getElementById('s_passwordWrap').style.display = 'none';
    new bootstrap.Modal(document.getElementById('staffModal')).show();
}
</script>
JS;
require dirname(__DIR__) . '/layouts/footer.php';
?>
