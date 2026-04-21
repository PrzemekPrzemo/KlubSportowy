<?php
use App\Helpers\MemberAuth;
use App\Helpers\Session;
use App\Helpers\View;
$flashSuccess = Session::getFlash('success');
$flashError   = Session::getFlash('error');
$memberName   = Session::get('portal_member_name');

// Unread notification count (only when logged in)
$unreadNotifCount = 0;
if (MemberAuth::check() && MemberAuth::id() && MemberAuth::clubId()) {
    try {
        $unreadNotifCount = (new \App\Models\MemberNotificationModel())
            ->countUnread((int)MemberAuth::id(), (int)MemberAuth::clubId());
    } catch (\Throwable) {}
}

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$isActive = fn(string $seg): string => str_contains($currentPath ?? '', $seg) ? 'fw-semibold text-decoration-underline' : '';
?><!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#EE2C28">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/manifest.json">
    <title><?= View::e($title ?? 'Portal zawodnika') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Poppins', system-ui, sans-serif; background:#f0f2f5; }
        .portal-nav { background: #232232; border-bottom: 3px solid #EE2C28; color:#fff; }
        .portal-nav a { color:rgba(255,255,255,.85); text-decoration:none; }
        .portal-nav a:hover { color:#fff; }
        .portal-container { max-width: 1200px; margin: 1.5rem auto; padding: 0 1rem; }
        .nav-section { font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; color:rgba(255,255,255,.4); margin-top:.4rem; }
    </style>
</head>
<body>
<nav class="portal-nav">
    <div class="container-fluid px-3 px-md-4">
        <!-- Top bar -->
        <div class="d-flex justify-content-between align-items-center py-2">
            <div class="d-flex align-items-center gap-2">
                <img src="/images/logo-cd-white.svg" alt="CD" style="height:28px;">
                <strong class="text-white">Portal zawodnika</strong>
            </div>
            <div class="d-flex align-items-center gap-3">
                <?php if ($unreadNotifCount > 0): ?>
                <a href="<?= url('portal/notifications') ?>" class="position-relative" title="Powiadomienia">
                    <i class="bi bi-bell-fill fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.65rem;">
                        <?= $unreadNotifCount ?>
                    </span>
                </a>
                <?php endif; ?>
                <span class="small text-white-50"><i class="bi bi-person-circle me-1"></i><?= View::e($memberName ?? '') ?></span>
                <a href="<?= url('portal/logout') ?>" class="btn btn-outline-light btn-sm py-0 px-2">
                    <i class="bi bi-box-arrow-right"></i> Wyloguj
                </a>
            </div>
        </div>
        <!-- Nav links -->
        <div class="d-flex flex-wrap gap-2 pb-2 align-items-end" style="font-size:.88rem;">
            <a href="<?= url('portal/dashboard') ?>" class="<?= $isActive('dashboard') ?>">
                <i class="bi bi-house me-1"></i>Dashboard
            </a>
            <a href="<?= url('portal/member-card') ?>" class="<?= $isActive('member-card') ?>">
                <i class="bi bi-person-badge me-1"></i>Karta zawodnika
            </a>
            <a href="<?= url('portal/profile') ?>" class="<?= $isActive('profile') ?>">
                <i class="bi bi-person me-1"></i>Profil
            </a>
            <span class="text-white-50">|</span>
            <a href="<?= url('portal/announcements') ?>" class="<?= $isActive('announcements') ?>">
                <i class="bi bi-megaphone me-1"></i>Ogłoszenia
            </a>
            <a href="<?= url('portal/schedule') ?>" class="<?= $isActive('schedule') ?>">
                <i class="bi bi-calendar3 me-1"></i>Plan treningów
            </a>
            <a href="<?= url('portal/events') ?>" class="<?= $isActive('events') ?>">
                <i class="bi bi-calendar-event me-1"></i>Wydarzenia
            </a>
            <a href="<?= url('portal/tournaments') ?>" class="<?= $isActive('tournaments') ?>">
                <i class="bi bi-trophy me-1"></i>Zawody
            </a>
            <span class="text-white-50">|</span>
            <a href="<?= url('portal/attendance') ?>" class="<?= $isActive('attendance') ?>">
                <i class="bi bi-list-check me-1"></i>Frekwencja
            </a>
            <a href="<?= url('portal/results') ?>" class="<?= $isActive('results') ?>">
                <i class="bi bi-bar-chart me-1"></i>Wyniki
            </a>
            <a href="<?= url('portal/belts') ?>" class="<?= $isActive('belts') ?>">
                <i class="bi bi-award me-1"></i>Pasy
            </a>
            <a href="<?= url('portal/sport-history') ?>" class="<?= $isActive('sport-history') ?>">
                <i class="bi bi-clock-history me-1"></i>Historia
            </a>
            <?php
            $sportKeys = ['bjj','gymnastics','floorball','padel','sailing','triathlon','crossfit',
                          'swimming','tennis','boxing','handball','cycling','icehockey','fencing',
                          'taekwondo','weightlifting','climbing'];
            $sportLabels = [
                'bjj'=>'BJJ','gymnastics'=>'Gimnastyka','floorball'=>'Floorball',
                'padel'=>'Padel','sailing'=>'Żeglarstwo','triathlon'=>'Triathlon','crossfit'=>'CrossFit',
                'swimming'=>'Pływanie','tennis'=>'Tenis','boxing'=>'Boks','handball'=>'P.ręczna',
                'cycling'=>'Kolarstwo','icehockey'=>'Hokej','fencing'=>'Szermierka',
                'taekwondo'=>'Taekwondo','weightlifting'=>'Ciężary','climbing'=>'Wspinaczka',
            ];
            foreach ($sportKeys as $sk):
                $activeSports = $activeSports ?? [];
                if (!empty($activeSports) && !in_array($sk, $activeSports)) continue;
            ?>
                <a href="<?= url('portal/sport/' . $sk) ?>"
                   class="<?= strpos($_SERVER['REQUEST_URI'] ?? '', '/portal/sport/' . $sk) !== false ? 'active' : '' ?>">
                    <i class="bi bi-lightning-charge me-1"></i><?= $sportLabels[$sk] ?>
                </a>
            <?php endforeach; ?>
            <span class="text-white-50">|</span>
            <a href="<?= url('portal/fees') ?>" class="<?= $isActive('fees') ?>">
                <i class="bi bi-receipt me-1"></i>Składki
            </a>
            <a href="<?= url('portal/payments') ?>" class="<?= $isActive('payments') ?>">
                <i class="bi bi-credit-card me-1"></i>Opłać online
            </a>
            <span class="text-white-50">|</span>
            <a href="<?= url('portal/body-metrics') ?>" class="<?= $isActive('body-metrics') ?>">
                <i class="bi bi-activity me-1"></i>Pomiary
            </a>
            <a href="<?= url('portal/training-log') ?>" class="<?= $isActive('training-log') ?>">
                <i class="bi bi-journal-bookmark me-1"></i>Dziennik
            </a>
            <a href="<?= url('portal/emergency-contacts') ?>" class="<?= $isActive('emergency-contacts') ?>">
                <i class="bi bi-telephone-fill me-1"></i>Kontakt awaryjny
            </a>
            <a href="<?= url('portal/medical') ?>" class="<?= $isActive('medical') ?>">
                <i class="bi bi-heart-pulse me-1"></i>Badania
            </a>
            <a href="<?= url('portal/licenses') ?>" class="<?= $isActive('licenses') ?>">
                <i class="bi bi-patch-check me-1"></i>Licencje
            </a>
            <a href="<?= url('portal/consents') ?>" class="<?= $isActive('consents') ?>">
                <i class="bi bi-shield-check me-1"></i>RODO
            </a>
            <a href="<?= url('portal/notifications') ?>" class="<?= $isActive('notifications') ?>">
                <i class="bi bi-bell me-1"></i>Powiadomienia
                <?php if ($unreadNotifCount > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?= $unreadNotifCount ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</nav>

<div class="portal-container">
    <?php if (\App\Helpers\Auth::isImpersonating() && \App\Helpers\Session::get('impersonating') === 'member'): ?>
        <div class="alert alert-warning d-flex justify-content-between align-items-center mb-3">
            <div>
                <i class="bi bi-person-fill-lock"></i>
                <strong>Impersonujesz zawodnika</strong> <?= View::e($memberName ?? '') ?>
            </div>
            <form method="POST" action="<?= url('impersonate/stop') ?>" class="m-0">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-warning"><i class="bi bi-arrow-return-left"></i> Powrót do admina</button>
            </form>
        </div>
    <?php endif; ?>

    <h2 class="mb-3"><?= View::e($title ?? '') ?></h2>

    <?php if ($flashSuccess): ?>
        <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-danger"><?= View::e($flashError) ?></div>
    <?php endif; ?>

    <?= $content ?? '' ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js').catch(function(err) {
    console.log('SW registration failed:', err);
  });
}
</script>
<script src="<?= url('js/cookie-consent.js') ?>"></script>
</body>
</html>
