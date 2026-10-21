<?php
/**
 * Admin Drivers Management
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
requireRole('admin');

AdminController::handle('drivers');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect(url('views/admin/drivers.php'));
}

$pageTitle = 'Drivers';
$pageHeading = 'Drivers';
$q = clean($_GET['q'] ?? '');
$statusFilter = clean($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$result = Driver::search($q, $statusFilter !== '' ? $statusFilter : null, $page);
$drivers = $result['data'];
$pagination = $result['pagination'];

require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/layouts/sidebar.php';
require dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="card scts-card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Manage Drivers</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#driverModal" onclick="resetDriverForm()">
            <i class="fa-solid fa-plus me-1"></i> Add Driver
        </button>
    </div>
    <div class="card-body">
        <div class="table-toolbar">
            <form method="get" class="d-flex gap-2 flex-wrap">
                <input type="search" name="q" class="form-control form-control-sm" placeholder="Search drivers..." value="<?= e($q) ?>">
                <select name="status" class="form-select form-select-sm" style="width:auto">
                    <option value="">All statuses</option>
                    <?php foreach (['available', 'on_trip', 'off_duty'] as $st): ?>
                        <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= ucwords(str_replace('_', ' ', $st)) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-outline-primary">Filter</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-scts align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>License</th>
                        <th>Expiry</th>
                        <th>Experience</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($drivers)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No drivers found.</td></tr>
                <?php else: ?>
                    <?php foreach ($drivers as $d): ?>
                        <tr>
                            <td><?= (int) $d['id'] ?></td>
                            <td>
                                <div><?= e($d['full_name'] ?? '') ?></div>
                                <div class="small text-muted"><?= e($d['email'] ?? '') ?></div>
                            </td>
                            <td><?= e($d['license_number'] ?? '') ?></td>
                            <td><?= e(formatDate($d['license_expiry'] ?? null)) ?></td>
                            <td><?= (int) ($d['experience_years'] ?? 0) ?> yrs</td>
                            <td><?= statusBadge($d['status'] ?? '') ?></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" type="button"
                                    onclick='editDriver(<?= json_encode($d, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>)'>
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">Status</button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <?php foreach (['available', 'on_trip', 'off_duty'] as $st): ?>
                                            <li>
                                                <form method="post">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="set_status">
                                                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                                    <input type="hidden" name="status" value="<?= $st ?>">
                                                    <button class="dropdown-item" type="submit"><?= ucwords(str_replace('_', ' ', $st)) ?></button>
                                                </form>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <form method="post" class="d-inline" data-confirm="Delete this driver?">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
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
                <ul class="pagination pagination-sm justify-content-end">
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                            <a class="page-link" href="?q=<?= e(urlencode($q)) ?>&status=<?= e(urlencode($statusFilter)) ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="driverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content" id="driverForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" id="driverAction" value="create">
            <input type="hidden" name="id" id="driverId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="driverModalTitle">Add Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="d_full_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="d_email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="d_phone" class="form-control">
                    </div>
                    <div class="col-md-6" id="d_passwordWrap">
                        <label class="form-label">Password</label>
                        <input type="text" name="password" class="form-control" value="Password1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">License Number</label>
                        <input type="text" name="license_number" id="d_license" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">License Expiry</label>
                        <input type="date" name="license_expiry" id="d_expiry" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Experience (years)</label>
                        <input type="number" name="experience_years" id="d_exp" class="form-control" min="0" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Driver Status</label>
                        <select name="status" id="d_status" class="form-select">
                            <option value="available">Available</option>
                            <option value="on_trip">On Trip</option>
                            <option value="off_duty">Off Duty</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Account Status</label>
                        <select name="user_status" id="d_user_status" class="form-select">
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
function resetDriverForm() {
    document.getElementById('driverForm').reset();
    document.getElementById('driverAction').value = 'create';
    document.getElementById('driverId').value = '';
    document.getElementById('driverModalTitle').textContent = 'Add Driver';
    document.getElementById('d_passwordWrap').style.display = '';
}
function editDriver(d) {
    resetDriverForm();
    document.getElementById('driverAction').value = 'update';
    document.getElementById('driverId').value = d.id;
    document.getElementById('driverModalTitle').textContent = 'Edit Driver';
    document.getElementById('d_full_name').value = d.full_name || '';
    document.getElementById('d_email').value = d.email || '';
    document.getElementById('d_phone').value = d.phone || '';
    document.getElementById('d_license').value = d.license_number || '';
    document.getElementById('d_expiry').value = d.license_expiry || '';
    document.getElementById('d_exp').value = d.experience_years || 0;
    document.getElementById('d_status').value = d.status || 'available';
    document.getElementById('d_user_status').value = d.user_status || 'active';
    document.getElementById('d_passwordWrap').style.display = 'none';
    new bootstrap.Modal(document.getElementById('driverModal')).show();
}
</script>
JS;
require dirname(__DIR__) . '/layouts/footer.php';
?>
