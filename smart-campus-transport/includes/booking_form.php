<?php
/**
 * Reusable Booking Wizard Form Partial
 * Expects: $isStaff (bool), optionally $locations, $formAction
 */

declare(strict_types=1);

$isStaff = !empty($isStaff);
$locations = $locations ?? Location::getActive();
$formAction = $formAction ?? '';
$csrf = csrfToken();
$ajaxUrl = url('ajax/book.php');
$minDate = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+30 days'));
?>
<style>
.booking-wizard .wizard-steps { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.5rem; }
.booking-wizard .wizard-step {
  flex:1; min-width:110px; text-align:center; padding:.65rem .5rem; border-radius:.5rem;
  background:#e8f5f1; color:#0d5c4d; font-size:.85rem; font-weight:600; border:2px solid transparent;
}
.booking-wizard .wizard-step.active { background:#0d9488; color:#fff; }
.booking-wizard .wizard-step.done { background:#14b8a6; color:#fff; }
.booking-wizard .wizard-pane { display:none; }
.booking-wizard .wizard-pane.active { display:block; }
.path-preview {
  background:linear-gradient(135deg,#ecfdf5,#f0fdfa); border:1px solid #99f6e4;
  border-radius:.75rem; padding:1rem; min-height:80px;
}
.path-chip {
  display:inline-flex; align-items:center; background:#0d9488; color:#fff;
  border-radius:999px; padding:.25rem .75rem; margin:.2rem; font-size:.8rem;
}
.path-chip::after { content:'→'; margin-left:.5rem; opacity:.7; }
.path-chip:last-child::after { content:''; }
.seat-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(64px,1fr)); gap:.5rem; }
.seat-btn {
  border:2px solid #99f6e4; background:#fff; border-radius:.5rem; padding:.6rem .3rem;
  font-weight:600; color:#0f766e; cursor:pointer; transition:.15s;
}
.seat-btn:hover, .seat-btn.selected { background:#0d9488; color:#fff; border-color:#0d9488; }
.seat-btn:disabled { opacity:.4; cursor:not-allowed; background:#f1f5f9; }
.shuttle-card {
  border:2px solid #e2e8f0; border-radius:.75rem; padding:1rem; cursor:pointer; transition:.15s;
}
.shuttle-card:hover, .shuttle-card.selected { border-color:#0d9488; background:#f0fdfa; }
</style>

<div class="booking-wizard" id="bookingWizard"
     data-ajax="<?= e($ajaxUrl) ?>"
     data-csrf="<?= e($csrf) ?>"
     data-is-staff="<?= $isStaff ? '1' : '0' ?>">

  <div class="wizard-steps" id="wizardSteps">
    <div class="wizard-step active" data-step="1">1. Locations</div>
    <div class="wizard-step" data-step="2">2. Date &amp; Time</div>
    <div class="wizard-step" data-step="3">3. Shuttle</div>
    <div class="wizard-step" data-step="4">4. Seat</div>
    <div class="wizard-step" data-step="5">5. Confirm</div>
  </div>

  <form method="post" action="<?= e($formAction) ?>" id="bookingForm" novalidate>
    <?= csrfField() ?>
    <input type="hidden" name="action" value="create_booking">
    <input type="hidden" name="schedule_id" id="fieldScheduleId" value="">
    <input type="hidden" name="seat_number" id="fieldSeatNumber" value="">

    <!-- Step 1: Pickup / Destination -->
    <div class="wizard-pane active" data-pane="1">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="pickup_location_id">Pickup Location</label>
          <select class="form-select" name="pickup_location_id" id="pickup_location_id" required>
            <option value="">Select pickup…</option>
            <?php foreach ($locations as $loc): ?>
              <option value="<?= (int) $loc['id'] ?>"><?= e($loc['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="destination_location_id">Destination</label>
          <select class="form-select" name="destination_location_id" id="destination_location_id" required>
            <option value="">Select destination…</option>
            <?php foreach ($locations as $loc): ?>
              <option value="<?= (int) $loc['id'] ?>"><?= e($loc['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Shortest Path Preview (Dijkstra)</label>
          <div class="path-preview" id="pathPreview">
            <span class="text-muted">Select pickup and destination to see the optimal campus path.</span>
          </div>
        </div>
      </div>
      <div class="mt-4 d-flex justify-content-end">
        <button type="button" class="btn btn-primary px-4" id="btnStep1Next">Next</button>
      </div>
    </div>

    <!-- Step 2: Date -->
    <div class="wizard-pane" data-pane="2">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="booking_date">Travel Date</label>
          <input type="date" class="form-control" name="booking_date" id="booking_date"
                 min="<?= e($minDate) ?>" max="<?= e($maxDate) ?>" value="<?= e($minDate) ?>" required>
        </div>
        <div class="col-md-6 d-flex align-items-end">
          <p class="text-muted mb-0 small">Shuttles matching the selected day of week will be listed next.</p>
        </div>
      </div>
      <div class="mt-4 d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary" data-prev="1">Back</button>
        <button type="button" class="btn btn-primary" id="btnStep2Next">Find Shuttles</button>
      </div>
    </div>

    <!-- Step 3: Available shuttles -->
    <div class="wizard-pane" data-pane="3">
      <div id="shuttleList" class="row g-3">
        <div class="col-12 text-muted">Loading available shuttles…</div>
      </div>
      <div class="mt-4 d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary" data-prev="2">Back</button>
        <button type="button" class="btn btn-primary" id="btnStep3Next" disabled>Next</button>
      </div>
    </div>

    <!-- Step 4: Seat -->
    <div class="wizard-pane" data-pane="4">
      <p class="text-muted mb-3">Select an available seat for your trip.</p>
      <div class="seat-grid" id="seatGrid"></div>
      <div class="mt-4 d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary" data-prev="3">Back</button>
        <button type="button" class="btn btn-primary" id="btnStep4Next" disabled>Next</button>
      </div>
    </div>

    <!-- Step 5: Confirm -->
    <div class="wizard-pane" data-pane="5">
      <div class="card scts-card mb-3">
        <div class="card-body">
          <h5 class="mb-3">Confirm Booking</h5>
          <dl class="row mb-0" id="confirmSummary">
            <dt class="col-sm-4">Pickup</dt><dd class="col-sm-8" id="sumPickup">—</dd>
            <dt class="col-sm-4">Destination</dt><dd class="col-sm-8" id="sumDest">—</dd>
            <dt class="col-sm-4">Date</dt><dd class="col-sm-8" id="sumDate">—</dd>
            <dt class="col-sm-4">Shuttle</dt><dd class="col-sm-8" id="sumShuttle">—</dd>
            <dt class="col-sm-4">Departure</dt><dd class="col-sm-8" id="sumTime">—</dd>
            <dt class="col-sm-4">Seat</dt><dd class="col-sm-8" id="sumSeat">—</dd>
            <dt class="col-sm-4">Path</dt><dd class="col-sm-8" id="sumPath">—</dd>
          </dl>
        </div>
      </div>

      <?php if ($isStaff): ?>
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="is_priority" id="is_priority" value="1" checked>
        <label class="form-check-label fw-semibold" for="is_priority">
          Priority booking <span class="badge bg-primary">Staff</span>
        </label>
        <div class="form-text">Staff priority bookings are auto-approved and listed first for boarding.</div>
      </div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label" for="notes">Notes (optional)</label>
        <textarea class="form-control" name="notes" id="notes" rows="2" placeholder="Any special request…"></textarea>
      </div>

      <div id="bookingAlert" class="alert d-none" role="alert"></div>

      <div class="mt-3 d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary" data-prev="4">Back</button>
        <button type="submit" class="btn btn-lg px-4 btn-primary" id="btnConfirmBooking">
          Confirm Booking
        </button>
      </div>
    </div>
  </form>
</div>

<script>
(function () {
  const root = document.getElementById('bookingWizard');
  if (!root) return;

  const ajaxUrl = root.dataset.ajax;
  const csrf = root.dataset.csrf;
  let currentStep = 1;
  let selectedSchedule = null;
  let pathNames = [];

  const pickupEl = document.getElementById('pickup_location_id');
  const destEl = document.getElementById('destination_location_id');
  const dateEl = document.getElementById('booking_date');
  const pathPreview = document.getElementById('pathPreview');

  function goToStep(step) {
    currentStep = step;
    root.querySelectorAll('.wizard-pane').forEach(p => {
      p.classList.toggle('active', Number(p.dataset.pane) === step);
    });
    root.querySelectorAll('.wizard-step').forEach(s => {
      const n = Number(s.dataset.step);
      s.classList.toggle('active', n === step);
      s.classList.toggle('done', n < step);
    });
  }

  function optionText(sel) {
    return sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : '—';
  }

  async function fetchPath() {
    const pickup = pickupEl.value;
    const dest = destEl.value;
    if (!pickup || !dest || pickup === dest) {
      pathPreview.innerHTML = '<span class="text-muted">Select two different locations.</span>';
      pathNames = [];
      return;
    }
    pathPreview.innerHTML = '<span class="text-muted">Computing shortest path…</span>';
    try {
      const url = ajaxUrl + '?action=path&pickup_location_id=' + encodeURIComponent(pickup)
        + '&destination_location_id=' + encodeURIComponent(dest);
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await res.json();
      const path = data.path || {};
      if (!path.success) {
        pathPreview.innerHTML = '<span class="text-danger">' + (path.message || 'No path found.') + '</span>';
        pathNames = [];
        return;
      }
      pathNames = path.path_names || [];
      const chips = pathNames.map(n => '<span class="path-chip">' + escapeHtml(n) + '</span>').join('');
      pathPreview.innerHTML = chips
        + '<div class="mt-2 small text-muted">Distance: <strong>' + escapeHtml(path.distance_formatted || '')
        + '</strong> · Hops: <strong>' + (path.hops || 0) + '</strong></div>';
    } catch (e) {
      pathPreview.innerHTML = '<span class="text-danger">Unable to load path preview.</span>';
    }
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, m => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[m]));
  }

  pickupEl.addEventListener('change', fetchPath);
  destEl.addEventListener('change', fetchPath);

  document.getElementById('btnStep1Next').addEventListener('click', function () {
    if (!pickupEl.value || !destEl.value) {
      alert('Please select pickup and destination.');
      return;
    }
    if (pickupEl.value === destEl.value) {
      alert('Pickup and destination must be different.');
      return;
    }
    goToStep(2);
  });

  document.getElementById('btnStep2Next').addEventListener('click', async function () {
    if (!dateEl.value) {
      alert('Please select a travel date.');
      return;
    }
    goToStep(3);
    await loadShuttles();
  });

  async function loadShuttles() {
    const list = document.getElementById('shuttleList');
    list.innerHTML = '<div class="col-12 text-muted">Loading available shuttles…</div>';
    selectedSchedule = null;
    document.getElementById('btnStep3Next').disabled = true;
    document.getElementById('fieldScheduleId').value = '';

    const url = ajaxUrl + '?action=schedules'
      + '&date=' + encodeURIComponent(dateEl.value)
      + '&pickup_location_id=' + encodeURIComponent(pickupEl.value)
      + '&destination_location_id=' + encodeURIComponent(destEl.value);

    try {
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await res.json();
      const schedules = data.schedules || [];
      if (!schedules.length) {
        list.innerHTML = '<div class="col-12"><div class="alert alert-warning mb-0">No shuttles available for this date.</div></div>';
        return;
      }
      list.innerHTML = schedules.map(s => {
        const dep = (s.departure_time || '').substring(0, 5);
        const arr = (s.arrival_time || '').substring(0, 5);
        return '<div class="col-md-6">'
          + '<div class="shuttle-card" data-id="' + s.id + '" data-name="' + escapeHtml(s.route_name || '') + '"'
          + ' data-time="' + escapeHtml(dep) + '" data-plate="' + escapeHtml(s.plate_number || 'TBA') + '"'
          + ' data-seats=\'' + JSON.stringify(s.seats || []).replace(/'/g, '&#39;') + '\'>'
          + '<div class="d-flex justify-content-between align-items-start">'
          + '<div><strong>' + escapeHtml(s.route_name || 'Route') + '</strong>'
          + '<div class="small text-muted">' + escapeHtml(s.start_name || '') + ' → ' + escapeHtml(s.end_name || '') + '</div></div>'
          + '<span class="badge bg-primary">' + (s.seats_remaining || 0) + ' seats</span></div>'
          + '<div class="mt-2 small">Departs <strong>' + escapeHtml(dep) + '</strong> · Arrives <strong>' + escapeHtml(arr) + '</strong></div>'
          + '<div class="small text-muted">Vehicle: ' + escapeHtml(s.plate_number || 'TBA')
          + ' · Driver: ' + escapeHtml(s.driver_name || 'TBA') + '</div>'
          + '</div></div>';
      }).join('');

      list.querySelectorAll('.shuttle-card').forEach(card => {
        card.addEventListener('click', function () {
          list.querySelectorAll('.shuttle-card').forEach(c => c.classList.remove('selected'));
          card.classList.add('selected');
          selectedSchedule = {
            id: card.dataset.id,
            name: card.dataset.name,
            time: card.dataset.time,
            plate: card.dataset.plate,
            seats: JSON.parse(card.dataset.seats || '[]')
          };
          document.getElementById('fieldScheduleId').value = selectedSchedule.id;
          document.getElementById('btnStep3Next').disabled = false;
        });
      });
    } catch (e) {
      list.innerHTML = '<div class="col-12"><div class="alert alert-danger mb-0">Failed to load shuttles.</div></div>';
    }
  }

  document.getElementById('btnStep3Next').addEventListener('click', function () {
    if (!selectedSchedule) return;
    renderSeats(selectedSchedule.seats || []);
    goToStep(4);
  });

  function renderSeats(seats) {
    const grid = document.getElementById('seatGrid');
    document.getElementById('fieldSeatNumber').value = '';
    document.getElementById('btnStep4Next').disabled = true;
    if (!seats.length) {
      grid.innerHTML = '<div class="text-warning">No seats available on this shuttle.</div>';
      return;
    }
    grid.innerHTML = seats.map(seat =>
      '<button type="button" class="seat-btn" data-seat="' + escapeHtml(seat) + '">' + escapeHtml(seat) + '</button>'
    ).join('');
    grid.querySelectorAll('.seat-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        grid.querySelectorAll('.seat-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        document.getElementById('fieldSeatNumber').value = btn.dataset.seat;
        document.getElementById('btnStep4Next').disabled = false;
      });
    });
  }

  document.getElementById('btnStep4Next').addEventListener('click', function () {
    const seat = document.getElementById('fieldSeatNumber').value;
    if (!seat) return;
    document.getElementById('sumPickup').textContent = optionText(pickupEl);
    document.getElementById('sumDest').textContent = optionText(destEl);
    document.getElementById('sumDate').textContent = dateEl.value;
    document.getElementById('sumShuttle').textContent = (selectedSchedule && selectedSchedule.name) || '—';
    document.getElementById('sumTime').textContent = (selectedSchedule && selectedSchedule.time) || '—';
    document.getElementById('sumSeat').textContent = seat;
    document.getElementById('sumPath').textContent = pathNames.length ? pathNames.join(' → ') : '—';
    goToStep(5);
  });

  root.querySelectorAll('[data-prev]').forEach(btn => {
    btn.addEventListener('click', function () {
      goToStep(Number(btn.dataset.prev));
    });
  });

  document.getElementById('bookingForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const alertBox = document.getElementById('bookingAlert');
    const submitBtn = document.getElementById('btnConfirmBooking');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Booking…';
    alertBox.className = 'alert d-none';

    const formData = new FormData(this);
    formData.append('action', 'create');

    try {
      const res = await fetch(ajaxUrl + '?action=create', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf
        },
        body: formData
      });
      const data = await res.json();
      if (data.success) {
        alertBox.className = 'alert alert-success';
        alertBox.textContent = data.message;
        setTimeout(function () {
          window.location.href = data.ticket_url || 'bookings.php';
        }, 800);
      } else {
        alertBox.className = 'alert alert-danger';
        alertBox.textContent = data.message || 'Booking failed.';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Confirm Booking';
      }
    } catch (err) {
      alertBox.className = 'alert alert-danger';
      alertBox.textContent = 'Network error. Please try again.';
      submitBtn.disabled = false;
      submitBtn.textContent = 'Confirm Booking';
    }
  });
})();
</script>
