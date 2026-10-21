<?php
/**
 * Admin Routes Management + Dijkstra Optimize
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
requireRole('admin');

AdminController::handle('routes');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect(url('views/admin/routes.php'));
}

$pageTitle = 'Routes';
$pageHeading = 'Routes';
$routes = Route::all();
$locations = Location::getActive();
$roads = Road::all('active');

require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/layouts/sidebar.php';
require dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="card scts-card h-100">
            <div class="card-header">Optimize with Dijkstra</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Start Location</label>
                    <select id="start_location_id" class="form-select">
                        <option value="">Select...</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= (int) $loc['id'] ?>"><?= e($loc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">End Location</label>
                    <select id="end_location_id" class="form-select">
                        <option value="">Select...</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= (int) $loc['id'] ?>"><?= e($loc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btn btn-primary w-100" id="btnOptimizeDijkstra">
                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Optimize with Dijkstra
                </button>
                <div id="pathResult" class="alert alert-success mt-3 d-none mb-0">
                    <div><strong>Path:</strong> <span id="pathNames"></span></div>
                    <div><strong>Distance:</strong> <span id="pathDistance"></span></div>
                    <div><strong>Hops:</strong> <span id="pathHops"></span></div>
                </div>
                <hr>
                <form method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="optimize_save">
                    <input type="hidden" name="use_dijkstra" value="1">
                    <div class="mb-2">
                        <label class="form-label">Route Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control" value="RT-<?= strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)) ?>" required>
                    </div>
                    <input type="hidden" name="start_location_id" id="save_start">
                    <input type="hidden" name="end_location_id" id="save_end">
                    <div class="mb-2">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100" onclick="syncSaveLocations()">
                        Save Optimized Route
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="campus-map-wrap">
            <svg id="dijkstraMapSvg" class="campus-map-canvas"></svg>
            <div class="map-legend">
                <span class="legend-node">Locations</span>
                <span class="legend-path">Shortest path</span>
            </div>
        </div>
    </div>
</div>

<div class="card scts-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>All Routes</span>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#routeModal" onclick="resetRouteForm()">
            <i class="fa-solid fa-plus me-1"></i> Manual Route
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-scts mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>From → To</th>
                        <th>Distance</th>
                        <th>ETA</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($routes)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No routes yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($routes as $rt): ?>
                        <tr>
                            <td><code><?= e($rt['code']) ?></code></td>
                            <td><?= e($rt['name']) ?></td>
                            <td><?= e($rt['start_name'] ?? '') ?> → <?= e($rt['end_name'] ?? '') ?></td>
                            <td><?= e(formatDistance((float) ($rt['total_distance'] ?? 0))) ?></td>
                            <td><?= (int) ($rt['estimated_time'] ?? 0) ?> min</td>
                            <td><?= statusBadge($rt['status'] ?? '') ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" type="button"
                                    onclick='editRoute(<?= json_encode($rt, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>)'>
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form method="post" class="d-inline" data-confirm="Delete this route?">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $rt['id'] ?>">
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

<div class="modal fade" id="routeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content" id="routeForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" id="routeAction" value="create">
            <input type="hidden" name="id" id="routeId" value="">
            <input type="hidden" name="path_json" id="path_json" value="[]">
            <div class="modal-header">
                <h5 class="modal-title" id="routeModalTitle">Add Route</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="rt_name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" id="rt_code" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Start</label>
                        <select name="start_location_id" id="rt_start" class="form-select" required>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= (int) $loc['id'] ?>"><?= e($loc['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">End</label>
                        <select name="end_location_id" id="rt_end" class="form-select" required>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= (int) $loc['id'] ?>"><?= e($loc['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total Distance (m)</label>
                        <input type="number" step="0.01" name="total_distance" id="total_distance" class="form-control" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estimated Time (min)</label>
                        <input type="number" name="estimated_time" id="estimated_time" class="form-control" value="10">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" id="rt_status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="rt_desc" class="form-control" rows="2"></textarea>
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

<script>
window.CAMPUS_LOCATIONS = <?= json_encode($locations) ?>;
window.CAMPUS_ROADS = <?= json_encode($roads) ?>;
function syncSaveLocations() {
    document.getElementById('save_start').value = document.getElementById('start_location_id').value;
    document.getElementById('save_end').value = document.getElementById('end_location_id').value;
}
function resetRouteForm() {
    document.getElementById('routeForm').reset();
    document.getElementById('routeAction').value = 'create';
    document.getElementById('routeId').value = '';
    document.getElementById('path_json').value = '[]';
    document.getElementById('routeModalTitle').textContent = 'Add Route';
}
function editRoute(r) {
    resetRouteForm();
    document.getElementById('routeAction').value = 'update';
    document.getElementById('routeId').value = r.id;
    document.getElementById('routeModalTitle').textContent = 'Edit Route';
    document.getElementById('rt_name').value = r.name || '';
    document.getElementById('rt_code').value = r.code || '';
    document.getElementById('rt_start').value = r.start_location_id;
    document.getElementById('rt_end').value = r.end_location_id;
    document.getElementById('total_distance').value = r.total_distance || 0;
    document.getElementById('estimated_time').value = r.estimated_time || 0;
    document.getElementById('rt_status').value = r.status || 'active';
    document.getElementById('rt_desc').value = r.description || '';
    document.getElementById('path_json').value = typeof r.path_json === 'string' ? r.path_json : JSON.stringify(r.path_json || []);
    new bootstrap.Modal(document.getElementById('routeModal')).show();
}
</script>

<?php
$extraJs = ['js/dijkstra-map.js'];
require dirname(__DIR__) . '/layouts/footer.php';
?>
