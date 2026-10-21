<?php
/**
 * Admin Reports
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
requireRole('admin');

$pageTitle = 'Reports';
$pageHeading = 'Reports';

$type = clean($_GET['type'] ?? $_POST['type'] ?? '');
$start = clean($_GET['start'] ?? $_POST['start'] ?? date('Y-m-01'));
$end = clean($_GET['end'] ?? $_POST['end'] ?? date('Y-m-d'));
$report = null;

if ($type !== '') {
    $report = ReportController::generate($type, $start, $end);
}

$types = [
    'daily_bookings' => 'Daily Bookings',
    'weekly_bookings' => 'Weekly Bookings',
    'monthly_bookings' => 'Monthly Bookings',
    'vehicle_utilization' => 'Vehicle Utilization',
    'driver_performance' => 'Driver Performance',
    'popular_routes' => 'Popular Routes',
    'cancelled' => 'Cancelled Trips & Bookings',
];

require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/layouts/sidebar.php';
require dirname(__DIR__) . '/layouts/navbar.php';
?>

<div class="card scts-card mb-3">
    <div class="card-header">Generate Report</div>
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Report Type</label>
                <select name="type" class="form-select" required>
                    <option value="">Select type...</option>
                    <?php foreach ($types as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $type === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start" class="form-control" value="<?= e($start) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end" class="form-control" value="<?= e($end) ?>" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Generate</button>
            </div>
        </form>
    </div>
</div>

<?php if ($report): ?>
<div class="card scts-card" id="reportResults">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 no-print">
        <span><?= e($report['title']) ?></span>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="SCTS.printReport('#reportResults')">
                <i class="fa-solid fa-print me-1"></i> Print / PDF
            </button>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="SCTS.exportTableCsv('#reportTable', 'scts-report.csv')">
                <i class="fa-solid fa-file-excel me-1"></i> Excel (CSV)
            </button>
        </div>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Period: <?= e(formatDate($start)) ?> — <?= e(formatDate($end)) ?>
            <?php if (!empty($report['summary'])): ?>
                · <?= e(json_encode($report['summary'])) ?>
            <?php endif; ?>
        </p>
        <div class="table-responsive">
            <table class="table table-scts table-bordered" id="reportTable">
                <thead>
                    <tr>
                        <?php foreach ($report['columns'] as $col): ?>
                            <th><?= e($col) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($report['rows'])): ?>
                    <tr><td colspan="<?= count($report['columns']) ?>" class="text-center text-muted py-4">No data for this period.</td></tr>
                <?php else: ?>
                    <?php foreach ($report['rows'] as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?= e((string) $cell) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
