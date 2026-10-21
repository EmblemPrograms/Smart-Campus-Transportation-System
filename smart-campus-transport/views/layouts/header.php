<?php
/**
 * Main Layout Header
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Outputs DOCTYPE, head (Bootstrap 5.3, Chart.js, Font Awesome 6, custom CSS),
 * dark-mode bootstrap from localStorage, and opening body/wrapper tags.
 *
 * Expected variables (optional):
 *   $pageTitle  - Page title suffix
 *   $bodyClass  - Extra body CSS classes
 *   $extraCss   - Additional CSS file paths (array) or HTML string
 */

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    require_once dirname(__DIR__, 2) . '/config/app.php';
    startAppSession();
}

$pageTitle = $pageTitle ?? APP_NAME;
$bodyClass = $bodyClass ?? '';
$extraCss = $extraCss ?? [];
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e(APP_FULL_NAME) ?> - <?= e(APP_INSTITUTION) ?>">
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <!-- App CSS -->
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">

    <?php if (is_array($extraCss)): ?>
        <?php foreach ($extraCss as $cssFile): ?>
            <link href="<?= e(asset($cssFile)) ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php elseif (is_string($extraCss) && $extraCss !== ''): ?>
        <?= $extraCss ?>
    <?php endif; ?>

    <!-- Apply dark mode before paint to avoid flash -->
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('scts-theme') || 'light';
                document.documentElement.setAttribute('data-bs-theme', theme);
                if (theme === 'dark') {
                    document.documentElement.classList.add('theme-dark');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body class="scts-body <?= e($bodyClass) ?>">
<div id="page-spinner" class="page-spinner d-none" aria-hidden="true">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>
<div class="scts-wrapper" id="sctsWrapper">
