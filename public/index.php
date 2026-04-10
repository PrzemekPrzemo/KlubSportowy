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

// Sekcje sportowe w kontekście klubu
$router->get('/sports',                [\App\Controllers\SportsController::class, 'index']);
$router->post('/sports/enable',        [\App\Controllers\SportsController::class, 'enable']);
$router->post('/sports/disable/:id',   [\App\Controllers\SportsController::class, 'disable']);
$router->post('/sports/activate/:id',  [\App\Controllers\SportsController::class, 'activate']);
$router->post('/sports/clear-active',  [\App\Controllers\SportsController::class, 'clearActive']);

// Zawodnicy
$router->get('/members',              [\App\Controllers\MembersController::class, 'index']);
$router->get('/members/create',       [\App\Controllers\MembersController::class, 'create']);
$router->post('/members/store',       [\App\Controllers\MembersController::class, 'store']);
$router->get('/members/:id',          [\App\Controllers\MembersController::class, 'show']);
$router->get('/members/:id/edit',     [\App\Controllers\MembersController::class, 'edit']);
$router->post('/members/:id/update',  [\App\Controllers\MembersController::class, 'update']);
$router->post('/members/:id/delete',  [\App\Controllers\MembersController::class, 'delete']);

// Finanse
$router->get('/fees',                      [\App\Controllers\FeesController::class, 'index']);
$router->get('/fees/rates',                [\App\Controllers\FeesController::class, 'rates']);
$router->post('/fees/rates/store',         [\App\Controllers\FeesController::class, 'storeRate']);
$router->post('/fees/rates/:id/delete',    [\App\Controllers\FeesController::class, 'deleteRate']);
$router->get('/fees/new',                  [\App\Controllers\FeesController::class, 'createPayment']);
$router->post('/fees/store',               [\App\Controllers\FeesController::class, 'storePayment']);

// Wydarzenia
$router->get('/events',               [\App\Controllers\EventsController::class, 'index']);
$router->get('/events/create',        [\App\Controllers\EventsController::class, 'create']);
$router->post('/events/store',        [\App\Controllers\EventsController::class, 'store']);
$router->post('/events/:id/delete',   [\App\Controllers\EventsController::class, 'delete']);

// ── Trasy z modułów sportowych (plugin-like) ─────────────
\App\Helpers\SportModuleLoader::registerRoutes($router);

// ============================================================
// Dispatch
// ============================================================
$router->dispatch();
