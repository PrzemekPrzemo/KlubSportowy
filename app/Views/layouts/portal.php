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
$unreadChatCount  = 0;
if (MemberAuth::check() && MemberAuth::id() && MemberAuth::clubId()) {
    try {
        $unreadNotifCount = (new \App\Models\MemberNotificationModel())
            ->countUnread((int)MemberAuth::id(), (int)MemberAuth::clubId());
    } catch (\Throwable) {}
    try {
        $unreadChatCount = (new \App\Models\MessageThreadModel())
            ->totalUnreadForMember((int)MemberAuth::id(), (int)MemberAuth::clubId());
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
<html lang="<?= \App\Helpers\Translator::getLocale() ?>">
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
    <title><?= View::e($title ?? __('portal.title')) ?></title>
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
                <strong class="text-white"><?= __('portal.title') ?></strong>
            </div>
            <div class="d-flex align-items-center gap-3">
                <?php if (count($portalSections) > 1): ?>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-light dropdown-toggle py-0 px-2" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false" title="<?= View::e(__('portal.active_section')) ?>">
                        <i class="bi bi-shuffle me-1"></i>
                        <?php if ($activeSection): ?>
                            <?= View::e($activeSection['sport_name'] ?? $activeSection['sport_key']) ?>
                            <span class="text-white-50">·</span>
                            <?= View::e($activeSection['club_short_name'] ?? $activeSection['club_name']) ?>
                        <?php else: ?>
                            <?= __('portal.choose_section') ?>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header"><?= __('portal.your_sport_sections') ?></h6></li>
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
                <a href="<?= url('portal/notifications') ?>" class="position-relative" title="<?= View::e(__('portal.notifications')) ?>">
                    <i class="bi bi-bell-fill fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.65rem;">
                        <?= $unreadNotifCount ?>
                    </span>
                </a>
                <?php endif; ?>
                <span class="small text-white-50"><i class="bi bi-person-circle me-1"></i><?= View::e($memberName ?? '') ?></span>
                <button type="button" id="pwa-install-btn" class="btn btn-light btn-sm py-0 px-2" style="display:none;" title="<?= View::e(__('portal.install_app')) ?>">
                    <i class="bi bi-download"></i> <span class="d-none d-md-inline"><?= __('portal.install') ?></span>
                </button>
                <span class="small d-none d-md-inline">
                    <a href="?lang=pl" class="text-white-50" style="<?= \App\Helpers\Translator::getLocale() === 'pl' ? 'font-weight:700;color:#fff!important;' : 'opacity:.7;' ?>">PL</a>
                    <span class="text-white-50">|</span>
                    <a href="?lang=en" class="text-white-50" style="<?= \App\Helpers\Translator::getLocale() === 'en' ? 'font-weight:700;color:#fff!important;' : 'opacity:.7;' ?>">EN</a>
                </span>
                <a href="<?= url('portal/logout') ?>" class="btn btn-outline-light btn-sm py-0 px-2">
                    <i class="bi bi-box-arrow-right"></i> <?= __('portal.logout') ?>
                </a>
            </div>
        </div>
        <!-- Nav links -->
        <div class="d-flex flex-wrap gap-2 pb-2 align-items-end" style="font-size:.88rem;">
            <a href="<?= url('portal/dashboard') ?>" class="<?= $isActive('dashboard') ?>">
                <i class="bi bi-house me-1"></i><?= __('portal.nav.dashboard') ?>
            </a>
            <a href="<?= url('portal/dashboard/cross-sport') ?>" class="<?= $isActive('cross-sport') ?>">
                <i class="bi bi-bar-chart-steps me-1"></i><?= __('portal.nav.cross_sport') ?>
            </a>
            <a href="<?= url('portal/member-card') ?>" class="<?= $isActive('member-card') ?>">
                <i class="bi bi-person-badge me-1"></i><?= __('portal.nav.member_card') ?>
            </a>
            <a href="<?= url('portal/profile') ?>" class="<?= $isActive('profile') ?>">
                <i class="bi bi-person me-1"></i><?= __('portal.nav.profile') ?>
            </a>
            <span class="text-white-50">|</span>
            <a href="<?= url('portal/announcements') ?>" class="<?= $isActive('announcements') ?>">
                <i class="bi bi-megaphone me-1"></i><?= __('portal.nav.announcements') ?>
            </a>
            <a href="<?= url('portal/messenger') ?>" class="<?= $isActive('messenger') ?>">
                <i class="bi bi-chat-dots me-1"></i>Wiadomosci
                <?php if ($unreadChatCount > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?= (int)$unreadChatCount ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= url('portal/schedule') ?>" class="<?= $isActive('schedule') ?>">
                <i class="bi bi-calendar3 me-1"></i><?= __('portal.nav.schedule') ?>
            </a>
            <a href="<?= url('portal/events') ?>" class="<?= $isActive('events') ?>">
                <i class="bi bi-calendar-event me-1"></i><?= __('portal.nav.events') ?>
            </a>
            <a href="<?= url('portal/tournaments') ?>" class="<?= $isActive('tournaments') ?>">
                <i class="bi bi-trophy me-1"></i><?= __('portal.nav.tournaments') ?>
            </a>
            <?php
            $showBookings = false;
            try {
                $portalClubId = \App\Helpers\MemberAuth::clubId();
                if ($portalClubId !== null) {
                    $showBookings = (new \App\Models\BookableResourceModel())->hasActiveForClub((int)$portalClubId);
                }
            } catch (\Throwable) {}
            if ($showBookings):
            ?>
            <a href="<?= url('portal/bookings') ?>" class="<?= $isActive('bookings') ?>">
                <i class="bi bi-calendar-check me-1"></i><?= __('portal.nav.bookings') ?>
            </a>
            <?php endif; ?>
            <span class="text-white-50">|</span>
            <a href="<?= url('portal/attendance') ?>" class="<?= $isActive('attendance') ?>">
                <i class="bi bi-list-check me-1"></i><?= __('portal.nav.attendance') ?>
            </a>
            <a href="<?= url('portal/results') ?>" class="<?= $isActive('results') ?>">
                <i class="bi bi-bar-chart me-1"></i><?= __('portal.nav.results') ?>
            </a>
            <a href="<?= url('portal/belts') ?>" class="<?= $isActive('belts') ?>">
                <i class="bi bi-award me-1"></i><?= __('portal.nav.belts') ?>
            </a>
            <a href="<?= url('portal/achievements') ?>" class="<?= $isActive('achievements') ?>">
                <i class="bi bi-trophy-fill me-1"></i>Moje osiągnięcia
            </a>
            <a href="<?= url('portal/sport-history') ?>" class="<?= $isActive('sport-history') ?>">
                <i class="bi bi-clock-history me-1"></i><?= __('portal.nav.history') ?>
            </a>
            <a href="<?= url('help/member') ?>" class="<?= $isActive('help/member') ?>">
                <i class="bi bi-question-circle me-1"></i><?= __('portal.nav.help') ?>
            </a>
            <?php
            $sportKeys = ['bjj','gymnastics','floorball','padel','sailing','triathlon','crossfit',
                          'swimming','tennis','boxing','handball','cycling','icehockey','fencing',
                          'taekwondo','weightlifting','climbing',
                          // X1-X13 (droga do 50)
                          'rugby','alpineski','xcski','skijump','snowboard','figureskating',
                          'biathlon','kickboxing','mma','kayaking','golf','bridge','fieldhockey',
                          'esport'];
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
                'esport'=>'E-sport',
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
            <?php
            // ── Migracja 105 — sport-specific link "Moje wyniki/PB" (timing/strength).
            $timingKeys   = ['swimming','cycling','rowing','triathlon','biathlon','alpineski',
                             'xcski','skijump','snowboard','rollerskating','kayaking'];
            $strengthKeys = ['powerlifting','strongman','weightlifting'];
            $primaryTiming   = null;
            $primaryStrength = null;
            if (!empty($activeSports)) {
                foreach ($timingKeys as $tk) {
                    if (in_array($tk, $activeSports, true)) { $primaryTiming = $tk; break; }
                }
                foreach ($strengthKeys as $stk) {
                    if (in_array($stk, $activeSports, true)) { $primaryStrength = $stk; break; }
                }
            }
            // Studio sports — link "Zajecia / Karnety" widoczny gdy klub ma yoga/fitness/pilates
            $studioActive = false;
            try {
                $portalClubIdStudio = \App\Helpers\MemberAuth::clubId();
                if ($portalClubIdStudio !== null) {
                    $stmtStudio = \App\Helpers\Database::pdo()->prepare(
                        "SELECT COUNT(*) FROM club_sports cs
                         JOIN sports s ON s.id = cs.sport_id
                         WHERE cs.club_id = ? AND cs.is_active = 1
                           AND s.`key` IN ('yoga','fitness','pilates')"
                    );
                    $stmtStudio->execute([$portalClubIdStudio]);
                    $studioActive = ((int)$stmtStudio->fetchColumn()) > 0;
                }
            } catch (\Throwable) {}
            ?>
            <?php if ($primaryTiming): ?>
                <a href="<?= url('portal/sport/' . $primaryTiming . '/my_results') ?>"
                   class="<?= strpos($_SERVER['REQUEST_URI'] ?? '', '/my_results') !== false ? 'active' : '' ?>">
                    <i class="bi bi-stopwatch me-1"></i>Moje wyniki
                </a>
            <?php endif; ?>
            <?php if ($primaryStrength): ?>
                <a href="<?= url('portal/sport/' . $primaryStrength . '/my_pbs') ?>"
                   class="<?= strpos($_SERVER['REQUEST_URI'] ?? '', '/my_pbs') !== false ? 'active' : '' ?>">
                    <i class="bi bi-shield-shaded me-1"></i>Moje PB
                </a>
            <?php endif; ?>
            <?php if ($studioActive): ?>
                <a href="<?= url('portal/studio') ?>" class="<?= $isActive('/studio') ?>">
                    <i class="bi bi-flower1 me-1"></i>Zajecia / Karnety
                </a>
            <?php endif; ?>
            <span class="text-white-50">|</span>
            <a href="<?= url('portal/fees') ?>" class="<?= $isActive('fees') ?>">
                <i class="bi bi-receipt me-1"></i><?= __('portal.nav.fees') ?>
            </a>
            <a href="<?= url('portal/payments') ?>" class="<?= $isActive('payments') ?>">
                <i class="bi bi-credit-card me-1"></i><?= __('portal.nav.pay_online') ?>
            </a>
            <span class="text-white-50">|</span>
            <a href="<?= url('portal/body-metrics') ?>" class="<?= $isActive('body-metrics') ?>">
                <i class="bi bi-activity me-1"></i><?= __('portal.nav.body_metrics') ?>
            </a>
            <a href="<?= url('portal/training-log') ?>" class="<?= $isActive('training-log') ?>">
                <i class="bi bi-journal-bookmark me-1"></i><?= __('portal.nav.training_log') ?>
            </a>
            <a href="<?= url('portal/emergency-contacts') ?>" class="<?= $isActive('emergency-contacts') ?>">
                <i class="bi bi-telephone-fill me-1"></i><?= __('portal.nav.emergency_contact') ?>
            </a>
            <a href="<?= url('portal/medical') ?>" class="<?= $isActive('medical') ?>">
                <i class="bi bi-heart-pulse me-1"></i><?= __('portal.nav.medical') ?>
            </a>
            <a href="<?= url('portal/licenses') ?>" class="<?= $isActive('licenses') ?>">
                <i class="bi bi-patch-check me-1"></i><?= __('portal.nav.licenses') ?>
            </a>
            <a href="<?= url('portal/consents') ?>" class="<?= $isActive('consents') ?>">
                <i class="bi bi-shield-check me-1"></i><?= __('portal.nav.gdpr_consents') ?>
            </a>
            <a href="<?= url('portal/gdpr') ?>" class="<?= $isActive('gdpr') ?>">
                <i class="bi bi-shield-lock me-1"></i><?= __('portal.nav.my_data') ?>
            </a>
            <a href="<?= url('portal/notifications') ?>" class="<?= $isActive('notifications') ?>">
                <i class="bi bi-bell me-1"></i><?= __('portal.notifications') ?>
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
                <strong><?= __('portal.impersonate') ?></strong> <?= View::e($memberName ?? '') ?>
            </div>
            <form method="POST" action="<?= url('impersonate/stop') ?>" class="m-0">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-warning"><i class="bi bi-arrow-return-left"></i> <?= __('portal.return_admin') ?></button>
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

    <footer class="cd-portal-footer mt-4 pt-3" style="border-top:1px solid rgba(0,0,0,.08); font-size:.8rem; color:#6c757d;">
        <div class="row align-items-center gy-2">
            <div class="col-md-7">
                <span>&copy; <?= date('Y') ?> ClubDesk &middot; Sendormeco Holding Sp. z o.o. &middot; NIP 5252866457</span>
            </div>
            <div class="col-md-5 text-md-end" style="white-space:nowrap;">
                <a href="<?= url('legal/regulamin') ?>" style="color: var(--app-primary, #EE2C28); text-decoration:none;">Regulamin</a> &middot;
                <a href="<?= url('legal/polityka-prywatnosci') ?>" style="color: var(--app-primary, #EE2C28); text-decoration:none;">Prywatność</a> &middot;
                <a href="<?= url('legal/cookies') ?>" style="color: var(--app-primary, #EE2C28); text-decoration:none;">Cookies</a> &middot;
                <a href="<?= url('legal/dpa') ?>" style="color: var(--app-primary, #EE2C28); text-decoration:none;">RODO</a> &middot;
                <a href="mailto:kontakt@clubdesk.pl" style="color: var(--app-primary, #EE2C28); text-decoration:none;">Kontakt</a>
            </div>
        </div>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- iOS install instructions modal (Safari nie wspiera beforeinstallprompt) -->
<div class="modal fade" id="pwaIosModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-phone"></i> <?= __('portal.pwa.install_title') ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= View::e(__('portal.pwa.close')) ?>"></button>
      </div>
      <div class="modal-body">
        <p><?= __('portal.pwa.intro') ?></p>
        <ol class="mb-3">
          <li><?= __('portal.pwa.step1') ?></li>
          <li><?= __('portal.pwa.step2') ?></li>
          <li><?= __('portal.pwa.step3') ?></li>
        </ol>
        <p class="text-muted small mb-0"><?= __('portal.pwa.note') ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('portal.pwa.understood') ?></button>
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

<!-- Floating "Zglos problem" button (support_reports + Todoist sync) -->
<a href="<?= url('support/report?return=' . urlencode($_SERVER['REQUEST_URI'] ?? '/')) ?>"
   class="support-fab"
   title="<?= View::e(__('nav.report_problem')) ?>">
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
