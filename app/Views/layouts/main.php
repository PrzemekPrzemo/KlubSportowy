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
    <?php
    $navItems = [
        'dashboard'     => ['url' => 'dashboard',     'icon' => 'bi-speedometer2',   'label' => 'Dashboard',       'mod' => null],
        'members'       => ['url' => 'members',       'icon' => 'bi-people',         'label' => 'Zawodnicy',       'mod' => 'members'],
        'sports'        => ['url' => 'sports',        'icon' => 'bi-trophy',         'label' => 'Sekcje sportowe', 'mod' => 'sports'],
        'calendar'      => ['url' => 'calendar',      'icon' => 'bi-calendar3',      'label' => 'Kalendarz',       'mod' => 'calendar'],
        'events'        => ['url' => 'events',        'icon' => 'bi-calendar-event', 'label' => 'Wydarzenia',      'mod' => 'events'],
        'trainings'     => ['url' => 'trainings',     'icon' => 'bi-stopwatch',      'label' => 'Treningi',        'mod' => 'trainings'],
        'fees'          => ['url' => 'fees',          'icon' => 'bi-cash-coin',      'label' => 'Finanse',         'mod' => 'fees'],
        'fees_rates'    => ['url' => 'fees/rates',    'icon' => 'bi-tag',            'label' => 'Stawki opłat',    'mod' => 'fees'],
        'medical'       => ['url' => 'medical',       'icon' => 'bi-heart-pulse',    'label' => 'Badania lekarskie','mod' => 'medical'],
        'announcements' => ['url' => 'announcements', 'icon' => 'bi-megaphone',      'label' => 'Ogłoszenia',      'mod' => 'announcements'],
    ];
    $allowed = $navModules ?? null; // null = pełny dostęp
    foreach ($navItems as $item):
        if ($item['mod'] === null || $allowed === null || in_array($item['mod'], $allowed, true)):
    ?>
        <a href="<?= url($item['url']) ?>"><i class="bi <?= View::e($item['icon']) ?>"></i> <?= View::e($item['label']) ?></a>
    <?php endif; endforeach; ?>

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
        <div class="d-flex align-items-center gap-2">
            <?php if (!empty($unreadNotifsCount)): ?>
                <div class="dropdown">
                    <button type="button" class="btn btn-outline-secondary position-relative" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= (int)$unreadNotifsCount ?>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-2" style="width:340px; max-height:400px; overflow-y:auto;">
                        <h6 class="dropdown-header">Powiadomienia (<?= (int)$unreadNotifsCount ?>)</h6>
                        <?php foreach (($unreadNotifs ?? []) as $n): ?>
                            <form method="POST" action="<?= url('notifications/' . (int)$n['id'] . '/read') ?>" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-link text-start w-100 px-2 py-1 text-decoration-none text-dark">
                                    <strong class="small d-block"><?= View::e($n['title']) ?></strong>
                                    <?php if (!empty($n['body'])): ?>
                                        <div class="small text-muted"><?= View::e(substr($n['body'], 0, 80)) ?></div>
                                    <?php endif; ?>
                                    <small class="text-muted"><?= format_datetime($n['created_at']) ?></small>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (!empty($subscription)): ?>
                <span class="badge bg-secondary">Plan: <?= View::e($subscription['plan_name'] ?? '') ?> • do <?= View::e(format_date($subscription['valid_until'] ?? null)) ?></span>
            <?php endif; ?>
        </div>
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
