<?php
/**
 * Full-page Loading Spinner Partial
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Include this partial when you need a dedicated full-page spinner markup
 * (also duplicated lightly in header.php for early paint).
 */
?>
<div id="fullPageSpinner" class="page-spinner" aria-live="polite" aria-busy="true">
    <div class="spinner-panel">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 mb-0 text-muted">Loading, please wait...</p>
    </div>
</div>
