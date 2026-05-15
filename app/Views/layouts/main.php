<?php
use App\Helpers\View;
$branding = $clubBranding ?? [];
$primary  = $branding['primary_color'] ?? '#EE2C28';
$navbarBg = $branding['navbar_bg']     ?? '#232232';
?><!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <?php $clubFavicon = $branding['favicon_path'] ?? null; ?>
    <?php if (!empty($clubFavicon)): ?>
        <?php $favExt = strtolower(pathinfo($clubFavicon, PATHINFO_EXTENSION)); ?>
        <link rel="icon" type="<?= $favExt === 'png' ? 'image/png' : 'image/x-icon' ?>" href="<?= url($clubFavicon) ?>">
    <?php else: ?>
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <?php endif; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#EE2C28">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/manifest.json">
    <title><?= View::e($title ?? 'ClubDesk') ?> — <?= View::e($appName ?? 'ClubDesk') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= url('css/app.css') ?>">
    <link rel="stylesheet" href="<?= url('css/dark-mode.css') ?>">
    <style>
        :root {
            --app-primary: <?= View::e($primary) ?>;
            --app-navbar-bg: <?= View::e($navbarBg) ?>;
        }
        .sidebar {
            background: var(--app-navbar-bg);
            color: rgba(255,255,255,0.85);
            height: 100vh;
            width: 260px;
            position: fixed; left:0; top:0;
            padding: 1rem 0;
            overflow-y: auto;
        }
        .sidebar a { color: rgba(255,255,255,0.85); text-decoration: none; display:block; padding: .5rem 1rem; border-left: 3px solid transparent; }
        .sidebar a:hover { background: rgba(255,255,255,.05); border-left-color: var(--app-primary); color: #fff; }
        .sidebar a.active { background: rgba(255,255,255,.08); border-left-color: var(--app-primary); color: #fff; font-weight: 600; }
        .sidebar .brand { padding: .5rem 1rem 1rem 1rem; border-bottom: 1px solid rgba(255,255,255,.08); margin-bottom: .5rem; }
        .sidebar .section-label { text-transform: uppercase; font-size: .7rem; color: #7f849c; padding: 1rem 1rem .3rem 1rem; letter-spacing: .1em; }

        /* Collapsible sections (Z.3 — sidebar grupy) */
        .sidebar .section-toggle {
            background: transparent; border: 0; width: 100%; text-align: left;
            color: #7f849c; text-transform: uppercase; font-size: .7rem;
            letter-spacing: .1em; padding: 1rem 1rem .3rem 1rem;
            display: flex; align-items: center; justify-content: space-between;
            cursor: pointer; font-family: inherit;
        }
        .sidebar .section-toggle:hover { color: rgba(255,255,255,.85); }
        .sidebar .section-toggle .bi-chevron-down { transition: transform .2s ease; font-size: .65rem; opacity: .6; }
        .sidebar .section-toggle[aria-expanded="false"] .bi-chevron-down { transform: rotate(-90deg); }
        .sidebar .section-items { overflow: hidden; max-height: 2000px; transition: max-height .25s ease; }
        .sidebar .section-toggle[aria-expanded="false"] + .section-items { max-height: 0; }
        .main-content { margin-left: 260px; padding: 1.5rem; min-height: 100vh; background: var(--cd-slate, #F0F2F5); }
        .btn-primary, .bg-primary { background-color: var(--app-primary) !important; border-color: var(--app-primary) !important; }
        .text-primary { color: var(--app-primary) !important; }
        .card { border: 0; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
        .sport-badge { display:inline-block; padding: .2rem .5rem; border-radius: .3rem; font-size: .75rem; color:#fff; }
        .hamburger-btn { display: none; position: fixed; top: 10px; left: 10px; z-index: 1100; background: var(--app-navbar-bg); color: #fff; border: none; border-radius: 6px; padding: 8px 12px; font-size: 1.2rem; cursor: pointer; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1049; }
        .search-wrapper { position: relative; }
        .search-dropdown { position: absolute; top: 100%; left: 0; right: 0; z-index: 1050; background: #fff; border: 1px solid #dee2e6; border-radius: .5rem; box-shadow: 0 4px 12px rgba(0,0,0,.15); max-height: 400px; overflow-y: auto; display: none; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform .3s ease; width: 260px; position: fixed; z-index: 1050; }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.active { display: block; }
            .hamburger-btn { display: block; }
            .main-content { margin-left: 0; padding-top: 3.5rem; }
            .table-responsive-auto { overflow-x: auto; }
        }
    </style>
    <?php if (!empty($branding['custom_css'])): ?>
    <style id="club-custom-css"><?= $branding['custom_css'] /* sanitized at save (WhitelabelSanitizer::sanitizeCss) */ ?></style>
    <?php endif; ?>
</head>
<body>

<button class="hamburger-btn" id="hamburger-btn"><i class="bi bi-list"></i></button>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<nav class="sidebar" id="sidebar">
    <div class="brand">
        <?php if (!empty($clubBranding['logo_path'])): ?>
            <img src="<?= url($clubBranding['logo_path']) ?>" alt="logo" style="max-width:180px; max-height:60px; margin-bottom:.5rem;">
        <?php endif; ?>
        <h5 class="mb-0"><img src="<?= View::e(system_logo('white')) ?>" alt="CD" style="height:48px;vertical-align:middle;margin-right:8px;"> <span style="color:#EE2C28;font-weight:700;">clubdesk.pl</span></h5>
        <?php if (!empty($currentClub)): ?>
            <small class="d-block mt-1 text-muted"><?= View::e($currentClub['name']) ?></small>
        <?php endif; ?>
        <?php if (!empty($clubBranding['motto'])): ?>
            <small class="d-block" style="color:#F9C6CE;"><em><?= View::e($clubBranding['motto']) ?></em></small>
        <?php endif; ?>
        <?php if (!empty($activeSportKey)): ?>
            <small class="d-block text-info">sport: <?= View::e($activeSportKey) ?></small>
        <?php endif; ?>
    </div>

    <?php
    // When super admin has no club selected — show only dashboard + info hint (skip club items)
    $isSuperAdminNoClub = !empty($isSuperAdmin) && empty($currentClubId);
    ?>

    <?php if (!$isSuperAdminNoClub):
    // ── Pogrupowane sekcje klubu (Z.3) ────────────────────────────
    // Każda pod-sekcja jest collapsible — stan w localStorage 'cd_nav_collapsed'.
    $allItems = [
        'dashboard'      => ['url' => 'dashboard',                  'icon' => 'bi-speedometer2',           'label' => __('nav.dashboard'),       'mod' => null],
        'members'        => ['url' => 'members',                    'icon' => 'bi-people',                 'label' => __('nav.members'),         'mod' => 'members'],
        'members_all'    => ['url' => 'members-all',                'icon' => 'bi-people-fill',            'label' => 'Wszyscy zawodnicy',       'mod' => 'members'],
        'sports'         => ['url' => 'sports',                     'icon' => 'bi-trophy',                 'label' => __('nav.sports_sections'), 'mod' => 'sports'],
        'calendar'       => ['url' => 'calendar',                   'icon' => 'bi-calendar3',              'label' => __('nav.calendar'),        'mod' => 'calendar'],
        'import'         => ['url' => 'import',                     'icon' => 'bi-upload',                 'label' => __('nav.import_csv'),      'mod' => 'members'],
        'events'         => ['url' => 'events',                     'icon' => 'bi-calendar-event',         'label' => __('nav.events'),          'mod' => 'events'],
        'trainings'      => ['url' => 'trainings',                  'icon' => 'bi-stopwatch',              'label' => __('nav.trainings'),       'mod' => 'trainings'],
        'bookings'       => ['url' => 'bookings',                   'icon' => 'bi-calendar-check',         'label' => __('nav.bookings'),        'mod' => null],
        'resources'      => ['url' => 'club/resources',             'icon' => 'bi-box-seam',               'label' => 'Zasoby do rezerwacji',    'mod' => null],
        'announcements'  => ['url' => 'announcements',              'icon' => 'bi-megaphone',              'label' => __('nav.announcements'),   'mod' => 'announcements'],
        'messages'       => ['url' => 'messages',                   'icon' => 'bi-chat-dots',              'label' => __('nav.messages'),        'mod' => null],
        'fees'           => ['url' => 'fees',                       'icon' => 'bi-cash-coin',              'label' => __('nav.finances'),        'mod' => 'fees'],
        'fees_rates'     => ['url' => 'fees/rates',                 'icon' => 'bi-tag',                    'label' => __('nav.fee_rates'),       'mod' => 'fees'],
        'commissions'    => ['url' => 'club/trainers/commissions',  'icon' => 'bi-cash-coin',              'label' => 'Prowizje trenerów',       'mod' => 'fees'],
        'subscription'   => ['url' => 'club/subscription',          'icon' => 'bi-credit-card-2-front',    'label' => 'Subskrypcja klubu',       'mod' => null],
        'medical'        => ['url' => 'medical',                    'icon' => 'bi-heart-pulse',            'label' => __('nav.medical'),         'mod' => 'medical',          'sensitive' => true],
        'compliance'     => ['url' => 'admin/compliance',           'icon' => 'bi-shield-check',           'label' => 'Zgodność WADA',           'mod' => 'medical',          'sensitive' => true],
        'certifications' => ['url' => 'certifications',             'icon' => 'bi-patch-check',            'label' => 'Uprawnienia trenerskie',  'mod' => null],
        'equipment'      => ['url' => 'equipment',                  'icon' => 'bi-box-seam',               'label' => 'Sprzęt klubowy',          'mod' => null],
        'analytics'      => ['url' => 'analytics',                  'icon' => 'bi-graph-up',               'label' => __('nav.analytics'),       'mod' => null],
        'reports'        => ['url' => 'reports',                    'icon' => 'bi-file-earmark-bar-graph', 'label' => __('nav.reports'),         'mod' => 'reports'],
        'documents'      => ['url' => 'documents',                  'icon' => 'bi-file-earmark-pdf',       'label' => __('nav.documents'),       'mod' => null],
        'gallery'        => ['url' => 'gallery',                    'icon' => 'bi-images',                 'label' => __('nav.gallery'),         'mod' => null],
        'gdpr'           => ['url' => 'gdpr',                       'icon' => 'bi-shield-check',           'label' => __('nav.gdpr'),            'mod' => 'club'],
    ];
    $clubGroups = [
        'core'      => ['label' => __('nav.club'),       'items' => ['dashboard', 'members', 'members_all', 'sports', 'calendar', 'import']],
        'schedule'  => ['label' => 'Działania',          'items' => ['events', 'trainings', 'bookings', 'resources', 'announcements', 'messages']],
        'finance'   => ['label' => 'Finanse',            'items' => ['fees', 'fees_rates', 'commissions', 'subscription']],
        'health'    => ['label' => 'Zdrowie i compliance', 'items' => ['medical', 'compliance', 'certifications', 'equipment']],
        'reports'   => ['label' => 'Raporty i media',    'items' => ['analytics', 'reports', 'documents', 'gallery', 'gdpr']],
    ];
    $allowed = $navModules ?? null;
    $canSensitive = \App\Helpers\Auth::canAccessSensitiveData();
    foreach ($clubGroups as $groupKey => $group):
        // Filter group items by allowed module + sensitive role
        $visible = [];
        foreach ($group['items'] as $k) {
            $item = $allItems[$k] ?? null;
            if (!$item) continue;
            if (!empty($item['sensitive']) && !$canSensitive) continue;
            if ($item['mod'] !== null && $allowed !== null && !in_array($item['mod'], $allowed, true)) continue;
            $visible[$k] = $item;
        }
        if (empty($visible)) continue;
        $sectionId = 'club-' . $groupKey;
    ?>
        <div class="nav-section" data-section="<?= $sectionId ?>">
            <button type="button" class="section-toggle" aria-expanded="true" aria-controls="sec-<?= $sectionId ?>">
                <span class="section-label" style="padding:0;"><?= View::e($group['label']) ?></span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="section-items" id="sec-<?= $sectionId ?>">
            <?php foreach ($visible as $item): ?>
                <a href="<?= url($item['url']) ?>"><i class="bi <?= View::e($item['icon']) ?>"></i> <?= View::e($item['label']) ?></a>
            <?php endforeach; ?>
            <?php if ($groupKey === 'finance' && \App\Helpers\Auth::hasRole(['trener', 'instruktor'])): ?>
                <a href="<?= url('trainer/commissions/my') ?>"><i class="bi bi-wallet2"></i> Moje prowizje</a>
            <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php endif; // end !$isSuperAdminNoClub ?>

    <!-- Z.2 — Recent items (zarządzane przez JS, ukryte gdy puste) -->
    <?php if (!$isSuperAdminNoClub): ?>
        <div class="section-label" id="recent-items-label" style="display:none;">Ostatnio odwiedzane</div>
        <div id="recent-items-list" style="display:none;"></div>
        <script>
            // Pokaż label gdy lista ma items
            document.addEventListener('DOMContentLoaded', function() {
                var list = document.getElementById('recent-items-list');
                var label = document.getElementById('recent-items-label');
                if (list && label && list.children.length > 0) label.style.display = 'block';
            });
        </script>
    <?php endif; ?>

    <?php if (!empty($sportNav)): ?>
        <div class="section-label"><?= __('nav.section') ?>: <?= View::e($activeSportKey) ?></div>
        <?php foreach ($sportNav as $item): ?>
            <a href="<?= url($item['url'] ?? '#') ?>">
                <i class="bi <?= View::e($item['icon'] ?? 'bi-dot') ?>"></i>
                <?= View::e($item['label'] ?? '') ?>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($clubSports)): ?>
        <?php
        // Filtrowanie quick-switch: tylko sporty aktywne (cs_active=1),
        // chyba że admin widok (super admin / zarząd może zobaczyć wszystkie)
        $canSeeInactive = \App\Helpers\Auth::isSuperAdmin() || \App\Helpers\Auth::hasRole('zarzad');
        $visibleSports  = array_filter($clubSports, fn($cs) => $canSeeInactive || (int)($cs['cs_active'] ?? 0) === 1);
        ?>
        <?php if (!empty($visibleSports)): ?>
            <div class="section-label"><?= __('nav.quick_switch') ?></div>
            <?php foreach ($visibleSports as $cs): ?>
                <form method="POST" action="<?= url('sports/activate/' . (int)$cs['club_sport_id']) ?>" class="m-0">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-link text-start w-100 p-2" style="color:rgba(255,255,255,0.85); text-decoration:none;">
                        <i class="bi <?= View::e($cs['icon'] ?? 'bi-dot') ?>"></i>
                        <?= View::e($cs['name']) ?>
                        <?php if ((int)($cs['cs_active'] ?? 0) === 0): ?>
                            <span class="badge bg-secondary ms-1" style="font-size:0.65rem;">wyłączony</span>
                        <?php endif; ?>
                        <?php if (($cs['key'] ?? '') === 'shooting'): ?>
                            <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem;" title="Użyj shotero.pl dla pełnej obsługi PZSS">shotero.pl</span>
                        <?php endif; ?>
                    </button>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php
    // ── FUNKCJE (cross-cutting features widoczne dla wszystkich zalogowanych
    //   uzytkownikow klubu — bez wymogu roli zarzad/admin) ───────────────────
    $hasFeatures = !empty($currentClubId) && (
        \App\Helpers\Feature::enabled('live_score') ||
        \App\Helpers\Feature::enabled('cross_sport_stats')
    );
    if ($hasFeatures): ?>
        <div class="nav-section" data-section="features">
            <button type="button" class="section-toggle" aria-expanded="true" aria-controls="sec-features">
                <span class="section-label" style="padding:0;">Funkcje</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="section-items" id="sec-features">
                <?php if (\App\Helpers\Feature::enabled('live_score')): ?>
                    <a href="<?= url('live') ?>"><i class="bi bi-broadcast"></i> Live updates</a>
                <?php endif; ?>
                <?php if (\App\Helpers\Feature::enabled('cross_sport_stats')): ?>
                    <a href="<?= url('club/cross-sport-overview') ?>"><i class="bi bi-bar-chart-steps"></i> Statystyki cross-sport</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php
    // ── INTEGRACJE — tylko zarzad/admin (zawieraja credentiale wrazliwe) ─────
    if (!empty($currentClubId) && \App\Helpers\Auth::hasRole(['zarzad', 'admin'])): ?>
        <div class="nav-section" data-section="integrations">
            <button type="button" class="section-toggle" aria-expanded="false" aria-controls="sec-integrations">
                <span class="section-label" style="padding:0;">Integracje</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="section-items" id="sec-integrations">
                <a href="<?= url('club/gateways') ?>"><i class="bi bi-credit-card-2-front"></i> Bramki płatności</a>
                <?php if (\App\Helpers\Feature::enabled('inpost_shipping')): ?>
                    <a href="<?= url('club/shipping') ?>"><i class="bi bi-truck"></i> Wysyłka InPost</a>
                    <a href="<?= url('club/shipping/shipments') ?>" style="padding-left:2rem;">
                        <i class="bi bi-list-ul"></i> <small>Przesyłki</small>
                    </a>
                <?php endif; ?>
                <?php if (\App\Helpers\Feature::enabled('federation_export')): ?>
                    <a href="<?= url('club/federations') ?>"><i class="bi bi-globe2"></i> Federacje sportowe</a>
                <?php endif; ?>
                <?php if (\App\Helpers\Feature::enabled('google_calendar_sync')): ?>
                    <a href="<?= url('club/google-calendar') ?>"><i class="bi bi-calendar-event"></i> Google Calendar</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php // Club settings — podzielone na 3 pod-grupy zamiast 11 itemów w ciągu
    if (!empty($currentClubId) && ($allowed === null || in_array('club', $allowed, true))): ?>
        <div class="nav-section" data-section="settings-data">
            <button type="button" class="section-toggle" aria-expanded="false" aria-controls="sec-settings-data">
                <span class="section-label" style="padding:0;">Ustawienia klubu</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="section-items" id="sec-settings-data">
                <a href="<?= url('club/settings') ?>"><i class="bi bi-gear"></i> <?= __('nav.club_data') ?></a>
                <a href="<?= url('club/customization') ?>"><i class="bi bi-palette"></i> <?= __('nav.branding') ?></a>
                <a href="<?= url('club/users') ?>"><i class="bi bi-people-fill"></i> <?= __('nav.users') ?></a>
                <a href="<?= url('club/smtp') ?>"><i class="bi bi-envelope-gear"></i> <?= __('nav.smtp_sms') ?></a>
                <a href="<?= url('email/templates') ?>"><i class="bi bi-file-text"></i> <?= __('nav.email_templates') ?></a>
                <?php if (\App\Helpers\Auth::hasRole(['zarzad', 'admin']) || \App\Helpers\Auth::isSuperAdmin()): ?>
                    <a href="<?= url('admin/audit-log') ?>"><i class="bi bi-clipboard-data"></i> Audyt aktywności</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="nav-section" data-section="settings-billing">
            <button type="button" class="section-toggle" aria-expanded="false" aria-controls="sec-settings-billing">
                <span class="section-label" style="padding:0;">Plan i rozliczenia</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="section-items" id="sec-settings-billing">
                <a href="<?= url('billing/plans') ?>"><i class="bi bi-credit-card-2-front"></i> <?= __('nav.plan_billing') ?></a>
                <a href="<?= url('billing/invoices') ?>"><i class="bi bi-receipt"></i> <?= __('nav.invoices') ?></a>
                <a href="<?= url('members/export') ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Eksport członków</a>
                <a href="<?= url('fees/bulk-assign') ?>"><i class="bi bi-people-fill"></i> Bulk składki</a>
                <a href="<?= url('admin/campaigns') ?>"><i class="bi bi-megaphone"></i> Kampanie email/SMS</a>
            </div>
        </div>
        <div class="nav-section" data-section="settings-dev">
            <button type="button" class="section-toggle" aria-expanded="false" aria-controls="sec-settings-dev">
                <span class="section-label" style="padding:0;">Developer i wsparcie</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="section-items" id="sec-settings-dev">
                <a href="<?= url('club/api-keys') ?>"><i class="bi bi-key"></i> <?= __('nav.api_keys') ?></a>
                <a href="<?= url('club/webhooks') ?>"><i class="bi bi-plug"></i> <?= __('nav.webhooks') ?></a>
                <a href="<?= url('federation') ?>"><i class="bi bi-globe"></i> <?= __('nav.federations') ?></a>
                <a href="<?= url('support') ?>"><i class="bi bi-headset"></i> Wsparcie techniczne</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($isSuperAdmin)): ?>
        <div class="nav-section" data-section="platform-core">
            <button type="button" class="section-toggle" aria-expanded="true" aria-controls="sec-platform-core">
                <span class="section-label" style="padding:0;">Platforma</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="section-items" id="sec-platform-core">
                <a href="<?= url('admin/dashboard') ?>"><i class="bi bi-speedometer2"></i> Pulpit admina</a>
                <a href="<?= url('admin/clubs') ?>"><i class="bi bi-building"></i> <?= __('nav.clubs') ?></a>
                <a href="<?= url('admin/demos') ?>"><i class="bi bi-rocket-takeoff"></i> Konta demo</a>
                <a href="<?= url('admin/users') ?>"><i class="bi bi-shield-fill-check"></i> Super admini</a>
            </div>
        </div>
        <div class="nav-section" data-section="platform-commerce">
            <button type="button" class="section-toggle" aria-expanded="false" aria-controls="sec-platform-commerce">
                <span class="section-label" style="padding:0;">Komercja</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="section-items" id="sec-platform-commerce">
                <a href="<?= url('admin/plans') ?>"><i class="bi bi-credit-card"></i> Plany</a>
                <a href="<?= url('admin/platform/plans') ?>"><i class="bi bi-tags"></i> Plany cenowe</a>
                <a href="<?= url('admin/subscriptions') ?>"><i class="bi bi-wallet2"></i> Subskrypcje</a>
                <a href="<?= url('admin/invoices') ?>"><i class="bi bi-receipt"></i> Faktury</a>
                <a href="<?= url('admin/invoices/bulk') ?>"><i class="bi bi-receipt-cutoff"></i> Bulk faktury</a>
                <a href="<?= url('admin/invoices/jpk-fa') ?>"><i class="bi bi-filetype-xml"></i> JPK_FA</a>
                <a href="<?= url('admin/ads') ?>"><i class="bi bi-badge-ad"></i> Reklamy</a>
            </div>
        </div>
        <div class="nav-section" data-section="platform-config">
            <button type="button" class="section-toggle" aria-expanded="false" aria-controls="sec-platform-config">
                <span class="section-label" style="padding:0;">Konfiguracja platformy</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="section-items" id="sec-platform-config">
                <a href="<?= url('admin/sports/catalog') ?>"><i class="bi bi-grid-3x3-gap"></i> Katalog sportów</a>
                <a href="<?= url('admin/platform/feature-flags') ?>"><i class="bi bi-toggles2"></i> Feature flags</a>
                <a href="<?= url('admin/platform/system-branding') ?>"><i class="bi bi-image"></i> Logo systemu</a>
                <a href="<?= url('admin/platform/support') ?>"><i class="bi bi-headset"></i> Support tickets</a>
                <a href="<?= url('admin/activity') ?>"><i class="bi bi-clock-history"></i> Log aktywności</a>
                <a href="<?= url('admin/backups') ?>"><i class="bi bi-hdd"></i> Kopie zapasowe</a>
            </div>
        </div>
        <div class="nav-section" data-section="platform-security">
            <button type="button" class="section-toggle" aria-expanded="false" aria-controls="sec-platform-security">
                <span class="section-label" style="padding:0;">Bezpieczeństwo i monitoring</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="section-items" id="sec-platform-security">
                <a href="<?= url('admin/errors') ?>"><i class="bi bi-bug-fill"></i> Dziennik błędów</a>
                <a href="<?= url('admin/security') ?>"><i class="bi bi-shield-lock-fill"></i> Dziennik bezpieczeństwa</a>
                <a href="<?= url('admin/audit/isolation') ?>"><i class="bi bi-shield-check"></i> Audyt izolacji</a>
                <a href="<?= url('admin/platform/audit-log') ?>"><i class="bi bi-clipboard-data"></i> Audit log (wszystkie kluby)</a>
                <a href="<?= url('admin/health') ?>"><i class="bi bi-heart-pulse"></i> Zdrowie systemu</a>
            </div>
        </div>

        <?php if (!empty($currentClubId)): ?>
        <div class="nav-section" data-section="platform-current-club">
            <button type="button" class="section-toggle" aria-expanded="false" aria-controls="sec-platform-current-club">
                <span class="section-label" style="padding:0;">Bieżący klub</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="section-items" id="sec-platform-current-club">
                <a href="<?= url('admin/clubs/' . (int)$currentClubId . '/config') ?>"><i class="bi bi-sliders"></i> Konfiguracja</a>
                <a href="<?= url('admin/clubs/' . (int)$currentClubId . '/features') ?>"><i class="bi bi-toggles"></i> Feature flags</a>
                <a href="<?= url('admin/clubs/' . (int)$currentClubId . '/permissions') ?>"><i class="bi bi-key-fill"></i> Uprawnienia</a>
                <a href="<?= url('admin/clubs/' . (int)$currentClubId . '/sports') ?>"><i class="bi bi-trophy"></i> Sporty (config)</a>
                <a href="<?= url('admin/platform/branding/' . (int)$currentClubId) ?>"><i class="bi bi-palette2"></i> Branding klubu</a>
                <a href="<?= url('admin/clubs/' . (int)$currentClubId . '/analytics') ?>"><i class="bi bi-bar-chart-line"></i> Analityka klubu</a>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="section-label"><?= __('nav.account') ?></div>
    <?php if (!empty($authUser)): ?>
        <div class="px-3 py-2 small" style="color:rgba(255,255,255,0.55);">
            <i class="bi bi-person-circle"></i> <?= View::e($authUser['full_name'] ?? $authUser['username'] ?? '') ?>
        </div>
    <?php endif; ?>
    <a href="<?= url('2fa/setup') ?>"><i class="bi bi-shield-lock"></i> <?= __('nav.2fa_totp') ?></a>
    <a href="#" id="dark-mode-toggle"><i class="bi bi-moon"></i> <?= __('misc.dark_mode') ?></a>
    <div class="px-3 py-2 small d-flex align-items-center gap-2" style="color:rgba(255,255,255,0.55); border-left:3px solid transparent;">
        <i class="bi bi-translate"></i>
        <a href="?lang=pl" style="color:rgba(255,255,255,0.85); text-decoration:none; <?= \App\Helpers\Translator::getLocale() === 'pl' ? 'font-weight:700;' : 'opacity:.6;' ?>">PL</a>
        <span style="opacity:.3;">|</span>
        <a href="?lang=en" style="color:rgba(255,255,255,0.85); text-decoration:none; <?= \App\Helpers\Translator::getLocale() === 'en' ? 'font-weight:700;' : 'opacity:.6;' ?>">EN</a>
    </div>
    <a href="<?= url('help') ?>"><i class="bi bi-question-circle"></i> Pomoc</a>
    <a href="<?= url('auth/logout') ?>"><i class="bi bi-box-arrow-right"></i> <?= __('nav.logout') ?></a>
</nav>

<main class="main-content">
    <!-- Global search bar (Z.1: Ctrl+K shortcut, arrow nav) -->
    <div class="search-wrapper mb-3" style="max-width:480px;">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="global-search-input" class="form-control"
                   placeholder="<?= View::e(__('common.search_placeholder')) ?>">
            <span class="input-group-text small text-muted" title="Skrót klawiaturowy"
                  style="font-family: monospace; font-size: 11px;">Ctrl+K</span>
        </div>
        <div id="global-search-dropdown" class="search-dropdown"></div>
    </div>
    <?php if (!empty($isImpersonating)): ?>
        <div class="alert alert-warning d-flex justify-content-between align-items-center mb-3">
            <div>
                <i class="bi bi-person-fill-lock"></i>
                <?php if (($impersonatingType ?? '') === 'club_context'): ?>
                    <strong>Przeglądasz kontekst klubu:</strong> <?= View::e($currentClub['name'] ?? '') ?>
                <?php else: ?>
                    <strong><?= __('impersonate.label') ?></strong> <?= View::e($authUser['full_name'] ?? '') ?>
                    <?php if (!empty($authUser['email'])): ?>
                        (<?= View::e($authUser['email']) ?>)
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <form method="POST" action="<?= url('impersonate/stop') ?>" class="m-0">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-warning"><i class="bi bi-arrow-return-left"></i> Powrót do Master Admin</button>
            </form>
        </div>
    <?php endif; ?>
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
                        <h6 class="dropdown-header"><?= __('notif.notifications') ?> (<?= (int)$unreadNotifsCount ?>)</h6>
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
<script src="<?= url('js/search.js') ?>"></script>
<script src="<?= url('js/recent-items.js') ?>"></script>
<script src="<?= url('js/dark-mode.js') ?>"></script>
<script>
// Hamburger sidebar toggle
(function() {
    var btn = document.getElementById('hamburger-btn');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    if (!btn || !sidebar) return;
    btn.addEventListener('click', function() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
    });
    if (overlay) overlay.addEventListener('click', function() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    });
})();
// Z.3 — Sidebar sections collapse z persystencja w localStorage
(function() {
    var STORAGE_KEY = 'cd_nav_collapsed';
    var saved;
    try { saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); }
    catch (e) { saved = {}; }
    var sections = document.querySelectorAll('.sidebar .nav-section');
    sections.forEach(function(sec) {
        var key = sec.dataset.section;
        var btn = sec.querySelector('.section-toggle');
        if (!key || !btn) return;
        // Apply saved state — overrides default aria-expanded
        if (Object.prototype.hasOwnProperty.call(saved, key)) {
            btn.setAttribute('aria-expanded', saved[key] ? 'true' : 'false');
        }
        btn.addEventListener('click', function() {
            var expanded = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            saved[key] = !expanded;
            try { localStorage.setItem(STORAGE_KEY, JSON.stringify(saved)); } catch (e) {}
        });
    });
    // Auto-expand sekcji zawierajacej aktywny link (jesli zwiniete)
    var here = window.location.pathname.replace(/^\/+|\/+$/g, '');
    sections.forEach(function(sec) {
        var hit = Array.prototype.some.call(sec.querySelectorAll('a[href]'), function(a) {
            var target = a.getAttribute('href') || '';
            target = target.replace(/^https?:\/\/[^\/]+/, '').replace(/^\/+|\/+$/g, '');
            return target && target === here;
        });
        if (hit) {
            var btn = sec.querySelector('.section-toggle');
            if (btn && btn.getAttribute('aria-expanded') === 'false') {
                btn.setAttribute('aria-expanded', 'true');
            }
        }
    });
})();
// Service Worker
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js').catch(function(err) {
    console.log('SW registration failed:', err);
  });
}
</script>
<script src="<?= url('js/cookie-consent.js') ?>"></script>

<!-- Floating "Zglos problem" button (support_reports + Todoist sync) -->
<a href="<?= url('support/report?return=' . urlencode($_SERVER['REQUEST_URI'] ?? '/')) ?>"
   class="support-fab"
   title="Zglos blad lub propozycje">
    <i class="bi bi-bug"></i>
</a>
<style>
.support-fab {
    position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 1040;
    width: 56px; height: 56px; border-radius: 50%;
    background: #f59e0b; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,.2);
    text-decoration: none; transition: transform .15s ease;
}
.support-fab:hover { transform: scale(1.1); color: #fff; }
@media (max-width: 768px) { .support-fab { bottom: 1rem; right: 1rem; width: 48px; height: 48px; font-size: 1.25rem; } }
</style>
</body>
</html>
