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

set_exception_handler(function (Throwable $e) use ($debugMode): void {
    http_response_code(500);
    error_log(sprintf("[%s] %s: %s in %s:%d\n%s\n",
        date('Y-m-d H:i:s'), get_class($e), $e->getMessage(),
        $e->getFile(), $e->getLine(), $e->getTraceAsString()
    ));
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

// Strona startowa → login
$router->get('/', [\App\Controllers\AuthController::class, 'showLogin']);

// Auth
$router->get('/auth/login',  [\App\Controllers\AuthController::class, 'showLogin']);
$router->post('/auth/login', [\App\Controllers\AuthController::class, 'login']);
$router->get('/auth/logout', [\App\Controllers\AuthController::class, 'logout']);

// Rejestracja publiczna klubu
$router->get('/register',  [\App\Controllers\AuthController::class, 'showRegister']);
$router->post('/register', [\App\Controllers\AuthController::class, 'register']);

// Wybór klubu po logowaniu
$router->get('/club-select',      [\App\Controllers\ClubSelectorController::class, 'show']);
$router->post('/club-select/:id', [\App\Controllers\ClubSelectorController::class, 'select']);

// Dashboard
$router->get('/dashboard', [\App\Controllers\DashboardController::class, 'index']);

// Admin (super admin)
$router->get('/admin/dashboard',            [\App\Controllers\AdminController::class, 'dashboard']);
$router->get('/admin/clubs',                [\App\Controllers\AdminController::class, 'clubs']);
$router->get('/admin/clubs/create',         [\App\Controllers\AdminController::class, 'createClub']);
$router->post('/admin/clubs/create',        [\App\Controllers\AdminController::class, 'storeClub']);
$router->get('/admin/clubs/:id/edit',       [\App\Controllers\AdminController::class, 'editClub']);
$router->post('/admin/clubs/:id/edit',      [\App\Controllers\AdminController::class, 'updateClub']);
$router->post('/admin/switch-club/:id',     [\App\Controllers\AdminController::class, 'switchClub']);
$router->get('/admin/sports',               [\App\Controllers\AdminController::class, 'sportsCatalog']);
$router->get('/admin/plans',                [\App\Controllers\AdminController::class, 'plans']);
$router->get('/admin/activity',             [\App\Controllers\AdminController::class, 'activityLog']);
$router->get('/admin/clubs/:id/users',      [\App\Controllers\AdminController::class, 'clubUsers']);
$router->post('/admin/clubs/:id/users/:userId/impersonate', [\App\Controllers\AdminController::class, 'impersonate']);

// Admin: demo tokeny
$router->get('/admin/demos',           [\App\Controllers\DemoController::class, 'index']);
$router->post('/admin/demos/create',   [\App\Controllers\DemoController::class, 'create']);
$router->post('/admin/demos/cleanup',  [\App\Controllers\DemoController::class, 'cleanup']);

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

// Impersonacja — zakończenie (dla zalogowanego impersonującego, nie wymaga super-admin)
$router->post('/impersonate/stop', [\App\Controllers\ImpersonationController::class, 'stop']);

// Demo — publiczny dostep przez token
$router->get('/demo/:token', [\App\Controllers\DemoController::class, 'loginViaToken']);

// Strony publiczne (bez logowania)
$router->get('/pub',                 [\App\Controllers\PublicController::class, 'clubList']);
$router->get('/pub/:slug/results',   [\App\Controllers\PublicController::class, 'clubResults']);
$router->get('/pub/:slug',           [\App\Controllers\PublicController::class, 'clubPage']);

// Sekcje sportowe w kontekście klubu
$router->get('/sports',                [\App\Controllers\SportsController::class, 'index']);
$router->post('/sports/enable',        [\App\Controllers\SportsController::class, 'enable']);
$router->post('/sports/disable/:id',   [\App\Controllers\SportsController::class, 'disable']);
$router->post('/sports/activate/:id',  [\App\Controllers\SportsController::class, 'activate']);
$router->post('/sports/clear-active',  [\App\Controllers\SportsController::class, 'clearActive']);

// GDPR
$router->get('/gdpr',                            [\App\Controllers\GdprController::class, 'index']);
$router->get('/gdpr/member/:memberId',            [\App\Controllers\GdprController::class, 'memberConsents']);
$router->post('/gdpr/member/:memberId/grant',     [\App\Controllers\GdprController::class, 'grantConsent']);
$router->post('/gdpr/member/:memberId/revoke',    [\App\Controllers\GdprController::class, 'revokeConsent']);
$router->get('/gdpr/member/:memberId/export',     [\App\Controllers\GdprController::class, 'exportData']);
$router->post('/gdpr/member/:memberId/anonymize', [\App\Controllers\GdprController::class, 'anonymize']);

// Zawodnicy
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
$router->post('/fees/rates/:id/delete',    [\App\Controllers\FeesController::class, 'deleteRate']);
$router->get('/fees/new',                  [\App\Controllers\FeesController::class, 'createPayment']);
$router->post('/fees/store',               [\App\Controllers\FeesController::class, 'storePayment']);

// 2FA (TOTP)
$router->get('/2fa/setup',     [\App\Controllers\TwoFactorController::class, 'setup']);
$router->post('/2fa/confirm',  [\App\Controllers\TwoFactorController::class, 'confirm']);
$router->post('/2fa/disable',  [\App\Controllers\TwoFactorController::class, 'disable']);
$router->get('/2fa/verify',    [\App\Controllers\TwoFactorController::class, 'verify']);
$router->post('/2fa/verify',   [\App\Controllers\TwoFactorController::class, 'verifyCode']);

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

// Stripe/P24 Webhook (no CSRF — signed)
$router->post('/webhook/payment', [\App\Controllers\PaymentWebhookController::class, 'handle']);

// Portal zawodnika (self-service)
$router->get('/portal/login',            [\App\Controllers\MemberPortalController::class, 'showLogin']);
$router->post('/portal/login',           [\App\Controllers\MemberPortalController::class, 'login']);
$router->get('/portal/logout',           [\App\Controllers\MemberPortalController::class, 'logout']);
$router->get('/portal/dashboard',        [\App\Controllers\MemberPortalController::class, 'dashboard']);
$router->get('/portal/profile',          [\App\Controllers\MemberPortalController::class, 'profile']);
$router->post('/portal/profile/update',  [\App\Controllers\MemberPortalController::class, 'updateProfile']);
$router->post('/portal/password',        [\App\Controllers\MemberPortalController::class, 'changePassword']);
$router->get('/portal/fees',             [\App\Controllers\MemberPortalController::class, 'fees']);
$router->get('/portal/events',           [\App\Controllers\MemberPortalController::class, 'events']);

// Powiadomienia (dzwoneczek)
$router->post('/notifications/:id/read', [\App\Controllers\NotificationsController::class, 'markRead']);

// Szablony e-mail + kolejka
$router->get('/email/templates',                [\App\Controllers\EmailTemplatesController::class, 'index']);
$router->get('/email/templates/:type',          [\App\Controllers\EmailTemplatesController::class, 'edit']);
$router->post('/email/templates/:type/save',    [\App\Controllers\EmailTemplatesController::class, 'save']);
$router->get('/email/queue',                    [\App\Controllers\EmailTemplatesController::class, 'queue']);

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

// Wydarzenia
$router->get('/events',               [\App\Controllers\EventsController::class, 'index']);
$router->get('/events/create',        [\App\Controllers\EventsController::class, 'create']);
$router->post('/events/store',        [\App\Controllers\EventsController::class, 'store']);
$router->post('/events/:id/delete',   [\App\Controllers\EventsController::class, 'delete']);

// Raporty
$router->get('/reports',                    [\App\Controllers\ReportsController::class, 'index']);
$router->get('/reports/members-pdf',        [\App\Controllers\ReportsController::class, 'membersPdf']);
$router->get('/reports/members-csv',        [\App\Controllers\ReportsController::class, 'membersCsv']);
$router->get('/reports/finances-pdf',       [\App\Controllers\ReportsController::class, 'financesPdf']);
$router->get('/reports/finances-csv',       [\App\Controllers\ReportsController::class, 'financesCsv']);
$router->get('/reports/event-protocol/:id', [\App\Controllers\ReportsController::class, 'eventProtocolPdf']);
$router->get('/reports/member-card/:id',    [\App\Controllers\ReportsController::class, 'memberCardPdf']);

// Klucze API (panel klubu)
$router->get('/club/api-keys',              [\App\Controllers\ApiKeysController::class, 'index']);
$router->post('/club/api-keys/generate',    [\App\Controllers\ApiKeysController::class, 'generate']);
$router->post('/club/api-keys/:id/revoke',  [\App\Controllers\ApiKeysController::class, 'revoke']);

// ── REST API v1 (Bearer token auth, no CSRF) ─────────────
$router->get('/api/v1/members',                  [\App\Controllers\Api\MembersApiController::class, 'index']);
$router->get('/api/v1/members/:id',              [\App\Controllers\Api\MembersApiController::class, 'show']);
$router->get('/api/v1/events',                   [\App\Controllers\Api\EventsApiController::class, 'index']);
$router->get('/api/v1/events/upcoming',          [\App\Controllers\Api\EventsApiController::class, 'upcoming']);
$router->get('/api/v1/payments',                 [\App\Controllers\Api\PaymentsApiController::class, 'index']);
$router->get('/api/v1/payments/summary',         [\App\Controllers\Api\PaymentsApiController::class, 'summary']);
$router->get('/api/v1/sports',                   [\App\Controllers\Api\SportsApiController::class, 'index']);
$router->get('/api/v1/sports/catalog',           [\App\Controllers\Api\SportsApiController::class, 'catalog']);
$router->get('/api/v1/sports/:sportId/disciplines', [\App\Controllers\Api\SportsApiController::class, 'disciplines']);

// ── Trasy z modułów sportowych (plugin-like) ─────────────
\App\Helpers\SportModuleLoader::registerRoutes($router);

// ============================================================
// Dispatch
// ============================================================
$router->dispatch();
