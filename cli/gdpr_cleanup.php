<?php
// ============================================================
// cli/gdpr_cleanup.php
//
// Cron: auto-cleanup wygasnietych eksportow GDPR (>7 dni).
//
// Schedule:
//   0 3 * * * /usr/bin/php /var/www/.../cli/gdpr_cleanup.php
// ============================================================
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = ROOT_PATH . '/app/' . $relative . '.php';
    if (file_exists($file)) require $file;
});

$vendor = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($vendor)) require $vendor;

$localApp = ROOT_PATH . '/config/app.local.php';
$cfg = file_exists($localApp) ? require $localApp : require ROOT_PATH . '/config/app.php';
date_default_timezone_set($cfg['timezone'] ?? 'Europe/Warsaw');

echo "[" . date('Y-m-d H:i:s') . "] GDPR cleanup cron starting...\n";

try {
    $deleted = \App\Helpers\GdprService::pruneExpiredExports();
    echo "  Usunieto $deleted plikow wygasnietych eksportow GDPR.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "BLAD: " . $e->getMessage() . "\n");
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Done.\n";
