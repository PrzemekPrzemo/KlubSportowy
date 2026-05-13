<?php
declare(strict_types=1);

// ============================================================
// Front Controller — KlubSportowy
// ============================================================

define('ROOT_PATH', dirname(__DIR__));

// Auto-detect base URL
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
define('BASE_URL', $scheme . '://' . $host . $baseDir);

// App config (app.local.php overrides app.php when present)
$localApp  = ROOT_PATH . '/config/app.local.php';
$appConfig = file_exists($localApp)
    ? require $localApp
    : require ROOT_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone'] ?? 'Europe/Warsaw');

// ── Obsługa błędów ─────────────────────────────────────────
$debugMode = (bool)($appConfig['debug'] ?? false);

if ($debugMode) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

$logDir = ROOT_PATH . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
ini_set('log_errors', '1');
ini_set('error_log', $logDir . '/app.log');

set_exception_handler(function (Throwable $e) use ($debugMode, $appConfig): void {
    http_response_code(500);
    error_log(sprintf("[%s] %s: %s in %s:%d\n%s\n",
        date('Y-m-d H:i:s'), get_class($e), $e->getMessage(),
        $e->getFile(), $e->getLine(), $e->getTraceAsString()
    ));

    // Report to external error monitoring (Sentry etc.)
    try {
        require_once ROOT_PATH . '/app/Helpers/ErrorMonitor.php';
        \App\Helpers\ErrorMonitor::init($appConfig);
        \App\Helpers\ErrorMonitor::captureException($e);
    } catch (\Throwable) {}

    if ($debugMode) {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Błąd</title>'
            . '<style>body{font-family:monospace;background:#1e1e2e;color:#cdd6f4;padding:2em}'
            . 'h1{color:#f38ba8}pre{background:#313244;padding:1em;border-radius:8px;overflow-x:auto}</style>'
            . '</head><body><h1>&#10060; ' . htmlspecialchars(get_class($e)) . '</h1>'
            . '<p><strong>' . htmlspecialchars($e->getMessage()) . '</strong></p>'
            . '<p>' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>'
            . '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre></body></html>';
    } else {
        echo '<h1>Błąd serwera</h1><p>Wystąpił błąd. Spróbuj ponownie później.</p>';
    }
    exit(1);
});

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    if (!(error_reporting() & $errno)) return false;
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Composer autoloader (optional)
$vendorAutoload = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require $vendorAutoload;
}

// Simple PSR-4 autoloader (fallback without composer install)
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = ROOT_PATH . '/app/' . $relative . '.php';
    if (file_exists($file)) require $file;
});

// Global helpers
require ROOT_PATH . '/app/Helpers/Helpers.php';

// Session
\App\Helpers\Session::start();
\App\Helpers\Session::checkTimeout();

// ── i18n locale detection ────────────────────────────────
if (isset($_GET['lang']) && in_array($_GET['lang'], ['pl', 'en'])) {
    \App\Helpers\Session::set('locale', $_GET['lang']);
}
\App\Helpers\Translator::setLocale(\App\Helpers\Translator::getLocale());

// ── Multi-club: wykrywanie subdomeny ─────────────────────
$baseDomain = '';
try {
    $db  = \App\Helpers\Database::pdo();
    $bds = $db->prepare("SELECT `value` FROM `settings` WHERE `key` = 'base_domain' LIMIT 1");
    $bds->execute();
    $row = $bds->fetch();
    $baseDomain = $row ? (string)$row['value'] : '';
} catch (\Throwable) {}
if ($baseDomain !== '') {
    \App\Helpers\ClubContext::setFromSubdomain($_SERVER['HTTP_HOST'] ?? '', $baseDomain);
}

// ============================================================
// Routes
// ============================================================
$router = new \App\Helpers\Router();

// Strona startowa → logowanie (landing page jest na clubdesk.pl)
$router->get('/', [\App\Controllers\AuthController::class, 'showLogin']);

// Auth
$router->get('/auth/login',  [\App\Controllers\AuthController::class, 'showLogin']);
$router->post('/auth/login', [\App\Controllers\AuthController::class, 'login']);
$router->get('/auth/logout', [\App\Controllers\AuthController::class, 'logout']);

// Password reset — user
$router->get('/auth/forgot-password',       [\App\Controllers\PasswordResetController::class, 'showForgot']);
$router->post('/auth/forgot-password',      [\App\Controllers\PasswordResetController::class, 'sendReset']);
$router->get('/auth/reset-password/:token', [\App\Controllers\PasswordResetController::class, 'showReset']);
$router->post('/auth/reset-password',       [\App\Controllers\PasswordResetController::class, 'processReset']);

// Password reset — member portal
$router->get('/portal/forgot-password',       [\App\Controllers\PasswordResetController::class, 'showForgotMember']);
$router->post('/portal/forgot-password',      [\App\Controllers\PasswordResetController::class, 'sendResetMember']);
$router->get('/portal/reset-password/:token', [\App\Controllers\PasswordResetController::class, 'showResetMember']);
$router->post('/portal/reset-password',       [\App\Controllers\PasswordResetController::class, 'processResetMember']);

// Rejestracja publiczna klubu
$router->get('/register',  [\App\Controllers\AuthController::class, 'showRegister']);
$router->post('/register', [\App\Controllers\AuthController::class, 'register']);

// Wybór klubu po logowaniu
$router->get('/club-select',      [\App\Controllers\ClubSelectorController::class, 'show']);
$router->post('/club-select/:id', [\App\Controllers\ClubSelectorController::class, 'select']);

// Dashboard
$router->get('/dashboard', [\App\Controllers\DashboardController::class, 'index']);
$router->post('/dashboard/widgets', [\App\Controllers\DashboardController::class, 'saveWidgets']);

// Admin (super admin)
$router->get('/admin/dashboard',            [\App\Controllers\AdminController::class, 'dashboard']);
$router->get('/admin/clubs',                [\App\Controllers\AdminController::class, 'clubs']);
$router->get('/admin/clubs/create',         [\App\Controllers\AdminController::class, 'createClub']);
$router->post('/admin/clubs/create',        [\App\Controllers\AdminController::class, 'storeClub']);
$router->get('/admin/clubs/:id/edit',       [\App\Controllers\AdminController::class, 'editClub']);
$router->post('/admin/clubs/:id/edit',      [\App\Controllers\AdminController::class, 'updateClub']);
$router->post('/admin/switch-club/:id',     [\App\Controllers\AdminController::class, 'switchClub']);
$router->get('/admin/sports',               [\App\Controllers\AdminController::class, 'sportsCatalog']);
$router->get('/admin/sports/catalog',       [\App\Controllers\AdminPlatformController::class, 'sportsCatalog']);
$router->post('/admin/sports/:key/toggle',  [\App\Controllers\AdminPlatformController::class, 'toggleSport']);
$router->get('/admin/plans',                [\App\Controllers\AdminController::class, 'plans']);
$router->get('/admin/activity',             [\App\Controllers\AdminController::class, 'activityLog']);
$router->get('/admin/clubs/:id/users',      [\App\Controllers\AdminController::class, 'clubUsers']);
$router->post('/admin/clubs/:id/users/:userId/impersonate', [\App\Controllers\AdminController::class, 'impersonate']);
$router->post('/admin/clubs/:clubId/members/:memberId/impersonate-member', [\App\Controllers\AdminController::class, 'impersonateMember']);

