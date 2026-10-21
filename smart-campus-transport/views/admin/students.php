<?php
/**
 * Admin Students Management
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
requireRole('admin');

AdminController::handle('students');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect(url('views/admin/students.php' . (!empty($_GET['q']) ? '?q=' . urlencode((string) $_GET['q']) : '')));
}

$pageTitle = 'Students';
$pageHeading = 'Students';
$q = clean($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$result = Student::search($q, $page);
$students = $result['data'];
$pagination = $result['pagination'];

/**
 * Build pagination URL for students list.
 *
 * @param int $p Page number
 * @return string
 */
function studentsPageUrl(int $p): string
{
    global $q;
    $params = ['page' => $p];
    if ($q !== '') {
        $params['q'] = $q;
    }
    return url('views/admin/students.php?' . http_build_query($params));
}

require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/layouts/sidebar.php';
require dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="card scts-card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>Manage Students</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#studentModal" onclick="resetStudentForm()">
            <i class="fa-solid fa-plus me-1"></i> Add Student
        </button>
    </div>
    <div class="card-body">
        <div class="table-toolbar">
            <form method="get" class="d-flex gap-2">
                <input type="search" name="q" id="tableSearch" class="form-control form-control-sm" placeholder="Search students..." value="<?= e($q) ?>">
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
                        <th>Matric</th>
                        <th>Department</th>
                        <th>Level</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($students)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No students found.</td></tr>
                <?php else: ?>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td><?= (int) $s['id'] ?></td>
                            <td><?= e($s['full_name'] ?? '') ?></td>
                            <td><?= e($s['matric_number'] ?? '') ?></td>
                            <td><?= e($s['department'] ?? '') ?></td>
                            <td><?= e($s['level'] ?? '') ?></td>
                            <td><?= e($s['email'] ?? '') ?></td>
                            <td><?= statusBadge($s['user_status'] ?? 'inactive') ?></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" type="button"
                                    onclick='editStudent(<?= json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>)'>
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form method="post" class="d-inline" data-confirm="Toggle status for this student?">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit" title="Toggle status">
                                        <i class="fa-solid fa-toggle-on"></i>
                                    </button>
                                </form>
                                <form method="post" class="d-inline" data-confirm="Delete this student permanently?">
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
                    <li class="page-item <?= $pagination['has_prev'] ? '' : 'disabled' ?>">
                        <a class="page-link" href="<?= e(studentsPageUrl($pagination['current_page'] - 1)) ?>">Prev</a>
                    </li>
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e(studentsPageUrl($i)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $pagination['has_next'] ? '' : 'disabled' ?>">
                        <a class="page-link" href="<?= e(studentsPageUrl($pagination['current_page'] + 1)) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content" id="studentForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" id="studentAction" value="create">
            <input type="hidden" name="id" id="studentId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="studentModalTitle">Add Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="full_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="phone" class="form-control">
                    </div>
                    <div class="col-md-6" id="passwordWrap">
                        <label class="form-label">Password</label>
                        <input type="text" name="password" id="password" class="form-control" value="Password1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Matric Number</label>
                        <input type="text" name="matric_number" id="matric_number" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Faculty</label>
                        <input type="text" name="faculty" id="faculty" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" id="department" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Level</label>
                        <select name="level" id="level" class="form-select">
                            <?php foreach (['ND1', 'ND2', 'HND1', 'HND2'] as $lvl): ?>
                                <option value="<?= $lvl ?>"><?= $lvl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
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
function resetStudentForm() {
    document.getElementById('studentForm').reset();
    document.getElementById('studentAction').value = 'create';
    document.getElementById('studentId').value = '';
    document.getElementById('studentModalTitle').textContent = 'Add Student';
    document.getElementById('passwordWrap').style.display = '';
}
function editStudent(s) {
    resetStudentForm();
    document.getElementById('studentAction').value = 'update';
    document.getElementById('studentId').value = s.id;
    document.getElementById('studentModalTitle').textContent = 'Edit Student';
    document.getElementById('full_name').value = s.full_name || '';
    document.getElementById('email').value = s.email || '';
    document.getElementById('phone').value = s.phone || '';
    document.getElementById('matric_number').value = s.matric_number || '';
    document.getElementById('faculty').value = s.faculty || '';
    document.getElementById('department').value = s.department || '';
    document.getElementById('level').value = s.level || 'ND1';
    document.getElementById('status').value = s.user_status || 'active';
    document.getElementById('passwordWrap').style.display = 'none';
    new bootstrap.Modal(document.getElementById('studentModal')).show();
}
</script>
JS;
require dirname(__DIR__) . '/layouts/footer.php';
?>
