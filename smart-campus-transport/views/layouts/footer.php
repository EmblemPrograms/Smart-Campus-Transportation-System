<?php
/**
 * Layout Footer
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Closes main content, loads jQuery/Bootstrap/app.js, toast container for flash
 * messages, and starts notification polling.
 *
 * Optional: $extraJs - array of asset paths or raw HTML string
 */

declare(strict_types=1);

$extraJs = $extraJs ?? [];
$flashMessages = getFlash();
?>
</main><!-- /.scts-content -->
</div><!-- /.scts-wrapper -->

<!-- Toast container for flash + JS toasts -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer" style="z-index: 1100;">
    <?php foreach ($flashMessages as $flash): ?>
        <?php
        $type = $flash['type'] ?? 'info';
        $bsClass = match ($type) {
            'success' => 'text-bg-success',
            'error', 'danger' => 'text-bg-danger',
            'warning' => 'text-bg-warning',
            default => 'text-bg-info',
        };
        ?>
        <div class="toast align-items-center <?= e($bsClass) ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true"
             data-bs-autohide="true" data-bs-delay="5000">
            <div class="d-flex">
                <div class="toast-body"><?= e($flash['message'] ?? '') ?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    window.SCTS = window.SCTS || {};
    window.SCTS.baseUrl = <?= json_encode(url()) ?>;
    window.SCTS.assetUrl = <?= json_encode(url('assets')) ?>;
    window.SCTS.csrfToken = <?= json_encode(csrfToken()) ?>;
    window.SCTS.ajax = {
        notifications: <?= json_encode(url('ajax/notifications.php')) ?>,
        dijkstra: <?= json_encode(url('ajax/dijkstra.php')) ?>,
        tracking: <?= json_encode(url('ajax/tracking.php')) ?>,
        dashboardStats: <?= json_encode(url('ajax/dashboard-stats.php')) ?>,
        bookingActions: <?= json_encode(url('ajax/booking-actions.php')) ?>,
        seats: <?= json_encode(url('ajax/seats.php')) ?>
    };
    window.SCTS.trackingInterval = <?= (int) TRACKING_REFRESH_INTERVAL ?> * 1000;
</script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- App JS -->
<script src="<?= e(asset('js/app.js')) ?>"></script>

<?php if (is_array($extraJs)): ?>
    <?php foreach ($extraJs as $jsFile): ?>
        <script src="<?= e(asset($jsFile)) ?>"></script>
    <?php endforeach; ?>
<?php elseif (is_string($extraJs) && $extraJs !== ''): ?>
    <?= $extraJs ?>
<?php endif; ?>
</body>
</html>