// Admin: extended club management (BLOK 2A)
$router->get('/admin/clubs/create-full',       [\App\Controllers\AdminController::class, 'createClubFull']);
$router->post('/admin/clubs/create-full',      [\App\Controllers\AdminController::class, 'storeClubFull']);
$router->get('/admin/clubs/:id/edit-full',     [\App\Controllers\AdminController::class, 'editClubFull']);
$router->post('/admin/clubs/:id/edit-full',    [\App\Controllers\AdminController::class, 'updateClubFull']);
$router->post('/admin/clubs/:id/toggle-sport', [\App\Controllers\AdminController::class, 'toggleClubSport']);
$router->post('/admin/clubs/:id/limits',       [\App\Controllers\AdminController::class, 'setClubLimits']);
$router->get('/admin/clubs/:id/analytics',     [\App\Controllers\AdminController::class, 'clubAnalytics']);
$router->get('/admin/clubs/:id/export',        [\App\Controllers\ClubExportController::class, 'adminExport']);

// Admin: demo tokeny
$router->get('/admin/demos',           [\App\Controllers\DemoController::class, 'index']);
$router->post('/admin/demos/create',   [\App\Controllers\DemoController::class, 'create']);
$router->post('/admin/demos/cleanup',  [\App\Controllers\DemoController::class, 'cleanup']);
$router->post('/admin/demos/:id/delete', [\App\Controllers\DemoController::class, 'delete']);

// Admin: reklamy
$router->get('/admin/ads',              [\App\Controllers\AdsController::class, 'index']);
$router->get('/admin/ads/create',       [\App\Controllers\AdsController::class, 'create']);
$router->post('/admin/ads/store',       [\App\Controllers\AdsController::class, 'store']);
$router->get('/admin/ads/:id/edit',     [\App\Controllers\AdsController::class, 'edit']);
$router->post('/admin/ads/:id/update',  [\App\Controllers\AdsController::class, 'update']);
$router->post('/admin/ads/:id/delete',  [\App\Controllers\AdsController::class, 'delete']);

// Admin: backupy
$router->get('/admin/backups',                  [\App\Controllers\BackupController::class, 'index']);
$router->post('/admin/backups/create',          [\App\Controllers\BackupController::class, 'create']);
$router->get('/admin/backups/:file/download',   [\App\Controllers\BackupController::class, 'download']);
$router->post('/admin/backups/:file/delete',    [\App\Controllers\BackupController::class, 'delete']);

// Admin: platforma (plany cenowe, branding per-klub, support)
$router->get('/admin/platform/plans',                [\App\Controllers\AdminPlatformController::class, 'plans']);
$router->get('/admin/platform/plans/create',         [\App\Controllers\AdminPlatformController::class, 'createPlan']);
$router->post('/admin/platform/plans/store',         [\App\Controllers\AdminPlatformController::class, 'storePlan']);
$router->get('/admin/platform/plans/:id/edit',       [\App\Controllers\AdminPlatformController::class, 'editPlan']);
$router->post('/admin/platform/plans/:id/update',    [\App\Controllers\AdminPlatformController::class, 'updatePlan']);
$router->get('/admin/platform/branding/:clubId',     [\App\Controllers\AdminPlatformController::class, 'clubBranding']);
$router->post('/admin/platform/branding/:clubId/save', [\App\Controllers\AdminPlatformController::class, 'saveClubBranding']);
// W.1 — global system logo (Master Admin)
$router->get('/admin/platform/system-branding',       [\App\Controllers\AdminPlatformController::class, 'systemBranding']);
$router->post('/admin/platform/system-branding/save', [\App\Controllers\AdminPlatformController::class, 'saveSystemBranding']);
$router->get('/admin/platform/support',              [\App\Controllers\AdminPlatformController::class, 'supportTickets']);
$router->get('/admin/platform/support/:id',          [\App\Controllers\AdminPlatformController::class, 'viewTicket']);
$router->post('/admin/platform/support/:id/reply',   [\App\Controllers\AdminPlatformController::class, 'replyTicket']);
$router->post('/admin/platform/support/:id/close',   [\App\Controllers\AdminPlatformController::class, 'closeTicket']);

// Admin: feature flags (per-klub boolean włącz/wyłącz feature'ów)
$router->get('/admin/platform/feature-flags',                  [\App\Controllers\AdminFeatureFlagsController::class, 'index']);
$router->get('/admin/platform/feature-flags/clubs/:clubId',    [\App\Controllers\AdminFeatureFlagsController::class, 'clubOverrides']);
$router->post('/admin/platform/feature-flags/override',        [\App\Controllers\AdminFeatureFlagsController::class, 'saveOverride']);
$router->post('/admin/platform/feature-flags/clear',           [\App\Controllers\AdminFeatureFlagsController::class, 'clearOverride']);

// Support tickets (klub zarzad)
$router->get('/support',          [\App\Controllers\SupportController::class, 'index']);
$router->get('/support/create',   [\App\Controllers\SupportController::class, 'create']);
$router->post('/support/store',   [\App\Controllers\SupportController::class, 'store']);
$router->get('/support/:id',      [\App\Controllers\SupportController::class, 'show']);
$router->post('/support/:id/reply', [\App\Controllers\SupportController::class, 'reply']);

// Admin: subskrypcje klubów
$router->get('/admin/subscriptions',                    [\App\Controllers\AdminSubscriptionsController::class, 'index']);
$router->get('/admin/subscriptions/revenue',            [\App\Controllers\AdminSubscriptionsController::class, 'revenue']);
$router->post('/admin/subscriptions/:id/extend',        [\App\Controllers\AdminSubscriptionsController::class, 'extend']);
$router->post('/admin/subscriptions/:id/plan',          [\App\Controllers\AdminSubscriptionsController::class, 'changePlan']);
$router->post('/admin/subscriptions/:id/suspend',       [\App\Controllers\AdminSubscriptionsController::class, 'suspend']);
$router->post('/admin/subscriptions/:id/activate',      [\App\Controllers\AdminSubscriptionsController::class, 'activate']);
$router->post('/admin/subscriptions/:id/override',      [\App\Controllers\AdminSubscriptionsController::class, 'override']);

// Admin: konfiguracja klubu + feature flags
$router->get('/admin/clubs/:id/config',          [\App\Controllers\AdminClubConfigController::class, 'settings']);
$router->post('/admin/clubs/:id/config/save',    [\App\Controllers\AdminClubConfigController::class, 'saveSettings']);
$router->get('/admin/clubs/:id/features',        [\App\Controllers\AdminClubConfigController::class, 'features']);
$router->post('/admin/clubs/:id/features/save',  [\App\Controllers\AdminClubConfigController::class, 'saveFeatures']);

// Admin: dziennik błędów (Batch A1)
$router->get('/admin/errors',          [\App\Controllers\AdminErrorController::class, 'index']);
$router->get('/admin/errors/:id',      [\App\Controllers\AdminErrorController::class, 'show']);
$router->post('/admin/errors/purge',   [\App\Controllers\AdminErrorController::class, 'purge']);

// Admin: dziennik bezpieczeństwa (Batch A2)
$router->get('/admin/security',                 [\App\Controllers\AdminSecurityController::class, 'index']);
$router->get('/admin/security/blocked-ips',     [\App\Controllers\AdminSecurityController::class, 'blockedIps']);
$router->post('/admin/security/unblock/:ip',    [\App\Controllers\AdminSecurityController::class, 'unblockIp']);

