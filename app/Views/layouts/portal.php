<?php
use App\Helpers\ClubBranding;
use App\Helpers\MemberAuth;
use App\Helpers\Session;
use App\Helpers\View;
$flashSuccess = Session::getFlash('success');
$flashError   = Session::getFlash('error');
$memberName   = Session::get('portal_member_name');

// PWA branding (per-klub via ClubContext / subdomena)
$pwaBranding     = ClubBranding::current();
$pwaThemeColor   = $pwaBranding->primaryColor();
$pwaAppleIconUrl = $pwaBranding->appleTouchIconUrl();

// Unread notification count (only when logged in)
$unreadNotifCount = 0;
if (MemberAuth::check() && MemberAuth::id() && MemberAuth::clubId()) {
    try {
        $unreadNotifCount = (new \App\Models\MemberNotificationModel())
            ->countUnread((int)MemberAuth::id(), (int)MemberAuth::clubId());
    } catch (\Throwable) {}
}

// Cross-club sport section switcher: lista wszystkich aktywnych sekcji
// dla zalogowanej tozsamosci (B1). Bezpieczne dla legacy logowan
// (identityId === null → brak listy → brak dropdownu).
$portalSections      = [];
$activeSection       = null;
$activeMembershipId  = MemberAuth::activeMembershipId();
$identityIdForNav    = MemberAuth::identityId();
if ($identityIdForNav !== null) {
    try {
        $portalSections = (new \App\Models\IdentitySportMembershipModel())->forIdentity($identityIdForNav);
        if ($activeMembershipId !== null) {
            foreach ($portalSections as $s) {
                if ((int)$s['id'] === $activeMembershipId) { $activeSection = $s; break; }
            }
        }
    } catch (\Throwable) {}
}

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$isActive = fn(string $seg): string => str_contains($currentPath ?? '', $seg) ? 'fw-semibold text-decoration-underline' : '';
?><!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="<?= View::e($pwaThemeColor) ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ClubDesk">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/portal/manifest.json">
    <link rel="apple-touch-icon" href="<?= View::e($pwaAppleIconUrl) ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= View::e($pwaAppleIconUrl) ?>">
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
                <img src="<?= View::e(system_logo('white')) ?>" alt="CD" style="height:28px;">
                <strong class="text-white">Portal zawodnika</strong>
            </div>
            <div class="d-flex align-items-center gap-3">
                <?php if (count($portalSections) > 1): ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-light dropdown-toggle py-0 px-2" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false" title="Aktywna sekcja">
                        <i class="bi bi-shuffle me-1"></i>
                        <?php if ($activeSection): ?>
                            <?= View::e($activeSection['sport_name'] ?? $activeSection['sport_key']) ?>
                            <span class="text-white-50">·</span>
                            <?= View::e($activeSection['club_short_name'] ?? $activeSection['club_name']) ?>
                        <?php else: ?>
                            Wybierz sekcję
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Twoje sekcje sportowe</h6></li>
                        <?php foreach ($portalSections as $s):
                            $isActiveItem = $activeMembershipId !== null && (int)$s['id'] === $activeMembershipId;
                        ?>
                            <li>
                                <form method="POST" action="<?= url('portal/switch-section/' . (int)$s['id']) ?>" class="m-0">
                                    <?= csrf_field() ?>
                                    <button type="submit"
                                            class="dropdown-item d-flex align-items-center gap-2 <?= $isActiveItem ? 'active' : '' ?>">
                                        <i class="bi <?= View::e($s['sport_icon'] ?? 'bi-trophy') ?>"
                                           style="color: <?= View::e($s['sport_color'] ?? '#0d6efd') ?>"></i>
                                        <span class="flex-grow-1">
                                            <?= View::e($s['sport_name'] ?? $s['sport_key']) ?>
                                            <small class="text-muted d-block"><?= View::e($s['club_name']) ?></small>
                                        </span>
                                        <?php if ((int)$s['is_primary'] === 1): ?>
                                            <i class="bi bi-star-fill text-warning small" title="Primary"></i>
                                        <?php endif; ?>
                                    </button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php if ($unreadNotifCount > 0): ?>
                <a href="<?= url('portal/notifications') ?>" class="position-relative" title="Powiadomienia">
                    <i class="bi bi-bell-fill fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.65rem;">
                        <?= $unreadNotifCount ?>
                    </span>
                </a>
                <?php endif; ?>
                <span class="small text-white-50"><i class="bi bi-person-circle me-1"></i><?= View::e($memberName ?? '') ?></span>
                <button type="button" id="pwa-install-btn" class="btn btn-light btn-sm py-0 px-2" style="display:none;" title="Zainstaluj jako aplikacje">
                    <i class="bi bi-download"></i> <span class="d-none d-md-inline">Zainstaluj</span>
                </button>
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
            <a href="<?= url('portal/dashboard/cross-sport') ?>" class="<?= $isActive('cross-sport') ?>">
                <i class="bi bi-bar-chart-steps me-1"></i>Cross-sport
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
            <a href="<?= url('help') ?>">
                <i class="bi bi-question-circle me-1"></i>Pomoc
            </a>
            <?php
            $sportKeys = ['bjj','gymnastics','floorball','padel','sailing','triathlon','crossfit',
                          'swimming','tennis','boxing','handball','cycling','icehockey','fencing',
                          'taekwondo','weightlifting','climbing',
                          // X1-X13 (droga do 50)
                          'rugby','alpineski','xcski','skijump','snowboard','figureskating',
                          'biathlon','kickboxing','mma','kayaking','golf','bridge','fieldhockey'];
            $sportLabels = [
                'bjj'=>'BJJ','gymnastics'=>'Gimnastyka','floorball'=>'Floorball',
                'padel'=>'Padel','sailing'=>'Żeglarstwo','triathlon'=>'Triathlon','crossfit'=>'CrossFit',
                'swimming'=>'Pływanie','tennis'=>'Tenis','boxing'=>'Boks','handball'=>'P.ręczna',
                'cycling'=>'Kolarstwo','icehockey'=>'Hokej','fencing'=>'Szermierka',
                'taekwondo'=>'Taekwondo','weightlifting'=>'Ciężary','climbing'=>'Wspinaczka',
                // X1-X13
                'rugby'=>'Rugby','alpineski'=>'Nart.alp.','xcski'=>'Nart.bieg.',
                'skijump'=>'Skoki','snowboard'=>'Snowboard','figureskating'=>'Łyż.fig.',
                'biathlon'=>'Biathlon','kickboxing'=>'Kickbox','mma'=>'MMA',
                'kayaking'=>'Kajak','golf'=>'Golf','bridge'=>'Brydż','fieldhockey'=>'Hok.trawa',
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

<!-- iOS install instructions modal (Safari nie wspiera beforeinstallprompt) -->
<div class="modal fade" id="pwaIosModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-phone"></i> Zainstaluj aplikacj&#281;</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
      </div>
      <div class="modal-body">
        <p>Aby zainstalowa&#263; portal jako aplikacj&#281; na iPhone/iPad:</p>
        <ol class="mb-3">
          <li>Dotknij ikony <i class="bi bi-box-arrow-up"></i> <strong>Udost&#281;pnij</strong> na dole ekranu Safari.</li>
          <li>Wybierz <strong>&bdquo;Do ekranu pocz&#261;tkowego&rdquo;</strong> (Add to Home Screen).</li>
          <li>Potwierd&#378; przyciskiem <strong>Dodaj</strong> w prawym g&oacute;rnym rogu.</li>
        </ol>
        <p class="text-muted small mb-0">Po dodaniu znajdziesz ikon&#281; aplikacji na ekranie pocz&#261;tkowym &mdash; b&#281;dzie dzia&#322;a&#263; jak natywna aplikacja.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Rozumiem</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  // ── Service Worker registration ─────────────────────────
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/portal/sw.js', { scope: '/' })
        .then(function (reg) {
          // Trigger update check on each load
          if (reg && typeof reg.update === 'function') {
            try { reg.update(); } catch (e) {}
          }
        })
        .catch(function (err) {
          console.warn('[PWA] SW registration failed:', err);
        });
    });
  }

  // ── Install prompt (Chrome / Edge / Android Chrome) ────
  var deferredPrompt = null;
  var installBtn = document.getElementById('pwa-install-btn');

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    if (installBtn) installBtn.style.display = 'inline-block';
  });

  if (installBtn) {
    installBtn.addEventListener('click', function () {
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then(function (choice) {
        if (choice && choice.outcome === 'accepted') {
          installBtn.style.display = 'none';
        }
        deferredPrompt = null;
      });
    });
  }

  window.addEventListener('appinstalled', function () {
    if (installBtn) installBtn.style.display = 'none';
    deferredPrompt = null;
  });

  // ── iOS detection (Safari, no beforeinstallprompt support) ──
  var isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
  var isStandalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) ||
                     window.navigator.standalone === true;
  if (isIos && !isStandalone && installBtn) {
    // Show install button on iOS — but route to modal instead of native prompt
    installBtn.style.display = 'inline-block';
    installBtn.addEventListener('click', function (ev) {
      if (!deferredPrompt) {
        ev.preventDefault();
        ev.stopImmediatePropagation();
        try {
          var modalEl = document.getElementById('pwaIosModal');
          if (modalEl && window.bootstrap) {
            new bootstrap.Modal(modalEl).show();
          }
        } catch (e) {}
      }
    }, true);
  }
})();
</script>
<script src="<?= url('js/cookie-consent.js') ?>"></script>
</body>
</html>
