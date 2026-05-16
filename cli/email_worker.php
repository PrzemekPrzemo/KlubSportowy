<?php
// ============================================================
// cli/email_worker.php — process email queue (invoke via cron)
// Usage: * * * * * php /path/to/cli/email_worker.php
// ============================================================
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap/php_version_check.php';

define('ROOT_PATH', dirname(__DIR__));

// Autoload
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = ROOT_PATH . '/app/' . $relative . '.php';
    if (file_exists($file)) require $file;
});

$vendor = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($vendor)) require $vendor;

// App config
$localApp = ROOT_PATH . '/config/app.local.php';
$cfg = file_exists($localApp) ? require $localApp : require ROOT_PATH . '/config/app.php';
date_default_timezone_set($cfg['timezone'] ?? 'Europe/Warsaw');

echo "[" . date('Y-m-d H:i:s') . "] Email worker starting…\n";

try {
    $sent = \App\Helpers\EmailService::processQueue(50);
    echo "[" . date('Y-m-d H:i:s') . "] Sent {$sent} emails from queue\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