// Admin: uprawnienia per-klub (Batch A4)
$router->get('/admin/clubs/:id/permissions',           [\App\Controllers\AdminClubConfigController::class, 'permissions']);
$router->post('/admin/clubs/:id/permissions',          [\App\Controllers\AdminClubConfigController::class, 'savePermissions']);
$router->post('/admin/clubs/:id/permissions/reset',    [\App\Controllers\AdminClubConfigController::class, 'resetPermissions']);

// Admin: sport settings per club (Batch S0)
$router->get('/admin/clubs/:id/sports',              [\App\Controllers\AdminClubConfigController::class, 'sportSettings']);
$router->get('/admin/clubs/:id/sports/:sport',       [\App\Controllers\AdminClubConfigController::class, 'sportSettings']);
$router->post('/admin/clubs/:id/sports/:sport/save', [\App\Controllers\AdminClubConfigController::class, 'saveSportSettings']);

// Admin: faktury (Batch A5)
$router->get('/admin/invoices',              [\App\Controllers\AdminInvoicesController::class, 'index']);
$router->get('/admin/invoices/create',       [\App\Controllers\AdminInvoicesController::class, 'create']);
$router->post('/admin/invoices/store',       [\App\Controllers\AdminInvoicesController::class, 'store']);
$router->get('/admin/invoices/:id',          [\App\Controllers\AdminInvoicesController::class, 'show']);
$router->post('/admin/invoices/:id/pay',     [\App\Controllers\AdminInvoicesController::class, 'markPaid']);
$router->post('/admin/invoices/:id/cancel',  [\App\Controllers\AdminInvoicesController::class, 'markCancelled']);

// Admin: audyt izolacji danych (Batch A6)
$router->get('/admin/audit/isolation',   [\App\Controllers\AdminAuditController::class, 'isolation']);
$router->post('/admin/audit/export',     [\App\Controllers\AdminAuditController::class, 'exportReport']);

// Admin: dashboard zdrowia systemu (Batch A7)
$router->get('/admin/health', [\App\Controllers\AdminHealthController::class, 'index']);

// Admin: super admin users (Batch A8)
$router->get('/admin/users',                       [\App\Controllers\AdminUsersController::class, 'index']);
$router->get('/admin/users/create',                [\App\Controllers\AdminUsersController::class, 'create']);
$router->post('/admin/users/store',                [\App\Controllers\AdminUsersController::class, 'store']);
$router->post('/admin/users/:id/deactivate',       [\App\Controllers\AdminUsersController::class, 'deactivate']);
$router->post('/admin/users/:id/activate',         [\App\Controllers\AdminUsersController::class, 'activate']);
$router->post('/admin/users/:id/reset-password',   [\App\Controllers\AdminUsersController::class, 'resetPassword']);

// Impersonacja — zakończenie (dla zalogowanego impersonującego, nie wymaga super-admin)
$router->post('/impersonate/stop', [\App\Controllers\ImpersonationController::class, 'stop']);

// Demo — publiczny dostep przez token
$router->get('/demo/:token', [\App\Controllers\DemoController::class, 'loginViaToken']);

// Legal pages
$router->get('/terms',   [\App\Controllers\LegalController::class, 'terms']);
$router->get('/privacy', [\App\Controllers\LegalController::class, 'privacy']);

// Strony publiczne (bez logowania)
// Q.1 — Publiczny cennik (no-auth)
$router->get('/cennik',              [\App\Controllers\PricingController::class, 'index']);
$router->get('/pricing',             [\App\Controllers\PricingController::class, 'index']);

$router->get('/pub',                 [\App\Controllers\PublicController::class, 'clubList']);
$router->get('/pub/:slug/results',   [\App\Controllers\PublicController::class, 'clubResults']);
$router->get('/pub/:slug',           [\App\Controllers\PublicController::class, 'clubPage']);

// Onboarding wizard
$router->get('/onboarding/step1',      [\App\Controllers\OnboardingController::class, 'step1']);
$router->post('/onboarding/step1',     [\App\Controllers\OnboardingController::class, 'saveStep1']);
$router->get('/onboarding/step2',      [\App\Controllers\OnboardingController::class, 'step2']);
$router->post('/onboarding/step2',     [\App\Controllers\OnboardingController::class, 'saveStep2']);
$router->get('/onboarding/step3',      [\App\Controllers\OnboardingController::class, 'step3']);
$router->post('/onboarding/step3',     [\App\Controllers\OnboardingController::class, 'saveStep3']);
$router->get('/onboarding/step4',      [\App\Controllers\OnboardingController::class, 'step4']);
$router->post('/onboarding/step4',     [\App\Controllers\OnboardingController::class, 'saveStep4']);
$router->get('/onboarding/step5',      [\App\Controllers\OnboardingController::class, 'step5']);
$router->post('/onboarding/complete',  [\App\Controllers\OnboardingController::class, 'complete']);
$router->get('/onboarding/skip',       [\App\Controllers\OnboardingController::class, 'skip']);

// Sekcje sportowe w kontekście klubu
$router->get('/sports',                [\App\Controllers\SportsController::class, 'index']);
$router->post('/sports/enable',        [\App\Controllers\SportsController::class, 'enable']);
$router->post('/sports/disable/:id',   [\App\Controllers\SportsController::class, 'disable']);
$router->post('/sports/activate/:id',  [\App\Controllers\SportsController::class, 'activate']);
$router->post('/sports/clear-active',  [\App\Controllers\SportsController::class, 'clearActive']);
// W.2 — per-sport logos (3 sloty na PDF / dokumenty)
$router->get('/sports/:id/logos',       [\App\Controllers\SportsController::class, 'editLogos']);
$router->post('/sports/:id/logos/save', [\App\Controllers\SportsController::class, 'saveLogos']);

// GDPR
$router->get('/gdpr',                            [\App\Controllers\GdprController::class, 'index']);
$router->get('/gdpr/member/:memberId',            [\App\Controllers\GdprController::class, 'memberConsents']);
$router->post('/gdpr/member/:memberId/grant',     [\App\Controllers\GdprController::class, 'grantConsent']);
$router->post('/gdpr/member/:memberId/revoke',    [\App\Controllers\GdprController::class, 'revokeConsent']);
$router->get('/gdpr/member/:memberId/export',     [\App\Controllers\GdprController::class, 'exportData']);
$router->post('/gdpr/member/:memberId/anonymize', [\App\Controllers\GdprController::class, 'anonymize']);

// Zawodnicy
$router->post('/members/bulk',        [\App\Controllers\MembersController::class, 'bulkAction']);
// Z.3 — bulk message form + send
$router->get('/members/bulk-message',         [\App\Controllers\MembersController::class, 'bulkMessageForm']);
$router->post('/members/bulk-message/send',   [\App\Controllers\MembersController::class, 'bulkMessageSend']);
$router->get('/members',              [\App\Controllers\MembersController::class, 'index']);
$router->get('/members/create',       [\App\Controllers\MembersController::class, 'create']);
$router->post('/members/store',       [\App\Controllers\MembersController::class, 'store']);
$router->get('/members/:id',          [\App\Controllers\MembersController::class, 'show']);
$router->get('/members/:id/edit',     [\App\Controllers\MembersController::class, 'edit']);
$router->post('/members/:id/update',  [\App\Controllers\MembersController::class, 'update']);
$router->post('/members/:id/delete',  [\App\Controllers\MembersController::class, 'delete']);
$router->post('/members/:id/portal-password', [\App\Controllers\MembersController::class, 'setPortalPassword']);

