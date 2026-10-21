<?php
/**
 * Admin Vehicles Management
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
requireRole('admin');

AdminController::handle('vehicles');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect(url('views/admin/vehicles.php'));
}

$pageTitle = 'Vehicles';
$pageHeading = 'Vehicles';
$vehicles = Vehicle::all();
$drivers = Driver::all();

require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/layouts/sidebar.php';
require dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="card scts-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Manage Vehicles</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#vehicleModal" onclick="resetVehicleForm()">
            <i class="fa-solid fa-plus me-1"></i> Add Vehicle
        </button>
    </div>
    <div class="card-body">
        <div class="table-toolbar">
            <input type="search" id="tableSearch" class="form-control form-control-sm" style="max-width:240px" placeholder="Filter table...">
        </div>
        <div class="table-responsive">
            <table class="table table-scts table-filterable align-middle">
                <thead>
                    <tr>
                        <th>Plate</th>
                        <th>Make / Model</th>
                        <th>Capacity</th>
                        <th>Year</th>
                        <th>Driver</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($vehicles)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No vehicles found.</td></tr>
                <?php else: ?>
                    <?php foreach ($vehicles as $v): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($v['plate_number']) ?></td>
                            <td><?= e(trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? ''))) ?></td>
                            <td><?= (int) ($v['capacity'] ?? 0) ?></td>
                            <td><?= e((string) ($v['year'] ?? '')) ?></td>
                            <td><?= e($v['driver_name'] ?? 'Unassigned') ?></td>
                            <td><?= statusBadge($v['status'] ?? '') ?></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" type="button"
                                    onclick='editVehicle(<?= json_encode($v, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>)'>
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" type="button"
                                    onclick='assignDriver(<?= (int) $v['id'] ?>, <?= json_encode($v['current_driver_id']) ?>)'>
                                    <i class="fa-solid fa-user-plus"></i>
                                </button>
                                <form method="post" class="d-inline" data-confirm="Delete this vehicle?">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="vehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content" id="vehicleForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" id="vehicleAction" value="create">
            <input type="hidden" name="id" id="vehicleId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="vehicleModalTitle">Add Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Plate Number</label>
                        <input type="text" name="plate_number" id="v_plate" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Make</label>
                        <input type="text" name="make" id="v_make" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" id="v_model" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Capacity</label>
                        <input type="number" name="capacity" id="v_capacity" class="form-control" min="1" value="14" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Year</label>
                        <input type="number" name="year" id="v_year" class="form-control" value="<?= date('Y') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Color</label>
                        <input type="text" name="color" id="v_color" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="v_status" class="form-select">
                            <option value="available">Available</option>
                            <option value="in_use">In Use</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Assign Driver</label>
                        <select name="current_driver_id" id="v_driver" class="form-select">
                            <option value="">— Unassigned —</option>
                            <?php foreach ($drivers as $d): ?>
                                <option value="<?= (int) $d['id'] ?>"><?= e($d['full_name']) ?></option>
                            <?php endforeach; ?>
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

<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="assign_driver">
            <input type="hidden" name="id" id="assignVehicleId">
            <div class="modal-header">
                <h5 class="modal-title">Assign Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Driver</label>
                <select name="current_driver_id" id="assignDriverSelect" class="form-select">
                    <option value="">— Unassigned —</option>
                    <?php foreach ($drivers as $d): ?>
                        <option value="<?= (int) $d['id'] ?>"><?= e($d['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Assign</button>
            </div>
        </form>
    </div>
</div>

<?php
$extraJs = <<<'JS'
<script>
function resetVehicleForm() {
    document.getElementById('vehicleForm').reset();
    document.getElementById('vehicleAction').value = 'create';
    document.getElementById('vehicleId').value = '';
    document.getElementById('vehicleModalTitle').textContent = 'Add Vehicle';
}
function editVehicle(v) {
    resetVehicleForm();
    document.getElementById('vehicleAction').value = 'update';
    document.getElementById('vehicleId').value = v.id;
    document.getElementById('vehicleModalTitle').textContent = 'Edit Vehicle';
    document.getElementById('v_plate').value = v.plate_number || '';
    document.getElementById('v_make').value = v.make || '';
    document.getElementById('v_model').value = v.model || '';
    document.getElementById('v_capacity').value = v.capacity || 14;
    document.getElementById('v_year').value = v.year || '';
    document.getElementById('v_color').value = v.color || '';
    document.getElementById('v_status').value = v.status || 'available';
    document.getElementById('v_driver').value = v.current_driver_id || '';
    new bootstrap.Modal(document.getElementById('vehicleModal')).show();
}
function assignDriver(id, current) {
    document.getElementById('assignVehicleId').value = id;
    document.getElementById('assignDriverSelect').value = current || '';
    new bootstrap.Modal(document.getElementById('assignModal')).show();
}
</script>
JS;
require dirname(__DIR__) . '/layouts/footer.php';
?>
