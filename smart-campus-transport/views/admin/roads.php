<?php
/**
 * Admin Roads Management
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
requireRole('admin');

AdminController::handle('roads');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect(url('views/admin/roads.php'));
}

$pageTitle = 'Roads';
$pageHeading = 'Roads';
$roads = Road::all();
$locations = Location::getActive();

require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/layouts/sidebar.php';
require dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="card scts-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Roads Between Locations</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#roadModal" onclick="resetRoadForm()">
            <i class="fa-solid fa-plus me-1"></i> Add Road
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
                        <th>From</th>
                        <th>To</th>
                        <th>Distance</th>
                        <th>Travel Time</th>
                        <th>Bidirectional</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($roads)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No roads found.</td></tr>
                <?php else: ?>
                    <?php foreach ($roads as $r): ?>
                        <tr>
                            <td><?= e($r['from_name'] ?? '') ?></td>
                            <td><?= e($r['to_name'] ?? '') ?></td>
                            <td><?= e(formatDistance((float) ($r['distance_metres'] ?? 0))) ?></td>
                            <td><?= (int) ($r['travel_time_minutes'] ?? 0) ?> min</td>
                            <td><?= (int) ($r['is_bidirectional'] ?? 0) ? 'Yes' : 'No' ?></td>
                            <td><?= statusBadge($r['status'] ?? '') ?></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" type="button"
                                    onclick='editRoad(<?= json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>)'>
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form method="post" class="d-inline" data-confirm="Delete this road?">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
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

<div class="modal fade" id="roadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content" id="roadForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" id="roadAction" value="create">
            <input type="hidden" name="id" id="roadId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="roadModalTitle">Add Road</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">From Location</label>
                    <select name="from_location_id" id="road_from" class="form-select" required>
                        <option value="">Select...</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= (int) $loc['id'] ?>"><?= e($loc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">To Location</label>
                    <select name="to_location_id" id="road_to" class="form-select" required>
                        <option value="">Select...</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= (int) $loc['id'] ?>"><?= e($loc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Distance (metres)</label>
                        <input type="number" step="0.01" name="distance_metres" id="road_dist" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Travel Time (minutes)</label>
                        <input type="number" name="travel_time_minutes" id="road_time" class="form-control" value="5">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" id="road_status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_bidirectional" id="road_bi" value="1" checked>
                            <label class="form-check-label" for="road_bi">Bidirectional</label>
                        </div>
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
function resetRoadForm() {
    document.getElementById('roadForm').reset();
    document.getElementById('roadAction').value = 'create';
    document.getElementById('roadId').value = '';
    document.getElementById('roadModalTitle').textContent = 'Add Road';
    document.getElementById('road_bi').checked = true;
}
function editRoad(r) {
    resetRoadForm();
    document.getElementById('roadAction').value = 'update';
    document.getElementById('roadId').value = r.id;
    document.getElementById('roadModalTitle').textContent = 'Edit Road';
    document.getElementById('road_from').value = r.from_location_id;
    document.getElementById('road_to').value = r.to_location_id;
    document.getElementById('road_dist').value = r.distance_metres;
    document.getElementById('road_time').value = r.travel_time_minutes;
    document.getElementById('road_status').value = r.status || 'active';
    document.getElementById('road_bi').checked = parseInt(r.is_bidirectional, 10) === 1;
    new bootstrap.Modal(document.getElementById('roadModal')).show();
}
</script>
JS;
require dirname(__DIR__) . '/layouts/footer.php';
?>