// Finanse
$router->get('/fees',                      [\App\Controllers\FeesController::class, 'index']);
$router->get('/fees/rates',                [\App\Controllers\FeesController::class, 'rates']);
$router->post('/fees/rates/store',         [\App\Controllers\FeesController::class, 'storeRate']);
$router->get('/fees/rates/:id/edit',       [\App\Controllers\FeesController::class, 'editRate']);
$router->post('/fees/rates/:id/update',    [\App\Controllers\FeesController::class, 'updateRate']);
$router->post('/fees/rates/:id/toggle',    [\App\Controllers\FeesController::class, 'toggleRateActive']);
$router->post('/fees/rates/:id/delete',    [\App\Controllers\FeesController::class, 'deleteRate']);

// Zniżki klubowe (Faza P.2)
$router->get('/fees/discounts',                [\App\Controllers\DiscountsController::class, 'index']);
$router->get('/fees/discounts/new',            [\App\Controllers\DiscountsController::class, 'create']);
$router->post('/fees/discounts/store',         [\App\Controllers\DiscountsController::class, 'store']);
$router->get('/fees/discounts/:id/edit',       [\App\Controllers\DiscountsController::class, 'edit']);
$router->post('/fees/discounts/:id/update',    [\App\Controllers\DiscountsController::class, 'update']);
$router->post('/fees/discounts/:id/toggle',    [\App\Controllers\DiscountsController::class, 'toggleActive']);
$router->post('/fees/discounts/:id/delete',    [\App\Controllers\DiscountsController::class, 'delete']);

// Subskrypcje opłat (Faza P.3) — przypisanie polityki + zniżek M:N do zawodnika
$router->get('/fees/assignments',                [\App\Controllers\FeeAssignmentsController::class, 'index']);
$router->get('/fees/assignments/new',            [\App\Controllers\FeeAssignmentsController::class, 'create']);
$router->post('/fees/assignments/store',         [\App\Controllers\FeeAssignmentsController::class, 'store']);
$router->get('/fees/assignments/:id/edit',       [\App\Controllers\FeeAssignmentsController::class, 'edit']);
$router->post('/fees/assignments/:id/update',    [\App\Controllers\FeeAssignmentsController::class, 'update']);
$router->post('/fees/assignments/:id/delete',    [\App\Controllers\FeeAssignmentsController::class, 'delete']);
$router->post('/fees/assignments/preview',       [\App\Controllers\FeeAssignmentsController::class, 'calculatePreview']);

// Należności + auto-generator (Faza P.4)
$router->get('/fees/dues',                  [\App\Controllers\DuesController::class, 'index']);
$router->get('/fees/dues/generate',         [\App\Controllers\DuesController::class, 'generateForm']);
$router->post('/fees/dues/generate',        [\App\Controllers\DuesController::class, 'generate']);
$router->post('/fees/dues/refresh',         [\App\Controllers\DuesController::class, 'refresh']);
$router->post('/fees/dues/:id/pay',         [\App\Controllers\DuesController::class, 'pay']);
$router->post('/fees/dues/:id/waive',       [\App\Controllers\DuesController::class, 'waive']);
$router->post('/fees/dues/:id/cancel',      [\App\Controllers\DuesController::class, 'cancel']);

// Księgowość (Faza P.4) — rejestr wpłat z filtrami + CSV export
$router->get('/accounting',         [\App\Controllers\AccountingController::class, 'index']);
$router->get('/accounting/export',  [\App\Controllers\AccountingController::class, 'exportCsv']);

// Wszyscy zawodnicy (cross-sport, Faza P.4)
$router->get('/members-all',        [\App\Controllers\AllMembersController::class, 'index']);

// Prowizje trenerów (U.2 admin + V.0 portal trenera)
$router->get('/trainer/commissions/my',                             [\App\Controllers\TrainerCommissionsController::class, 'my']);
$router->get('/club/trainers/commissions',                          [\App\Controllers\TrainerCommissionsController::class, 'index']);
$router->get('/club/trainers/commissions/report',                   [\App\Controllers\TrainerCommissionsController::class, 'report']);
$router->post('/club/trainers/commissions/mark-paid-out',           [\App\Controllers\TrainerCommissionsController::class, 'markPaidOut']);
$router->get('/club/trainers/commissions/rates',                    [\App\Controllers\TrainerCommissionsController::class, 'rates']);
$router->get('/club/trainers/commissions/rates/new',                [\App\Controllers\TrainerCommissionsController::class, 'createRate']);
$router->post('/club/trainers/commissions/rates/store',             [\App\Controllers\TrainerCommissionsController::class, 'storeRate']);
$router->get('/club/trainers/commissions/rates/:id/edit',           [\App\Controllers\TrainerCommissionsController::class, 'editRate']);
$router->post('/club/trainers/commissions/rates/:id/update',        [\App\Controllers\TrainerCommissionsController::class, 'updateRate']);
$router->post('/club/trainers/commissions/rates/:id/toggle',        [\App\Controllers\TrainerCommissionsController::class, 'toggleRate']);
$router->post('/club/trainers/commissions/rates/:id/delete',        [\App\Controllers\TrainerCommissionsController::class, 'deleteRate']);

// Per-klub bramki płatności (Faza P.5)
// Q.2.2 — subskrypcja klubu + addons (dokup zasobów)
$router->get('/club/subscription',                            [\App\Controllers\SubscriptionAddonsController::class, 'overview']);
$router->get('/club/subscription/addons',                     [\App\Controllers\SubscriptionAddonsController::class, 'catalog']);
$router->post('/club/subscription/addons/buy',                [\App\Controllers\SubscriptionAddonsController::class, 'buy']);
$router->post('/club/subscription/addons/:id/cancel',         [\App\Controllers\SubscriptionAddonsController::class, 'cancel']);
$router->post('/club/subscription/addons/:id/reactivate',     [\App\Controllers\SubscriptionAddonsController::class, 'reactivate']);

$router->get('/club/gateways',                       [\App\Controllers\ClubGatewayController::class, 'index']);
$router->get('/club/gateways/:provider/edit',        [\App\Controllers\ClubGatewayController::class, 'edit']);
$router->post('/club/gateways/:provider/save',       [\App\Controllers\ClubGatewayController::class, 'save']);
$router->post('/club/gateways/:provider/test',       [\App\Controllers\ClubGatewayController::class, 'testConnection']);
$router->post('/club/gateways/:provider/toggle',     [\App\Controllers\ClubGatewayController::class, 'toggleActive']);
$router->post('/club/gateways/:provider/delete',     [\App\Controllers\ClubGatewayController::class, 'delete']);

// Per-klub integracja wysyłki InPost (ShipX) — F.6
$router->get('/club/shipping',              [\App\Controllers\ClubShippingController::class, 'index']);
$router->get('/club/shipping/edit',         [\App\Controllers\ClubShippingController::class, 'edit']);
$router->post('/club/shipping/save',        [\App\Controllers\ClubShippingController::class, 'save']);
$router->post('/club/shipping/test',        [\App\Controllers\ClubShippingController::class, 'testConnection']);
$router->post('/club/shipping/toggle',      [\App\Controllers\ClubShippingController::class, 'toggleActive']);

