<?php
/**
 * Top Navbar
 * Smart Campus Transportation System - Federal Polytechnic Ede
 *
 * Includes search, notifications dropdown (AJAX unread count),
 * dark mode toggle, and user dropdown (profile, change password, logout).
 */

declare(strict_types=1);

$role = currentUserRole() ?? 'guest';
$profileUrl = url('views/' . $role . '/profile.php');
$pageHeading = $pageHeading ?? ($pageTitle ?? 'Dashboard');
?>
<header class="scts-navbar">
    <div class="navbar-left">
        <button type="button" class="btn btn-icon d-lg-none" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="fa-solid fa-bars"></i>
        </button>
        <h1 class="page-heading mb-0"><?= e($pageHeading) ?></h1>
    </div>

    <div class="navbar-right">
        <form class="navbar-search d-none d-md-flex" action="<?= e($_SERVER['PHP_SELF'] ?? '#') ?>" method="get" role="search">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="search" name="q" class="form-control" placeholder="Search..."
                       value="<?= e($_GET['q'] ?? '') ?>" aria-label="Search">
            </div>
        </form>

        <!-- Notifications -->
        <div class="dropdown">
            <button class="btn btn-icon position-relative" type="button" id="notifDropdown"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                <i class="fa-solid fa-bell"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
                      id="notifBadge">0</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end notif-dropdown" aria-labelledby="notifDropdown">
                <div class="notif-header d-flex justify-content-between align-items-center px-3 py-2">
                    <strong>Notifications</strong>
                    <button type="button" class="btn btn-link btn-sm p-0" id="markAllReadBtn">Mark all read</button>
                </div>
                <div class="notif-list" id="notifList">
                    <div class="text-center text-muted py-3 small">Loading...</div>
                </div>
                <div class="notif-footer text-center border-top">
                    <a href="<?= e(url('views/' . $role . '/notifications.php')) ?>" class="dropdown-item small">View all</a>
                </div>
            </div>
        </div>

        <!-- Dark mode -->
        <button type="button" class="btn btn-icon" id="themeToggle" title="Toggle dark mode" aria-label="Toggle dark mode">
            <i class="fa-solid fa-moon" id="themeIcon"></i>
        </button>

        <!-- User menu -->
        <div class="dropdown">
            <button class="btn btn-user dropdown-toggle" type="button" id="userDropdown"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <span class="user-avatar-sm"><i class="fa-solid fa-user"></i></span>
                <span class="d-none d-md-inline user-name"><?= e(currentUserName()) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li>
                    <a class="dropdown-item" href="<?= e($profileUrl) ?>">
                        <i class="fa-solid fa-user me-2"></i>Profile
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?= e($profileUrl) ?>#password">
                        <i class="fa-solid fa-key me-2"></i>Change Password
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="<?= e(url('views/auth/logout.php')) ?>">
                        <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
<main class="scts-content" id="sctsContent">
