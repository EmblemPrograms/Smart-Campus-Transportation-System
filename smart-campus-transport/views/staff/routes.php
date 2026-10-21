<?php
/**
 * Staff Campus Routes & Dijkstra Path Finder
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
attemptRememberLogin();
requireRole('staff');

$locations = Location::getActive();
$routes = Route::all('active');
$pathResult = null;
$fromId = (int) ($_GET['from'] ?? $_POST['from'] ?? 0);
$toId = (int) ($_GET['to'] ?? $_POST['to'] ?? 0);

if (($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'find_path')
    || ($fromId > 0 && $toId > 0 && isset($_GET['find']))) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireCsrf();
        $fromId = (int) ($_POST['from'] ?? 0);
        $toId = (int) ($_POST['to'] ?? 0);
    }
    $pathResult = BookingController::pathPreview($fromId, $toId);
}

$pageTitle = 'Campus Routes';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/navbar.php';
?>

<style>
.route-visual {
  background:linear-gradient(135deg,#ecfdf5,#f0fdfa); border:1px solid #99f6e4;
  border-radius:1rem; padding:1.5rem; min-height:140px;
}
.path-node {
  display:inline-flex; flex-direction:column; align-items:center; margin:0 .35rem .5rem;
  vertical-align:top;
}
.path-dot {
  width:36px; height:36px; border-radius:50%; background:#0d9488; color:#fff;
  display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.85rem;
  box-shadow:0 0 0 4px #ccfbf1;
}
.path-label { font-size:.75rem; margin-top:.35rem; max-width:90px; text-align:center; color:#0f766e; }
.path-arrow { display:inline-block; color:#14b8a6; font-size:1.25rem; margin:0 .15rem; vertical-align:top; margin-top:6px; }
</style>

<div>
  <div class="mb-4">
    <h1 class="h3 mb-1">Campus Routes</h1>
    <p class="text-muted mb-0">Browse shuttle routes and find the shortest path between any two locations.</p>
  </div>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card scts-card h-100">
        <div class="card-body">
          <h2 class="h5 mb-3">Find Shortest Path</h2>
          <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="find_path">
            <div class="mb-3">
              <label class="form-label" for="from">From</label>
              <select class="form-select" name="from" id="from" required>
                <option value="">Select location…</option>
                <?php foreach ($locations as $loc): ?>
                  <option value="<?= (int) $loc['id'] ?>" <?= $fromId === (int) $loc['id'] ? 'selected' : '' ?>>
                    <?= e($loc['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label" for="to">To</label>
              <select class="form-select" name="to" id="to" required>
                <option value="">Select location…</option>
                <?php foreach ($locations as $loc): ?>
                  <option value="<?= (int) $loc['id'] ?>" <?= $toId === (int) $loc['id'] ? 'selected' : '' ?>>
                    <?= e($loc['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Compute with Dijkstra</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card scts-card h-100">
        <div class="card-body">
          <h2 class="h5 mb-3">Path Result</h2>
          <?php if ($pathResult === null): ?>
            <div class="route-visual text-muted">Choose two locations to visualise the optimal campus path.</div>
          <?php elseif (empty($pathResult['success'])): ?>
            <div class="alert alert-warning mb-0"><?= e($pathResult['message'] ?? 'No path found.') ?></div>
          <?php else: ?>
            <div class="route-visual mb-3">
              <?php $names = $pathResult['path_names'] ?? [];
              foreach ($names as $i => $name): ?>
                <span class="path-node">
                  <span class="path-dot"><?= $i + 1 ?></span>
                  <span class="path-label"><?= e($name) ?></span>
                </span>
                <?php if ($i < count($names) - 1): ?><span class="path-arrow">→</span><?php endif; ?>
              <?php endforeach; ?>
            </div>
            <div class="row g-2">
              <div class="col-sm-4"><div class="p-3 rounded-3 bg-body-tertiary border"><div class="small text-muted">Distance</div><strong><?= e($pathResult['distance_formatted'] ?? formatDistance($pathResult['distance'] ?? 0)) ?></strong></div></div>
              <div class="col-sm-4"><div class="p-3 rounded-3 bg-body-tertiary border"><div class="small text-muted">Stops / Hops</div><strong><?= (int) ($pathResult['hops'] ?? 0) ?></strong></div></div>
              <div class="col-sm-4"><div class="p-3 rounded-3 bg-body-tertiary border"><div class="small text-muted">Nodes</div><strong><?= count($pathResult['path'] ?? []) ?></strong></div></div>
            </div>
            <?php if (!empty($pathResult['segments'])): ?>
              <div class="table-responsive mt-3">
                <table class="table table-scts table-sm align-middle mb-0">
                  <thead><tr><th>Segment</th><th>Distance</th></tr></thead>
                  <tbody>
                    <?php foreach ($pathResult['segments'] as $seg): ?>
                      <tr>
                        <td><?= e($seg['from_name']) ?> → <?= e($seg['to_name']) ?></td>
                        <td><?= e($seg['distance_formatted']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card scts-card mt-4">
    <div class="card-header">
      <h2 class="h5 mb-0">Active Campus Routes</h2>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-scts align-middle mb-0">
          <thead>
            <tr><th>Code</th><th>Name</th><th>Start</th><th>End</th><th>Distance</th><th>Est. Time</th></tr>
          </thead>
          <tbody>
            <?php if (empty($routes)): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">No routes available.</td></tr>
            <?php else: ?>
              <?php foreach ($routes as $r): ?>
                <tr>
                  <td><span class="badge bg-primary"><?= e($r['code']) ?></span></td>
                  <td><?= e($r['name']) ?></td>
                  <td><?= e($r['start_name']) ?></td>
                  <td><?= e($r['end_name']) ?></td>
                  <td><?= e(formatDistance($r['total_distance'])) ?></td>
                  <td><?= (int) $r['estimated_time'] ?> min</td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
