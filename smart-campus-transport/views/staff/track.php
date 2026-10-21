<?php
/**
 * Staff Live Shuttle Tracking
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/app.php';
startAppSession();
attemptRememberLogin();
requireRole('staff');

$userId = (int) currentUserId();
$refresh = (int) TRACKING_REFRESH_INTERVAL;

if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $activeTrips = Tracking::getActiveTrips();
    $myBookings = Booking::getByUser($userId);
    $assigned = null;

    foreach ($myBookings as $b) {
        if (!in_array($b['status'], [BOOKING_PENDING, BOOKING_APPROVED], true)) {
            continue;
        }
        if ((string) $b['booking_date'] !== date('Y-m-d')) {
            continue;
        }
        foreach ($activeTrips as $t) {
            if ((int) $t['schedule_id'] === (int) $b['schedule_id']) {
                $assigned = [
                    'booking' => [
                        'booking_number' => $b['booking_number'],
                        'seat_number' => $b['seat_number'],
                        'route_name' => $b['route_name'],
                        'pickup_name' => $b['pickup_name'],
                        'destination_name' => $b['destination_name'],
                        'is_priority' => (int) ($b['is_priority'] ?? 0),
                    ],
                    'trip' => $t,
                ];
                break 2;
            }
        }
    }

    jsonResponse([
        'success' => true,
        'trips' => $activeTrips,
        'assigned' => $assigned,
        'server_time' => date('Y-m-d H:i:s'),
    ]);
}

$pageTitle = 'Track Shuttle';
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/navbar.php';
?>

<div>
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
      <h1 class="h3 mb-1">Live Shuttle Tracking</h1>
      <p class="text-muted mb-0">Auto-refreshes every <?= $refresh ?> seconds.</p>
    </div>
    <div>
      <span class="badge bg-success" id="liveBadge">Live</span>
      <span class="small text-muted ms-2" id="lastUpdated">—</span>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card scts-card h-100">
        <div class="card-body">
          <h2 class="h5 mb-3">Your Assigned Trip</h2>
          <div id="assignedTrip">
            <p class="text-muted mb-0">Looking for an active trip linked to your booking…</p>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="card scts-card">
        <div class="card-header">
          <h2 class="h5 mb-0">Active Campus Shuttles</h2>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-scts align-middle mb-0">
              <thead>
                <tr><th>Route</th><th>Vehicle</th><th>Driver</th><th>Location</th><th>Status</th></tr>
              </thead>
              <tbody id="tripsBody">
                <tr><td colspan="5" class="text-center text-muted py-4">Loading…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const refreshMs = <?= $refresh * 1000 ?>;
  const endpoint = <?= json_encode(url('views/staff/track.php?ajax=1')) ?>;

  function escapeHtml(str) {
    return String(str == null ? '' : str).replace(/[&<>"']/g, m => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[m]));
  }

  function statusBadge(status) {
    const map = { boarding:'bg-warning text-dark', in_progress:'bg-primary', scheduled:'bg-info text-dark', completed:'bg-success', cancelled:'bg-danger' };
    const cls = map[status] || 'bg-secondary';
    return '<span class="badge ' + cls + '">' + escapeHtml(String(status || '').replace(/_/g, ' ')) + '</span>';
  }

  async function poll() {
    try {
      const res = await fetch(endpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await res.json();
      document.getElementById('lastUpdated').textContent = 'Updated ' + (data.server_time || '');
      document.getElementById('liveBadge').className = 'badge bg-success';

      const assignedEl = document.getElementById('assignedTrip');
      if (data.assigned) {
        const b = data.assigned.booking;
        const t = data.assigned.trip;
        assignedEl.innerHTML =
          '<div class="mb-2"><strong>' + escapeHtml(b.route_name) + '</strong> ' + statusBadge(t.status)
          + (b.is_priority ? ' <span class="badge bg-warning text-dark">Priority</span>' : '') + '</div>'
          + '<div class="small text-muted mb-2">Booking ' + escapeHtml(b.booking_number)
          + ' · Seat ' + escapeHtml(b.seat_number) + '</div>'
          + '<div class="mb-1">' + escapeHtml(b.pickup_name) + ' → ' + escapeHtml(b.destination_name) + '</div>'
          + '<div class="p-3 rounded-3 mt-3 bg-primary-subtle">'
          + '<div class="small text-muted">Current location</div>'
          + '<strong>' + escapeHtml(t.current_location_name || 'En route') + '</strong>'
          + '<div class="small text-muted mt-1">Vehicle ' + escapeHtml(t.plate_number || '—') + '</div></div>';
      } else {
        assignedEl.innerHTML = '<p class="text-muted mb-0">No active trip linked to your bookings today.</p>';
      }

      const trips = data.trips || [];
      const body = document.getElementById('tripsBody');
      if (!trips.length) {
        body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No shuttles currently on the road.</td></tr>';
      } else {
        body.innerHTML = trips.map(t =>
          '<tr><td>' + escapeHtml(t.route_name || '—') + '</td><td>' + escapeHtml(t.plate_number || '—')
          + '</td><td>' + escapeHtml(t.driver_name || '—') + '</td><td>' + escapeHtml(t.current_location_name || 'Updating…')
          + '</td><td>' + statusBadge(t.status) + '</td></tr>'
        ).join('');
      }
    } catch (e) {
      document.getElementById('liveBadge').className = 'badge bg-danger';
      document.getElementById('liveBadge').textContent = 'Offline';
    }
  }

  poll();
  setInterval(poll, refreshMs);
})();
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
