<?php
$assetRoot = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public';
$cssVersion = filemtime($assetRoot . DIRECTORY_SEPARATOR . 'assets/css/app.css');
$jsVersion = filemtime($assetRoot . DIRECTORY_SEPARATOR . 'assets/js/app.js');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(config('app.name', 'Football Platform')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= route_path('/assets/css/app.css') ?>?v=<?= $cssVersion ?>">
</head>
<body>
<div class="bg-layer"></div>
<div id="siteRainBg" class="site-rain-bg" aria-hidden="true">
    <div id="siteLightningFlash" class="site-lightning-flash"></div>
</div>
<nav class="navbar navbar-expand-lg navbar-dark py-3">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= route_path('/') ?>">KickOff Elite</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <?php if (Auth::check()): ?>
                    <?php if ((Auth::user()['role'] ?? '') !== 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= route_path('/dashboard') ?>">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= route_path('/pitches') ?>">Pitches</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= route_path('/bookings') ?>">My Bookings</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= route_path('/team-ads') ?>">Team Ads</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= route_path('/backup-calls') ?>">Backup Calls</a></li>
                    <?php endif; ?>
                    <?php if ((Auth::user()['role'] ?? '') === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= route_path('/admin/dashboard') ?>">Admin Dash</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= route_path('/admin/pitches') ?>">Manage Pitches</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= route_path('/admin/verify-code') ?>">Verify Code</a></li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <form action="<?= route_path('/auth/logout') ?>" method="post" class="m-0">
                            <?= Csrf::inputField() ?>
                            <button class="btn btn-sm btn-outline-light rounded-pill px-3" type="submit">Logout</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= route_path('/auth/login') ?>">Login</a></li>
                    <li class="nav-item"><a class="btn btn-sm btn-warning rounded-pill px-3" href="<?= route_path('/auth/register') ?>">Get Started</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="pb-5">
    <div class="container">
        <?php foreach (['success', 'danger', 'info', 'warning'] as $type): ?>
            <?php foreach (flash_get($type) as $message): ?>
                <div class="alert alert-<?= e($type) ?> shadow-sm border-0" role="alert"><?= e($message) ?></div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

    <div class="container">
        <?php require $viewFile; ?>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= route_path('/assets/js/app.js') ?>?v=<?= $jsVersion ?>"></script>
</body>
</html>
