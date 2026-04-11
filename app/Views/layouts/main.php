<?php
use App\Helpers\View;
$branding = $clubBranding ?? [];
$primary  = $branding['primary_color'] ?? '#0d6efd';
$navbarBg = $branding['navbar_bg']     ?? '#212529';
?><!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title ?? 'KlubSportowy') ?> — <?= View::e($appName ?? 'KlubSportowy') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= url('css/app.css') ?>">
    <style>
        :root {
            --app-primary: <?= View::e($primary) ?>;
            --app-navbar-bg: <?= View::e($navbarBg) ?>;
        }
        .sidebar {
            background: var(--app-navbar-bg);
            color: #cdd6f4;
            min-height: 100vh;
            width: 260px;
            position: fixed; left:0; top:0;
            padding: 1rem 0;
            overflow-y: auto;
        }
        .sidebar a { color: #cdd6f4; text-decoration: none; display:block; padding: .5rem 1rem; border-left: 3px solid transparent; }
        .sidebar a:hover { background: rgba(255,255,255,.05); border-left-color: var(--app-primary); color: #fff; }
        .sidebar a.active { background: rgba(255,255,255,.08); border-left-color: var(--app-primary); color: #fff; font-weight: 600; }
        .sidebar .brand { padding: .5rem 1rem 1rem 1rem; border-bottom: 1px solid rgba(255,255,255,.08); margin-bottom: .5rem; }
        .sidebar .section-label { text-transform: uppercase; font-size: .7rem; color: #7f849c; padding: 1rem 1rem .3rem 1rem; letter-spacing: .1em; }
        .main-content { margin-left: 260px; padding: 1.5rem; min-height: 100vh; background: #f8f9fa; }
        .btn-primary, .bg-primary { background-color: var(--app-primary) !important; border-color: var(--app-primary) !important; }
        .text-primary { color: var(--app-primary) !important; }
        .card { border: 0; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
        .sport-badge { display:inline-block; padding: .2rem .5rem; border-radius: .3rem; font-size: .75rem; color:#fff; }
        @media (max-width: 768px) {
            .sidebar { width: 100%; min-height: auto; position: static; }
            .main-content { margin-left: 0; }
        }
    </style>
    <?php if (!empty($branding['custom_css'])): ?>
    <style><?= $branding['custom_css'] ?></style>
    <?php endif; ?>
</head>
<body>

<nav class="sidebar">
    <div class="brand">
        <h5 class="mb-0"><i class="bi bi-trophy"></i> <?= View::e($appName ?? 'KlubSportowy') ?></h5>
        <?php if (!empty($currentClub)): ?>
            <small class="d-block mt-1 text-muted"><?= View::e($currentClub['name']) ?></small>
        <?php endif; ?>
        <?php if (!empty($activeSportKey)): ?>
            <small class="d-block text-info">sport: <?= View::e($activeSportKey) ?></small>
        <?php endif; ?>
    </div>

    <div class="section-label">Klub</div>
    <a href="<?= url('dashboard') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="<?= url('members') ?>"><i class="bi bi-people"></i> Zawodnicy</a>
    <a href="<?= url('sports') ?>"><i class="bi bi-trophy"></i> Sekcje sportowe</a>
    <a href="<?= url('calendar') ?>"><i class="bi bi-calendar3"></i> Kalendarz</a>
    <a href="<?= url('events') ?>"><i class="bi bi-calendar-event"></i> Wydarzenia</a>
    <a href="<?= url('trainings') ?>"><i class="bi bi-stopwatch"></i> Treningi</a>
    <a href="<?= url('fees') ?>"><i class="bi bi-cash-coin"></i> Finanse</a>
    <a href="<?= url('fees/rates') ?>"><i class="bi bi-tag"></i> Stawki opłat</a>
    <a href="<?= url('medical') ?>"><i class="bi bi-heart-pulse"></i> Badania lekarskie</a>

    <?php if (!empty($sportNav)): ?>
        <div class="section-label">Sekcja: <?= View::e($activeSportKey) ?></div>
        <?php foreach ($sportNav as $item): ?>
            <a href="<?= url($item['url'] ?? '#') ?>">
                <i class="bi <?= View::e($item['icon'] ?? 'bi-dot') ?>"></i>
                <?= View::e($item['label'] ?? '') ?>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($clubSports)): ?>
        <div class="section-label">Szybkie przełączanie sekcji</div>
        <?php foreach ($clubSports as $cs): ?>
            <form method="POST" action="<?= url('sports/activate/' . (int)$cs['club_sport_id']) ?>" class="m-0">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-link text-start w-100 p-2" style="color:#cdd6f4; text-decoration:none;">
                    <i class="bi <?= View::e($cs['icon'] ?? 'bi-dot') ?>"></i>
                    <?= View::e($cs['name']) ?>
                </button>
            </form>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($isSuperAdmin)): ?>
        <div class="section-label">Administracja</div>
        <a href="<?= url('admin/dashboard') ?>"><i class="bi bi-shield-lock"></i> Panel admina</a>
        <a href="<?= url('admin/clubs') ?>"><i class="bi bi-building"></i> Kluby</a>
        <a href="<?= url('admin/sports') ?>"><i class="bi bi-grid-3x3-gap"></i> Katalog sportów</a>
        <a href="<?= url('admin/plans') ?>"><i class="bi bi-credit-card"></i> Plany</a>
    <?php endif; ?>

    <div class="section-label">Konto</div>
    <?php if (!empty($authUser)): ?>
        <div class="px-3 py-2 small text-muted">
            <i class="bi bi-person-circle"></i> <?= View::e($authUser['full_name'] ?? $authUser['username'] ?? '') ?>
        </div>
    <?php endif; ?>
    <a href="<?= url('auth/logout') ?>"><i class="bi bi-box-arrow-right"></i> Wyloguj</a>
</nav>

<main class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0"><?= View::e($title ?? '') ?></h2>
        <?php if (!empty($subscription)): ?>
            <span class="badge bg-secondary">Plan: <?= View::e($subscription['plan_name'] ?? '') ?> • do <?= View::e(format_date($subscription['valid_until'] ?? null)) ?></span>
        <?php endif; ?>
    </div>

    <?php foreach (['flashSuccess'=>'success','flashError'=>'danger','flashWarning'=>'warning','flashInfo'=>'info'] as $k => $cls): ?>
        <?php if (!empty($$k)): ?>
            <div class="alert alert-<?= $cls ?> alert-dismissible fade show">
                <?= View::e($$k) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?= $content ?? '' ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('js/app.js') ?>"></script>
</body>
</html>
