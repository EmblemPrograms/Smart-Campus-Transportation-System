<?php
/**
 * Driver Navigation — Dijkstra route path + live location simulation
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
attemptRememberLogin();
requireRole('driver');

$userId = (int) currentUserId();
$driver = Driver::findByUserId($userId);
if (!$driver) {
    flash('error', 'Driver profile not found.');
    redirect(url('views/auth/login.php'));
}

$driverId = (int) $driver['id'];
$trackingId = (int) ($_GET['id'] ?? $_POST['tracking_id'] ?? 0);

// AJAX location update
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'POST required.'], 405);
    }
    requireCsrf();
    $tid = (int) ($_POST['tracking_id'] ?? 0);
    $locationId = (int) ($_POST['location_id'] ?? 0) ?: null;
    $lat = (float) ($_POST['latitude'] ?? 0);
    $lng = (float) ($_POST['longitude'] ?? 0);
    $result = DriverController::updateLocation($tid, $userId, $lat, $lng, $locationId);
    if ($result['success']) {
        $trip = Tracking::findById($tid);
        $result['trip'] = $trip;
    }
    jsonResponse($result, $result['success'] ? 200 : 422);
}

$trip = $trackingId > 0 ? Tracking::findById($trackingId) : Tracking::getCurrentTrip($driverId);

if (!$trip || (int) $trip['driver_id'] !== $driverId) {
    flash('warning', 'Start a trip before opening navigation.');
    redirect(url('views/driver/trips.php'));
}

if (!in_array($trip['status'], [TRIP_BOARDING, TRIP_IN_PROGRESS], true)) {
    flash('warning', 'Navigation is only available for active trips.');
    redirect(url('views/driver/trip-action.php?id=' . (int) $trip['id']));
}

$schedule = Schedule::findById((int) $trip['schedule_id']);
$route = $schedule ? Route::findById((int) $schedule['route_id']) : null;

$pathIds = $route ? Route::getPathIds($route) : [];
$pathDetails = null;

if ($route && count($pathIds) >= 2) {
    $dijkstra = new Dijkstra(true);
    $dijkstra->shortestPath((int) $route['start_location_id'], (int) $route['end_location_id']);
    $pathDetails = $dijkstra->getPathDetails();
    if (!empty($pathDetails['success'])) {
        $pathIds = $pathDetails['path'];
    }
} elseif ($route) {
    $pathIds = [(int) $route['start_location_id'], (int) $route['end_location_id']];
    $dijkstra = new Dijkstra(true);
    $result = $dijkstra->shortestPath($pathIds[0], $pathIds[1]);
    if ($result['success']) {
        $pathDetails = $dijkstra->getPathDetails();
        $pathIds = $pathDetails['path'];
    }
}

$stops = [];
foreach ($pathIds as $locId) {
    $loc = Location::findById((int) $locId);
    if ($loc) {
        $stops[] = $loc;
    }
}

$currentIndex = 0;
$currentLocId = (int) ($trip['current_location_id'] ?? 0);
foreach ($stops as $i => $stop) {
    if ((int) $stop['id'] === $currentLocId) {
        $currentIndex = $i;
        break;
    }
}

$pageTitle = 'Navigation';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/navbar.php';
?>

<style>
.nav-map {
  background:linear-gradient(160deg,#ecfdf5 0%,#f0fdfa 40%,#ffffff 100%);
  border:1px solid #99f6e4; border-radius:1rem; padding:1.5rem; min-height:220px;
}
.nav-stop {
  display:inline-flex; flex-direction:column; align-items:center; margin:0 .4rem .75rem; vertical-align:top;
}
.nav-dot {
  width:42px; height:42px; border-radius:50%; background:#99f6e4; color:#0f766e;
  display:flex; align-items:center; justify-content:center; font-weight:700;
  border:3px solid #ccfbf1; transition:.2s;
}
.nav-stop.done .nav-dot { background:#14b8a6; color:#fff; border-color:#0d9488; }
.nav-stop.current .nav-dot {
  background:#0d9488; color:#fff; border-color:#115e59;
  box-shadow:0 0 0 6px rgba(13,148,136,.25); transform:scale(1.08);
}
.nav-label { font-size:.72rem; margin-top:.35rem; max-width:88px; text-align:center; color:#0f766e; }
.nav-arrow { color:#14b8a6; font-size:1.3rem; display:inline-block; margin-top:8px; }
</style>

<div>
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
      <h1 class="h3 mb-1">Route Navigation</h1>
      <p class="text-muted mb-0">
        <?= e($trip['route_name'] ?? ($schedule['route_name'] ?? 'Route')) ?>
        · <?= statusBadge($trip['status']) ?>
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= e(url('views/driver/trip-action.php?id=' . (int) $trip['id'])) ?>" class="btn btn-outline-secondary">Trip Control</a>
      <a href="<?= e(url('views/driver/passengers.php?tracking_id=' . (int) $trip['id'])) ?>" class="btn btn-outline-secondary">Passengers</a>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card scts-card mb-4">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Dijkstra Path</h2>
            <?php if ($pathDetails && !empty($pathDetails['success'])): ?>
              <span class="badge bg-light text-dark">
                <?= e($pathDetails['distance_formatted']) ?> · <?= (int) $pathDetails['hops'] ?> hops
              </span>
            <?php endif; ?>
          </div>
          <div class="nav-map" id="navMap">
            <?php if (empty($stops)): ?>
              <p class="text-muted mb-0">No path data available for this route.</p>
            <?php else: ?>
              <?php foreach ($stops as $i => $stop):
                  $cls = $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'current' : '');
              ?>
                <span class="nav-stop <?= e($cls) ?>" data-index="<?= $i ?>" data-id="<?= (int) $stop['id'] ?>"
                      data-lat="<?= e($stop['latitude']) ?>" data-lng="<?= e($stop['longitude']) ?>"
                      data-name="<?= e($stop['name']) ?>">
                  <span class="nav-dot"><?= $i + 1 ?></span>
                  <span class="nav-label"><?= e($stop['name']) ?></span>
                </span>
                <?php if ($i < count($stops) - 1): ?><span class="nav-arrow">→</span><?php endif; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <div class="mt-3 p-3 rounded-3 bg-body-tertiary border">
            <div class="small text-muted">Current location</div>
            <strong id="currentLocLabel"><?= e($trip['current_location_name'] ?? ($stops[$currentIndex]['name'] ?? '—')) ?></strong>
            <div class="small text-muted mt-1" id="coordLabel">
              Lat <?= e((string) ($trip['latitude'] ?? '—')) ?>,
              Lng <?= e((string) ($trip['longitude'] ?? '—')) ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card scts-card">
        <div class="card-body">
          <h2 class="h5 mb-3">Simulate Movement</h2>
          <p class="small text-muted">Advance the shuttle along the Dijkstra path and broadcast live location to passengers.</p>
          <div id="navAlert" class="alert d-none"></div>
          <div class="d-grid gap-2">
            <button type="button" class="btn btn-outline-primary" id="btnPrevStop">← Previous Stop</button>
            <button type="button" class="btn btn-primary" id="btnNextStop">Next Stop →</button>
            <button type="button" class="btn btn-outline-secondary" id="btnRefreshLoc">Ping Current Location</button>
          </div>
          <hr>
          <ol class="small mb-0" id="stopList">
            <?php foreach ($stops as $i => $stop): ?>
              <li class="<?= $i === $currentIndex ? 'fw-bold' : '' ?>" data-index="<?= $i ?>">
                <?= e($stop['name']) ?>
              </li>
            <?php endforeach; ?>
          </ol>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const stops = <?= json_encode(array_map(static function ($s) {
      return [
          'id' => (int) $s['id'],
          'name' => $s['name'],
          'latitude' => (float) $s['latitude'],
          'longitude' => (float) $s['longitude'],
      ];
  }, $stops), JSON_UNESCAPED_UNICODE) ?>;
  let currentIndex = <?= (int) $currentIndex ?>;
  const trackingId = <?= (int) $trip['id'] ?>;
  const csrf = <?= json_encode(csrfToken()) ?>;
  const endpoint = <?= json_encode(url('views/driver/navigation.php?ajax=1')) ?>;

  const alertBox = document.getElementById('navAlert');

  function renderStops() {
    document.querySelectorAll('.nav-stop').forEach(el => {
      const i = Number(el.dataset.index);
      el.classList.remove('done', 'current');
      if (i < currentIndex) el.classList.add('done');
      if (i === currentIndex) el.classList.add('current');
    });
    document.querySelectorAll('#stopList li').forEach(li => {
      li.classList.toggle('fw-bold', Number(li.dataset.index) === currentIndex);
    });
    if (stops[currentIndex]) {
      document.getElementById('currentLocLabel').textContent = stops[currentIndex].name;
      document.getElementById('coordLabel').textContent =
        'Lat ' + stops[currentIndex].latitude + ', Lng ' + stops[currentIndex].longitude;
    }
  }

  async function updateLocation(index) {
    if (!stops[index]) return;
    const stop = stops[index];
    const fd = new FormData();
    fd.append('<?= e(CSRF_TOKEN_NAME) ?>', csrf);
    fd.append('tracking_id', String(trackingId));
    fd.append('location_id', String(stop.id));
    fd.append('latitude', String(stop.latitude));
    fd.append('longitude', String(stop.longitude));

    try {
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
        body: fd
      });
      const data = await res.json();
      alertBox.className = 'alert ' + (data.success ? 'alert-success' : 'alert-danger');
      alertBox.textContent = data.message || (data.success ? 'Updated' : 'Failed');
      if (data.success) {
        currentIndex = index;
        renderStops();
      }
    } catch (e) {
      alertBox.className = 'alert alert-danger';
      alertBox.textContent = 'Network error updating location.';
    }
  }

  document.getElementById('btnNextStop').addEventListener('click', function () {
    if (currentIndex >= stops.length - 1) {
      alertBox.className = 'alert alert-warning';
      alertBox.textContent = 'Already at the final stop.';
      return;
    }
    updateLocation(currentIndex + 1);
  });

  document.getElementById('btnPrevStop').addEventListener('click', function () {
    if (currentIndex <= 0) {
      alertBox.className = 'alert alert-warning';
      alertBox.textContent = 'Already at the first stop.';
      return;
    }
    updateLocation(currentIndex - 1);
  });

  document.getElementById('btnRefreshLoc').addEventListener('click', function () {
    updateLocation(currentIndex);
  });
})();
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
