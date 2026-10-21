<?php
/**
 * Admin Schedules Management
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
requireRole('admin');

AdminController::handle('schedules');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect(url('views/admin/schedules.php'));
}

$pageTitle = 'Schedules';
$pageHeading = 'Schedules';
$schedules = Schedule::all();
$routes = Route::all('active');
$vehicles = Vehicle::all();
$drivers = Driver::all();
$days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/layouts/sidebar.php';
require dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="card scts-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Transport Schedules</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#scheduleModal" onclick="resetScheduleForm()">
            <i class="fa-solid fa-plus me-1"></i> Add Schedule
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-scts align-middle">
                <thead>
                    <tr>
                        <th>Route</th>
                        <th>Departure</th>
                        <th>Arrival</th>
                        <th>Days</th>
                        <th>Vehicle</th>
                        <th>Driver</th>
                        <th>Seats</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($schedules)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No schedules found.</td></tr>
                <?php else: ?>
                    <?php foreach ($schedules as $s): ?>
                        <tr>
                            <td><?= e($s['route_name'] ?? '') ?></td>
                            <td><?= e(substr((string) ($s['departure_time'] ?? ''), 0, 5)) ?></td>
                            <td><?= e(substr((string) ($s['arrival_time'] ?? ''), 0, 5)) ?></td>
                            <td class="small"><?= e($s['days_of_week'] ?? '') ?></td>
                            <td><?= e($s['plate_number'] ?? '—') ?></td>
                            <td><?= e($s['driver_name'] ?? '—') ?></td>
                            <td><?= (int) ($s['available_seats'] ?? 0) ?></td>
                            <td><?= statusBadge($s['status'] ?? '') ?></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" type="button"
                                    onclick='editSchedule(<?= json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>)'>
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form method="post" class="d-inline" data-confirm="Delete this schedule?">
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
    </div>
</div>

<div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content" id="scheduleForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" id="scheduleAction" value="create">
            <input type="hidden" name="id" id="scheduleId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleModalTitle">Add Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Route</label>
                        <select name="route_id" id="sch_route" class="form-select" required>
                            <?php foreach ($routes as $r): ?>
                                <option value="<?= (int) $r['id'] ?>"><?= e($r['name']) ?> (<?= e($r['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Vehicle</label>
                        <select name="vehicle_id" id="sch_vehicle" class="form-select">
                            <option value="">— Select —</option>
                            <?php foreach ($vehicles as $v): ?>
                                <option value="<?= (int) $v['id'] ?>"><?= e($v['plate_number']) ?> (<?= (int) $v['capacity'] ?> seats)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Driver</label>
                        <select name="driver_id" id="sch_driver" class="form-select">
                            <option value="">— Select —</option>
                            <?php foreach ($drivers as $d): ?>
                                <option value="<?= (int) $d['id'] ?>"><?= e($d['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Departure</label>
                        <input type="time" name="departure_time" id="sch_dep" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Arrival</label>
                        <input type="time" name="arrival_time" id="sch_arr" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Available Seats</label>
                        <input type="number" name="available_seats" id="sch_seats" class="form-control" min="1" value="14" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label d-block">Days of Week</label>
                        <?php foreach ($days as $day): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input day-check" type="checkbox" name="days_of_week[]" value="<?= $day ?>" id="day_<?= $day ?>">
                                <label class="form-check-label" for="day_<?= $day ?>"><?= $day ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" id="sch_status" class="form-select">
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
function resetScheduleForm() {
    document.getElementById('scheduleForm').reset();
    document.getElementById('scheduleAction').value = 'create';
    document.getElementById('scheduleId').value = '';
    document.getElementById('scheduleModalTitle').textContent = 'Add Schedule';
    document.querySelectorAll('.day-check').forEach(function (c) { c.checked = false; });
}
function editSchedule(s) {
    resetScheduleForm();
    document.getElementById('scheduleAction').value = 'update';
    document.getElementById('scheduleId').value = s.id;
    document.getElementById('scheduleModalTitle').textContent = 'Edit Schedule';
    document.getElementById('sch_route').value = s.route_id;
    document.getElementById('sch_vehicle').value = s.vehicle_id || '';
    document.getElementById('sch_driver').value = s.driver_id || '';
    document.getElementById('sch_dep').value = (s.departure_time || '').substring(0, 5);
    document.getElementById('sch_arr').value = (s.arrival_time || '').substring(0, 5);
    document.getElementById('sch_seats').value = s.available_seats || 14;
    document.getElementById('sch_status').value = s.status || 'active';
    var days = (s.days_of_week || '').split(',');
    document.querySelectorAll('.day-check').forEach(function (c) {
        c.checked = days.indexOf(c.value) !== -1;
    });
    new bootstrap.Modal(document.getElementById('scheduleModal')).show();
}
</script>
JS;
require dirname(__DIR__) . '/layouts/footer.php';
?>
