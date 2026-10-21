<?php
/**
 * Landing / Home Page
 * Smart Campus Transportation System - Federal Polytechnic Ede
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';

startAppSession();
attemptRememberLogin();

if (isLoggedIn()) {
    redirect(dashboardUrl());
}

$flashMessages = getFlash();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_FULL_NAME) ?> — <?= e(APP_INSTITUTION) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --campus-green: #0d7377;
            --campus-green-dark: #095456;
            --campus-teal: #14919b;
            --campus-mint: #e8f6f5;
            --campus-deep: #0a3d3f;
            --hero-height: 100vh;
        }

        [data-bs-theme="dark"] {
            --campus-mint: #0f2a2c;
            --bs-body-bg: #0b1c1e;
            --bs-body-color: #e4f0ee;
        }

        body {
            font-family: "DM Sans", system-ui, sans-serif;
            overflow-x: hidden;
        }

        .navbar-landing {
            background: transparent;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 20;
            padding: 1rem 0;
            transition: background 0.3s;
        }

        .navbar-landing .navbar-brand {
            font-family: "Fraunces", Georgia, serif;
            font-weight: 700;
            font-size: 1.15rem;
            color: #fff !important;
            letter-spacing: -0.01em;
        }

        .navbar-landing .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
        }

        .navbar-landing .btn-outline-light {
            border-radius: 0.5rem;
            font-weight: 600;
        }

        .navbar-landing .btn-light {
            background: #fff;
            color: var(--campus-green-dark);
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
        }

        /* Full-bleed hero */
        .hero {
            min-height: var(--hero-height);
            display: flex;
            align-items: center;
            position: relative;
            color: #fff;
            background:
                linear-gradient(115deg, rgba(10, 61, 63, 0.92) 0%, rgba(13, 115, 119, 0.78) 48%, rgba(10, 92, 76, 0.7) 100%),
                url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"),
                linear-gradient(160deg, #0a5c4c, #0d7377 55%, #095456);
            background-size: cover, 60px 60px, cover;
        }

        .hero::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 120px;
            background: linear-gradient(to top, var(--bs-body-bg), transparent);
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            padding-top: 5rem;
            padding-bottom: 4rem;
            animation: heroFade 0.8s ease both;
        }

        @keyframes heroFade {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hero-brand {
            font-family: "Fraunces", Georgia, serif;
            font-size: clamp(2.4rem, 5.5vw, 3.75rem);
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -0.02em;
            margin-bottom: 0.75rem;
        }

        .hero-institution {
            display: inline-block;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--campus-teal);
            padding-bottom: 0.25rem;
        }

        .hero-lead {
            font-size: 1.15rem;
            max-width: 32rem;
            color: rgba(255, 255, 255, 0.88);
            margin-bottom: 2rem;
        }

        .hero-ctas .btn {
            border-radius: 0.5rem;
            font-weight: 600;
            padding: 0.7rem 1.5rem;
        }

        .btn-hero-primary {
            background: #fff;
            color: var(--campus-green-dark);
            border: none;
        }

        .btn-hero-primary:hover {
            background: var(--campus-mint);
            color: var(--campus-green-dark);
        }

        .btn-hero-outline {
            border: 2px solid rgba(255, 255, 255, 0.7);
            color: #fff;
            background: transparent;
        }

        .btn-hero-outline:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            border-color: #fff;
        }

        .theme-toggle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.4);
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
        }

        .theme-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        [data-bs-theme="dark"] .theme-toggle {
            border-color: rgba(255, 255, 255, 0.3);
        }

        /* Features section */
        .features {
            padding: 5rem 0 4rem;
            position: relative;
        }

        .features .section-title {
            font-family: "Fraunces", Georgia, serif;
            font-weight: 700;
            color: var(--campus-green-dark);
            margin-bottom: 0.5rem;
        }

        [data-bs-theme="dark"] .features .section-title {
            color: #7dd3ce;
        }

        .feature-item {
            padding: 1.5rem 0;
            animation: featureRise 0.6s ease both;
        }

        .feature-item:nth-child(2) { animation-delay: 0.1s; }
        .feature-item:nth-child(3) { animation-delay: 0.2s; }
        .feature-item:nth-child(4) { animation-delay: 0.3s; }

        @keyframes featureRise {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .feature-icon {
            width: 52px;
            height: 52px;
            border-radius: 0.75rem;
            background: linear-gradient(145deg, var(--campus-teal), var(--campus-green-dark));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            margin-bottom: 1rem;
        }

        .feature-item h3 {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .feature-item p {
            color: var(--bs-secondary-color);
            margin-bottom: 0;
            max-width: 28rem;
        }

        .site-footer {
            border-top: 1px solid rgba(13, 115, 119, 0.15);
            padding: 2rem 0;
            font-size: 0.9rem;
            color: var(--bs-secondary-color);
        }

        [data-bs-theme="dark"] .site-footer {
            border-top-color: rgba(125, 211, 206, 0.15);
        }

        .flash-wrap {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1050;
            max-width: 360px;
        }
    </style>
</head>
<body>
    <?php if (!empty($flashMessages)): ?>
        <div class="flash-wrap">
            <?php foreach ($flashMessages as $msg): ?>
                <div class="alert alert-<?= e($msg['type'] === 'error' ? 'danger' : $msg['type']) ?> alert-dismissible fade show shadow" role="alert">
                    <?= e($msg['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <nav class="navbar navbar-expand-lg navbar-landing">
        <div class="container">
            <a class="navbar-brand" href="<?= e(url('index.php')) ?>">
                <i class="bi bi-bus-front-fill me-2"></i><?= e(APP_NAME) ?>
            </a>
            <div class="d-flex align-items-center gap-2 ms-auto">
                <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode" title="Toggle dark mode">
                    <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
                </button>
                <a href="<?= e(url('views/auth/login.php')) ?>" class="btn btn-outline-light btn-sm d-none d-sm-inline-block">Login</a>
                <a href="<?= e(url('views/auth/register.php')) ?>" class="btn btn-light btn-sm">Register</a>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="container hero-content">
            <p class="hero-institution"><?= e(APP_INSTITUTION) ?></p>
            <h1 class="hero-brand"><?= e(APP_FULL_NAME) ?></h1>
            <p class="hero-lead">
                Plan routes, book campus shuttles, and track trips in real time —
                built for students, staff, and drivers across Federal Polytechnic Ede.
            </p>
            <div class="hero-ctas d-flex flex-wrap gap-3">
                <a href="<?= e(url('views/auth/login.php')) ?>" class="btn btn-hero-primary btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </a>
                <a href="<?= e(url('views/auth/register.php')) ?>" class="btn btn-hero-outline btn-lg">
                    <i class="bi bi-person-plus me-2"></i>Register
                </a>
            </div>
        </div>
    </header>

    <section class="features" id="features">
        <div class="container">
            <div class="row mb-4">
                <div class="col-lg-7">
                    <h2 class="section-title">Campus transport, simplified</h2>
                    <p class="text-secondary mb-0">Everything you need to move around campus efficiently and safely.</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 feature-item">
                    <div class="feature-icon"><i class="bi bi-geo-alt-fill"></i></div>
                    <h3>Smart Route Planning</h3>
                    <p>Find the shortest campus paths using live road and location data so you spend less time waiting and walking.</p>
                </div>
                <div class="col-md-6 feature-item">
                    <div class="feature-icon"><i class="bi bi-calendar-check-fill"></i></div>
                    <h3>Easy Shuttle Booking</h3>
                    <p>Browse schedules, reserve seats, and manage your trips — with priority options for staff when needed.</p>
                </div>
                <div class="col-md-6 feature-item">
                    <div class="feature-icon"><i class="bi bi-broadcast-pin"></i></div>
                    <h3>Live Trip Tracking</h3>
                    <p>Follow vehicles in near real time and get clearer ETAs so you know when your shuttle is approaching.</p>
                </div>
                <div class="col-md-6 feature-item">
                    <div class="feature-icon"><i class="bi bi-bell-fill"></i></div>
                    <h3>Instant Notifications</h3>
                    <p>Stay informed about booking approvals, schedule changes, and trip updates from one dashboard.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="site-footer">
        <div class="container d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <span>&copy; <?= date('Y') ?> <?= e(APP_INSTITUTION) ?> — <?= e(APP_NAME) ?></span>
            <span class="small">v<?= e(APP_VERSION) ?></span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            var root = document.documentElement;
            var toggle = document.getElementById('themeToggle');
            var icon = document.getElementById('themeIcon');
            var stored = localStorage.getItem('scts-theme');

            function applyTheme(theme) {
                root.setAttribute('data-bs-theme', theme);
                if (icon) {
                    icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
                }
                localStorage.setItem('scts-theme', theme);
            }

            if (stored === 'dark' || stored === 'light') {
                applyTheme(stored);
            }

            if (toggle) {
                toggle.addEventListener('click', function () {
                    var next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
                    applyTheme(next);
                });
            }
        })();
    </script>
</body>
</html>
