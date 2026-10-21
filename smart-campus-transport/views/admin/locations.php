<?php
/**
 * Admin Locations Management
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
requireRole('admin');

AdminController::handle('locations');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect(url('views/admin/locations.php'));
}

$pageTitle = 'Locations';
$pageHeading = 'Campus Locations';
$locations = Location::all();
$types = ['gate', 'faculty', 'hostel', 'library', 'admin', 'sport', 'other'];

require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/layouts/sidebar.php';
require dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="card scts-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Campus Locations</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#locationModal" onclick="resetLocationForm()">
            <i class="fa-solid fa-plus me-1"></i> Add Location
        </button>
    </div>
    <div class="card-body">
        <div class="table-toolbar">
            <input type="search" id="tableSearch" class="form-control form-control-sm" style="max-width:240px" placeholder="Filter...">
        </div>
        <div class="table-responsive">
            <table class="table table-scts table-filterable align-middle">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Coordinates</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($locations)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No locations found.</td></tr>
                <?php else: ?>
                    <?php foreach ($locations as $loc): ?>
                        <tr>
                            <td><code><?= e($loc['code']) ?></code></td>
                            <td>
                                <div class="fw-semibold"><?= e($loc['name']) ?></div>
                                <div class="small text-muted"><?= e($loc['description'] ?? '') ?></div>
                            </td>
                            <td><?= e(ucfirst((string) ($loc['location_type'] ?? ''))) ?></td>
                            <td class="small"><?= e($loc['latitude']) ?>, <?= e($loc['longitude']) ?></td>
                            <td><?= statusBadge($loc['status'] ?? '') ?></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" type="button"
                                    onclick='editLocation(<?= json_encode($loc, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>)'>
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form method="post" class="d-inline" data-confirm="Delete this location?">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $loc['id'] ?>">
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

<div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content" id="locationForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" id="locationAction" value="create">
            <input type="hidden" name="id" id="locationId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="locationModalTitle">Add Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="loc_name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" id="loc_code" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="loc_desc" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Latitude</label>
                        <input type="number" step="any" name="latitude" id="loc_lat" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Longitude</label>
                        <input type="number" step="any" name="longitude" id="loc_lng" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select name="location_type" id="loc_type" class="form-select">
                            <?php foreach ($types as $t): ?>
                                <option value="<?= $t ?>"><?= ucfirst($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" id="loc_status" class="form-select">
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
function resetLocationForm() {
    document.getElementById('locationForm').reset();
    document.getElementById('locationAction').value = 'create';
    document.getElementById('locationId').value = '';
    document.getElementById('locationModalTitle').textContent = 'Add Location';
}
function editLocation(l) {
    resetLocationForm();
    document.getElementById('locationAction').value = 'update';
    document.getElementById('locationId').value = l.id;
    document.getElementById('locationModalTitle').textContent = 'Edit Location';
    document.getElementById('loc_name').value = l.name || '';
    document.getElementById('loc_code').value = l.code || '';
    document.getElementById('loc_desc').value = l.description || '';
    document.getElementById('loc_lat').value = l.latitude || '';
    document.getElementById('loc_lng').value = l.longitude || '';
    document.getElementById('loc_type').value = l.location_type || 'other';
    document.getElementById('loc_status').value = l.status || 'active';
    new bootstrap.Modal(document.getElementById('locationModal')).show();
}
</script>
JS;
require dirname(__DIR__) . '/layouts/footer.php';
?>