// Powiadomienia (Faza S.1)
$router->get('/club/notifications',                       [\App\Controllers\NotificationRulesController::class, 'index']);
$router->post('/club/notifications/rules/store',          [\App\Controllers\NotificationRulesController::class, 'storeRule']);
$router->post('/club/notifications/rules/:id/update',     [\App\Controllers\NotificationRulesController::class, 'updateRule']);
$router->post('/club/notifications/rules/:id/toggle',     [\App\Controllers\NotificationRulesController::class, 'toggleRule']);
$router->post('/club/notifications/rules/:id/delete',     [\App\Controllers\NotificationRulesController::class, 'deleteRule']);
$router->get('/fees/new',                  [\App\Controllers\FeesController::class, 'createPayment']);
$router->post('/fees/store',               [\App\Controllers\FeesController::class, 'storePayment']);

// 2FA (TOTP)
$router->get('/2fa/setup',     [\App\Controllers\TwoFactorController::class, 'setup']);
$router->post('/2fa/confirm',  [\App\Controllers\TwoFactorController::class, 'confirm']);
$router->post('/2fa/disable',  [\App\Controllers\TwoFactorController::class, 'disable']);
$router->get('/2fa/verify',    [\App\Controllers\TwoFactorController::class, 'verify']);
$router->post('/2fa/verify',   [\App\Controllers\TwoFactorController::class, 'verifyCode']);

// Integracje z federacjami sportowymi
$router->get('/federation',                    [\App\Controllers\FederationController::class, 'index']);
$router->get('/federation/configure',          [\App\Controllers\FederationController::class, 'configure']);
$router->post('/federation/configure/save',    [\App\Controllers\FederationController::class, 'saveConfigure']);
$router->get('/federation/verify/:licenseId',  [\App\Controllers\FederationController::class, 'verifyLicense']);

// Zarządzanie klubem (ustawienia / branding / SMTP / użytkownicy)
$router->get('/club/settings',            [\App\Controllers\ClubManagementController::class, 'settings']);
$router->post('/club/settings/save',      [\App\Controllers\ClubManagementController::class, 'saveSettings']);
$router->get('/club/customization',       [\App\Controllers\ClubManagementController::class, 'customization']);
$router->post('/club/customization/save', [\App\Controllers\ClubManagementController::class, 'saveCustomization']);
$router->get('/club/smtp',                [\App\Controllers\ClubManagementController::class, 'smtp']);
$router->post('/club/smtp/save',          [\App\Controllers\ClubManagementController::class, 'saveSmtp']);
$router->get('/club/users',               [\App\Controllers\ClubManagementController::class, 'users']);
$router->post('/club/users/add',          [\App\Controllers\ClubManagementController::class, 'addUser']);
$router->post('/club/users/:userId/revoke', [\App\Controllers\ClubManagementController::class, 'revokeUser']);

// Club export
$router->get('/club/export', [\App\Controllers\ClubExportController::class, 'export']);

// Webhooki
$router->get('/club/webhooks',              [\App\Controllers\WebhooksController::class, 'index']);
$router->get('/club/webhooks/create',       [\App\Controllers\WebhooksController::class, 'create']);
$router->post('/club/webhooks/store',       [\App\Controllers\WebhooksController::class, 'store']);
$router->post('/club/webhooks/:id/delete',  [\App\Controllers\WebhooksController::class, 'delete']);

// Billing (subskrypcje)
$router->get('/billing/plans',               [\App\Controllers\BillingController::class, 'plans']);
$router->post('/billing/upgrade',            [\App\Controllers\BillingController::class, 'upgrade']);
$router->get('/billing/invoices',            [\App\Controllers\BillingController::class, 'invoices']);
$router->post('/billing/invoices/:id/paid',  [\App\Controllers\BillingController::class, 'markPaid']);

// Stripe/P24 Webhook (no CSRF — signed) — legacy SaaS billing
$router->post('/webhook/payment', [\App\Controllers\PaymentWebhookController::class, 'handle']);

// Faza T.3 — universal gateway webhook router (all providers)
// Routes payment notifications per provider via GatewayFactory.
// (no CSRF — uwierzytelnione sygnaturą HMAC w adapterze)
$router->post('/api/v1/payment/webhook/:provider', [\App\Controllers\GatewayWebhookController::class, 'handle']);

// Portal zawodnika (self-service)
$router->get('/portal/login',            [\App\Controllers\MemberPortalController::class, 'showLogin']);
$router->post('/portal/login',           [\App\Controllers\MemberPortalController::class, 'login']);
$router->get('/portal/logout',           [\App\Controllers\MemberPortalController::class, 'logout']);
$router->get('/portal/dashboard',        [\App\Controllers\MemberPortalController::class, 'dashboard']);
$router->get('/portal/profile',          [\App\Controllers\MemberPortalController::class, 'profile']);
$router->post('/portal/profile/update',  [\App\Controllers\MemberPortalController::class, 'updateProfile']);
$router->post('/portal/password',        [\App\Controllers\MemberPortalController::class, 'changePassword']);

// Portal: 2FA TOTP
$router->get('/portal/2fa/setup',                     [\App\Controllers\MemberTwoFactorController::class, 'setup']);
$router->post('/portal/2fa/confirm',                  [\App\Controllers\MemberTwoFactorController::class, 'confirm']);
$router->get('/portal/2fa/verify',                    [\App\Controllers\MemberTwoFactorController::class, 'verify']);
$router->post('/portal/2fa/verify',                   [\App\Controllers\MemberTwoFactorController::class, 'verifySubmit']);
$router->get('/portal/2fa/backup-codes',              [\App\Controllers\MemberTwoFactorController::class, 'backupCodes']);
$router->post('/portal/2fa/backup-codes/regenerate',  [\App\Controllers\MemberTwoFactorController::class, 'regenerateBackup']);
$router->post('/portal/2fa/disable',                  [\App\Controllers\MemberTwoFactorController::class, 'disable']);

$router->get('/portal/fees',             [\App\Controllers\MemberPortalController::class, 'fees']);
$router->get('/portal/events',           [\App\Controllers\MemberPortalController::class, 'events']);
$router->get('/portal/sport-history',    [\App\Controllers\MemberPortalController::class, 'sportHistory']);

// Portal: club selection (BLOK 2B - unified member identity)
$router->get('/portal/club-select',      [\App\Controllers\MemberPortalController::class, 'showClubSelect']);
$router->post('/portal/club-select/:id', [\App\Controllers\MemberPortalController::class, 'selectClub']);

// Portal: cross-club sport section switcher (B1)
$router->post('/portal/switch-section/:id', [\App\Controllers\MemberPortalController::class, 'switchSection']);

// Portal: płatności online
$router->get('/portal/payments',         [\App\Controllers\MemberPaymentController::class, 'index']);
$router->post('/portal/payments/pay',    [\App\Controllers\MemberPaymentController::class, 'pay']);
$router->get('/portal/payments/success', [\App\Controllers\MemberPaymentController::class, 'success']);

// Portal — należności (Faza P.6)
$router->get('/portal/dues',             [\App\Controllers\MemberPortalController::class, 'dues']);
$router->post('/portal/dues/:id/pay',    [\App\Controllers\MemberPaymentController::class, 'payDue']);

// Portal — preferencje powiadomień (Faza S.2 RODO opt-out)
$router->get('/portal/notification-prefs',         [\App\Controllers\MemberPortalController::class, 'notificationPrefs']);
$router->post('/portal/notification-prefs/update', [\App\Controllers\MemberPortalController::class, 'updateNotificationPrefs']);

// Portal: karta zawodnika + zdjęcie
$router->get('/portal/member-card',      [\App\Controllers\MemberPortalController::class, 'memberCard']);
$router->post('/portal/photo-upload',    [\App\Controllers\MemberPortalController::class, 'uploadPhoto']);

// Portal: pomiary ciała
$router->get('/portal/body-metrics',     [\App\Controllers\MemberPortalController::class, 'bodyMetrics']);
$router->post('/portal/body-metrics',    [\App\Controllers\MemberPortalController::class, 'storeBodyMetrics']);

// Admin: uprawnienia trenerskie i sędziowskie
$router->get('/certifications',             [\App\Controllers\CoachCertificationsController::class, 'index']);
$router->post('/certifications/store',      [\App\Controllers\CoachCertificationsController::class, 'store']);
$router->post('/certifications/:id/delete', [\App\Controllers\CoachCertificationsController::class, 'delete']);

// Admin: sprzęt klubowy
$router->get('/equipment',                          [\App\Controllers\ClubEquipmentController::class, 'index']);
$router->get('/equipment/:id',                      [\App\Controllers\ClubEquipmentController::class, 'show']);
$router->post('/equipment/store',                   [\App\Controllers\ClubEquipmentController::class, 'store']);
$router->post('/equipment/:id/delete',              [\App\Controllers\ClubEquipmentController::class, 'delete']);
$router->post('/equipment/:id/assign',              [\App\Controllers\ClubEquipmentController::class, 'assign']);
$router->post('/equipment/:id/return/:aid',         [\App\Controllers\ClubEquipmentController::class, 'returnItem']);

// Admin: dziennik dostępu do danych wrażliwych (RODO art. 30) — tylko zarząd
$router->get('/admin/sensitive-access', [\App\Controllers\AdminSensitiveAccessController::class, 'index']);

// Admin: zgodność (anti-doping + zgody małoletnich)
$router->get('/admin/compliance',                               [\App\Controllers\ComplianceController::class, 'index']);
$router->post('/admin/compliance/declaration/store',            [\App\Controllers\ComplianceController::class, 'storeDeclaration']);
$router->post('/admin/compliance/declaration/:id/delete',       [\App\Controllers\ComplianceController::class, 'deleteDeclaration']);
$router->post('/admin/compliance/minor-consent/:id/store',      [\App\Controllers\ComplianceController::class, 'storeMinorConsent']);

// Portal: dziennik treningowy
$router->get('/portal/training-log',            [\App\Controllers\MemberPortalController::class, 'trainingLog']);
$router->post('/portal/training-log/store',     [\App\Controllers\MemberPortalController::class, 'storeTrainingLog']);
$router->post('/portal/training-log/:id/delete',[\App\Controllers\MemberPortalController::class, 'deleteTrainingLog']);

// Portal: kontakty awaryjne
$router->get('/portal/emergency-contacts',                   [\App\Controllers\MemberPortalController::class, 'emergencyContacts']);
$router->post('/portal/emergency-contacts/store',            [\App\Controllers\MemberPortalController::class, 'storeEmergencyContact']);
$router->post('/portal/emergency-contacts/:id/delete',       [\App\Controllers\MemberPortalController::class, 'deleteEmergencyContact']);

// Admin: kontakty awaryjne zawodnika
$router->get('/members/:id/emergency-contacts',                  [\App\Controllers\EmergencyContactsController::class, 'member']);
$router->post('/members/:id/emergency-contacts/store',           [\App\Controllers\EmergencyContactsController::class, 'store']);
$router->post('/members/:id/emergency-contacts/:cid/primary',    [\App\Controllers\EmergencyContactsController::class, 'makePrimary']);
$router->post('/members/:id/emergency-contacts/:cid/delete',     [\App\Controllers\EmergencyContactsController::class, 'delete']);

// Portal: badania lekarskie + licencje
$router->get('/portal/medical',          [\App\Controllers\MemberPortalController::class, 'medical']);
$router->get('/portal/licenses',         [\App\Controllers\MemberPortalController::class, 'licenses']);

// Admin: pomiary ciała zawodnika
$router->get('/members/:id/metrics',              [\App\Controllers\BodyMetricsController::class, 'member']);
$router->post('/members/:id/metrics/store',       [\App\Controllers\BodyMetricsController::class, 'store']);
$router->post('/members/:id/metrics/:mid/delete', [\App\Controllers\BodyMetricsController::class, 'delete']);

// Portal: zgody RODO
$router->get('/portal/consents',         [\App\Controllers\MemberPortalController::class, 'consents']);
$router->post('/portal/consents/update', [\App\Controllers\MemberPortalController::class, 'updateConsent']);
$router->get('/portal/anti-doping',      [\App\Controllers\MemberPortalController::class, 'antiDoping']);
$router->post('/portal/anti-doping',     [\App\Controllers\MemberPortalController::class, 'storeAntiDoping']);

// Portal: ogłoszenia + plan treningów
$router->get('/portal/announcements',    [\App\Controllers\MemberPortalController::class, 'announcements']);
$router->get('/portal/schedule',         [\App\Controllers\MemberPortalController::class, 'schedule']);

// Portal: frekwencja, wyniki, rankingi
$router->get('/portal/attendance',       [\App\Controllers\MemberPortalController::class, 'attendance']);
$router->get('/portal/results',          [\App\Controllers\MemberPortalController::class, 'results']);

// Portal: pasy i stopnie
$router->get('/portal/belts',            [\App\Controllers\MemberPortalController::class, 'belts']);

// Portal: powiadomienia
$router->get('/portal/notifications',                   [\App\Controllers\MemberPortalController::class, 'notifications']);
$router->post('/portal/notifications/:id/read',         [\App\Controllers\MemberPortalController::class, 'markNotificationRead']);

// Portal: turnieje
$router->get('/portal/tournaments',                     [\App\Controllers\MemberPortalController::class, 'tournaments']);
$router->post('/portal/tournaments/:id/register',       [\App\Controllers\MemberPortalController::class, 'registerTournament']);
$router->post('/portal/tournaments/:id/withdraw',       [\App\Controllers\MemberPortalController::class, 'withdrawTournament']);

// Portal: widoki per sport
$router->get('/portal/sport/:key',                      [\App\Controllers\MemberPortalController::class, 'sportDetail']);

// Powiadomienia (dzwoneczek)
$router->post('/notifications/:id/read', [\App\Controllers\NotificationsController::class, 'markRead']);

// Szablony e-mail + kolejka
$router->get('/email/templates',                [\App\Controllers\EmailTemplatesController::class, 'index']);
$router->get('/email/templates/:type',          [\App\Controllers\EmailTemplatesController::class, 'edit']);
$router->post('/email/templates/:type/save',    [\App\Controllers\EmailTemplatesController::class, 'save']);
$router->get('/email/queue',                    [\App\Controllers\EmailTemplatesController::class, 'queue']);

// Galeria
$router->get('/gallery',                    [\App\Controllers\GalleryController::class, 'index']);
$router->get('/gallery/create',             [\App\Controllers\GalleryController::class, 'create']);
$router->post('/gallery/store',             [\App\Controllers\GalleryController::class, 'store']);
$router->get('/gallery/:id',                [\App\Controllers\GalleryController::class, 'show']);
$router->post('/gallery/:id/upload',        [\App\Controllers\GalleryController::class, 'upload']);
$router->post('/gallery/:id/delete',        [\App\Controllers\GalleryController::class, 'delete']);
$router->post('/gallery/photo/:id/delete',  [\App\Controllers\GalleryController::class, 'deletePhoto']);

// Ogłoszenia
$router->get('/announcements',              [\App\Controllers\AnnouncementsController::class, 'index']);
$router->get('/announcements/create',       [\App\Controllers\AnnouncementsController::class, 'create']);
$router->post('/announcements/store',       [\App\Controllers\AnnouncementsController::class, 'store']);
$router->get('/announcements/:id/edit',     [\App\Controllers\AnnouncementsController::class, 'edit']);
$router->post('/announcements/:id/update',  [\App\Controllers\AnnouncementsController::class, 'update']);
$router->post('/announcements/:id/delete',  [\App\Controllers\AnnouncementsController::class, 'delete']);

// Badania lekarskie
$router->get('/medical',              [\App\Controllers\MedicalExamsController::class, 'index']);
$router->get('/medical/create',       [\App\Controllers\MedicalExamsController::class, 'create']);
$router->post('/medical/store',       [\App\Controllers\MedicalExamsController::class, 'store']);
$router->get('/medical/:id/edit',     [\App\Controllers\MedicalExamsController::class, 'edit']);
$router->post('/medical/:id/update',  [\App\Controllers\MedicalExamsController::class, 'update']);
$router->post('/medical/:id/delete',  [\App\Controllers\MedicalExamsController::class, 'delete']);

// Kalendarz
$router->get('/calendar',              [\App\Controllers\CalendarController::class, 'index']);
$router->get('/calendar/create',       [\App\Controllers\CalendarController::class, 'create']);
$router->post('/calendar/store',       [\App\Controllers\CalendarController::class, 'store']);
$router->get('/calendar/:id/edit',     [\App\Controllers\CalendarController::class, 'edit']);
$router->post('/calendar/:id/update',  [\App\Controllers\CalendarController::class, 'update']);
$router->post('/calendar/:id/delete',  [\App\Controllers\CalendarController::class, 'delete']);
$router->get('/calendar/ical',         [\App\Controllers\CalendarController::class, 'icalSubscription']);
$router->get('/cal/:token',            [\App\Controllers\CalendarController::class, 'calendarFeed']);

// Transmisje live
$router->get('/livestream',                [\App\Controllers\LivestreamController::class, 'index']);
$router->get('/livestream/create',         [\App\Controllers\LivestreamController::class, 'create']);
$router->post('/livestream/store',         [\App\Controllers\LivestreamController::class, 'store']);
$router->get('/livestream/:id/watch',      [\App\Controllers\LivestreamController::class, 'watch']);
$router->post('/livestream/:id/status',    [\App\Controllers\LivestreamController::class, 'setStatus']);
$router->post('/livestream/:id/delete',    [\App\Controllers\LivestreamController::class, 'delete']);

// Treningi
$router->get('/trainings',                        [\App\Controllers\TrainingsController::class, 'index']);
$router->get('/trainings/create',                 [\App\Controllers\TrainingsController::class, 'create']);
$router->post('/trainings/store',                 [\App\Controllers\TrainingsController::class, 'store']);
$router->get('/trainings/:id',                    [\App\Controllers\TrainingsController::class, 'show']);
$router->get('/trainings/:id/edit',               [\App\Controllers\TrainingsController::class, 'edit']);
$router->post('/trainings/:id/update',            [\App\Controllers\TrainingsController::class, 'update']);
$router->post('/trainings/:id/delete',            [\App\Controllers\TrainingsController::class, 'delete']);
$router->post('/trainings/:id/attendee/add',      [\App\Controllers\TrainingsController::class, 'addAttendee']);
$router->post('/trainings/:id/attendee/:attendeeId/remove', [\App\Controllers\TrainingsController::class, 'removeAttendee']);
$router->post('/trainings/:id/attendance',        [\App\Controllers\TrainingsController::class, 'markAttendance']);

// Import CSV
$router->get('/import',               [\App\Controllers\ImportController::class, 'index']);
$router->post('/import/upload',       [\App\Controllers\ImportController::class, 'upload']);
$router->post('/import/execute',      [\App\Controllers\ImportController::class, 'execute']);

// Global AJAX search
$router->get('/api/search', [\App\Controllers\SearchController::class, 'search']);

// Wydarzenia
$router->get('/events',               [\App\Controllers\EventsController::class, 'index']);
$router->get('/events/create',        [\App\Controllers\EventsController::class, 'create']);
$router->post('/events/store',        [\App\Controllers\EventsController::class, 'store']);
$router->post('/events/:id/delete',   [\App\Controllers\EventsController::class, 'delete']);

// iCal export
$router->get('/ics/event/:id',    [\App\Controllers\IcsController::class, 'event']);
$router->get('/ics/training/:id', [\App\Controllers\IcsController::class, 'training']);

// Dokumenty PDF
$router->get('/documents',                      [\App\Controllers\DocumentsController::class, 'index']);
$router->get('/documents/agreement/:memberId',  [\App\Controllers\DocumentsController::class, 'memberAgreement']);
$router->get('/documents/consent/:memberId',    [\App\Controllers\DocumentsController::class, 'trainingConsent']);
$router->get('/documents/waiver/:memberId',     [\App\Controllers\DocumentsController::class, 'liabilityWaiver']);

// Statystyki i porównywarka zawodników
$router->get('/stats/member/:memberId',  [\App\Controllers\PlayerStatsController::class, 'profile']);
$router->get('/stats/compare',           [\App\Controllers\PlayerStatsController::class, 'compare']);

// Raporty
$router->get('/reports',                    [\App\Controllers\ReportsController::class, 'index']);
$router->get('/reports/members-pdf',        [\App\Controllers\ReportsController::class, 'membersPdf']);
$router->get('/reports/members-csv',        [\App\Controllers\ReportsController::class, 'membersCsv']);
$router->get('/reports/finances-pdf',       [\App\Controllers\ReportsController::class, 'financesPdf']);
$router->get('/reports/monthly-dues-pdf',   [\App\Controllers\ReportsController::class, 'monthlyDuesPdf']);
$router->get('/reports/finances-csv',       [\App\Controllers\ReportsController::class, 'financesCsv']);
$router->get('/reports/event-protocol/:id', [\App\Controllers\ReportsController::class, 'eventProtocolPdf']);
$router->get('/reports/member-card/:id',    [\App\Controllers\ReportsController::class, 'memberCardPdf']);

// Klucze API (panel klubu)
$router->get('/club/api-keys',              [\App\Controllers\ApiKeysController::class, 'index']);
$router->post('/club/api-keys/generate',    [\App\Controllers\ApiKeysController::class, 'generate']);
$router->post('/club/api-keys/:id/revoke',  [\App\Controllers\ApiKeysController::class, 'revoke']);

// Wiadomości wewnętrzne
$router->get('/messages',              [\App\Controllers\MessagesController::class, 'inbox']);
$router->get('/messages/sent',         [\App\Controllers\MessagesController::class, 'sent']);
$router->get('/messages/compose',      [\App\Controllers\MessagesController::class, 'compose']);
$router->post('/messages/store',       [\App\Controllers\MessagesController::class, 'store']);
$router->get('/messages/:id',          [\App\Controllers\MessagesController::class, 'show']);
$router->post('/messages/:id/read',    [\App\Controllers\MessagesController::class, 'markRead']);

// Analityka klubu
$router->get('/analytics',      [\App\Controllers\AnalyticsController::class, 'dashboard']);
$router->get('/analytics/data', [\App\Controllers\AnalyticsController::class, 'data']);

// Rezerwacje obiektów
$router->get('/bookings',                        [\App\Controllers\BookingsController::class, 'index']);
$router->get('/bookings/facilities',             [\App\Controllers\BookingsController::class, 'facilities']);
$router->post('/bookings/facilities/store',      [\App\Controllers\BookingsController::class, 'storeFacility']);
$router->post('/bookings/facilities/:id/delete', [\App\Controllers\BookingsController::class, 'deleteFacility']);
$router->get('/bookings/calendar',               [\App\Controllers\BookingsController::class, 'calendar']);
$router->post('/bookings/book',                  [\App\Controllers\BookingsController::class, 'book']);
$router->post('/bookings/:id/cancel',            [\App\Controllers\BookingsController::class, 'cancel']);

// ── REST API v1 ─────────────
$router->post('/api/v1/auth/login',    [\App\Controllers\Api\AuthApiController::class, 'login']);
$router->get('/api/v1/members',                  [\App\Controllers\Api\MembersApiController::class, 'index']);
$router->get('/api/v1/members/:id',              [\App\Controllers\Api\MembersApiController::class, 'show']);
$router->get('/api/v1/events',                   [\App\Controllers\Api\EventsApiController::class, 'index']);
$router->get('/api/v1/events/upcoming',          [\App\Controllers\Api\EventsApiController::class, 'upcoming']);
$router->get('/api/v1/payments',                 [\App\Controllers\Api\PaymentsApiController::class, 'index']);
$router->get('/api/v1/payments/summary',         [\App\Controllers\Api\PaymentsApiController::class, 'summary']);
$router->get('/api/v1/sports',                   [\App\Controllers\Api\SportsApiController::class, 'index']);
$router->get('/api/v1/sports/catalog',           [\App\Controllers\Api\SportsApiController::class, 'catalog']);
$router->get('/api/v1/sports/:sportId/disciplines', [\App\Controllers\Api\SportsApiController::class, 'disciplines']);

// API: push device tokens
$router->post('/api/v1/devices/register',   [\App\Controllers\Api\DevicesApiController::class, 'register']);
$router->post('/api/v1/devices/unregister', [\App\Controllers\Api\DevicesApiController::class, 'unregister']);

// Sklep klubowy
$router->get('/shop/products',               [\App\Controllers\ShopController::class, 'products']);
$router->get('/shop/products/create',        [\App\Controllers\ShopController::class, 'productForm']);
$router->get('/shop/products/:id/edit',      [\App\Controllers\ShopController::class, 'productForm']);
$router->post('/shop/products/store',        [\App\Controllers\ShopController::class, 'storeProduct']);
$router->post('/shop/products/:id/delete',   [\App\Controllers\ShopController::class, 'deleteProduct']);
$router->get('/pub/:slug/shop',              [\App\Controllers\ShopController::class, 'catalog']);
$router->get('/shop/cart',                   [\App\Controllers\ShopController::class, 'cart']);
$router->post('/shop/cart/add',              [\App\Controllers\ShopController::class, 'addToCart']);
$router->get('/shop/checkout',               [\App\Controllers\ShopController::class, 'checkout']);
$router->post('/shop/checkout/store',        [\App\Controllers\ShopController::class, 'storeOrder']);
$router->get('/shop/confirmation/:id',       [\App\Controllers\ShopController::class, 'orderConfirmation']);
$router->get('/shop/orders',                 [\App\Controllers\ShopController::class, 'orders']);
$router->post('/shop/orders/:id/status',     [\App\Controllers\ShopController::class, 'updateOrderStatus']);

// OCR — zdjecia wynikow
$router->get('/results',                     [\App\Controllers\ResultImageController::class, 'index']);
$router->get('/results/upload',              [\App\Controllers\ResultImageController::class, 'upload']);
$router->post('/results/upload',             [\App\Controllers\ResultImageController::class, 'storeUpload']);
$router->get('/results/:id',                 [\App\Controllers\ResultImageController::class, 'show']);
$router->post('/results/:id/save',           [\App\Controllers\ResultImageController::class, 'save']);
$router->post('/results/:id/delete',         [\App\Controllers\ResultImageController::class, 'deleteImage']);

// ── Licencje sportowe ─────────────────────────────────────
$router->get('/sport-licenses',              [\App\Controllers\SportLicensesController::class, 'index']);
$router->post('/sport-licenses/store',       [\App\Controllers\SportLicensesController::class, 'store']);
$router->post('/sport-licenses/:id/delete',  [\App\Controllers\SportLicensesController::class, 'delete']);

// ── Rankingi sportowe ─────────────────────────────────────
$router->get('/sport-rankings',              [\App\Controllers\SportRankingsController::class, 'index']);
$router->post('/sport-rankings/store',       [\App\Controllers\SportRankingsController::class, 'store']);
$router->post('/sport-rankings/:id/delete',  [\App\Controllers\SportRankingsController::class, 'delete']);
$router->post('/sport-rankings/recalculate', [\App\Controllers\SportRankingsController::class, 'recalculate']);
$router->post('/rankings/recalculate',       [\App\Controllers\SportRankingsController::class, 'recalculate']);

// ── Turnieje ───────────────────────────────────────────────
$router->get('/tournaments',                         [\App\Controllers\TournamentsController::class, 'index']);
$router->get('/tournaments/create',                  [\App\Controllers\TournamentsController::class, 'create']);
$router->post('/tournaments/store',                  [\App\Controllers\TournamentsController::class, 'store']);
$router->get('/tournaments/:id',                     [\App\Controllers\TournamentsController::class, 'show']);
$router->post('/tournaments/:id/participant',        [\App\Controllers\TournamentsController::class, 'addParticipant']);
$router->post('/tournaments/:id/participant-remove', [\App\Controllers\TournamentsController::class, 'removeParticipant']);
$router->post('/tournaments/:id/generate',           [\App\Controllers\TournamentsController::class, 'generateBracket']);
$router->post('/tournaments/match/:matchId/result',  [\App\Controllers\TournamentsController::class, 'recordResult']);
$router->post('/tournaments/:id/delete',             [\App\Controllers\TournamentsController::class, 'delete']);

// ── Trasy z modułów sportowych (plugin-like) ─────────────
\App\Helpers\SportModuleLoader::registerRoutes($router);

// ── Association management ────────────────────────────────
$router->get('/association/meetings',                    [\App\Controllers\AssociationController::class, 'meetings']);
$router->post('/association/meetings/create',            [\App\Controllers\AssociationController::class, 'createMeeting']);
$router->get('/association/meetings/:id',                [\App\Controllers\AssociationController::class, 'showMeeting']);
$router->post('/association/meetings/:id/vote',          [\App\Controllers\AssociationController::class, 'addVote']);
$router->get('/association/board',                       [\App\Controllers\AssociationController::class, 'board']);
$router->post('/association/board/update',               [\App\Controllers\AssociationController::class, 'updateBoard']);

// ============================================================
// Dispatch
// ============================================================
$router->dispatch();
