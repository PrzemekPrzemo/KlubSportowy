<?php
// ============================================================
// cli/webhook_worker.php — process pending webhook deliveries (cron)
// Usage: * * * * * php /path/to/cli/webhook_worker.php
//
// Bierze batch do 100 pending/retrying deliveries, probuje dostarczyc
// z HMAC-SHA256 signing, timeout 5s per request. Retry policy z
// exponential backoff (1m, 5m, 30m, 2h, 12h) w WebhookDispatcher.
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

$localApp = ROOT_PATH . '/config/app.local.php';
$cfg = file_exists($localApp) ? require $localApp : require ROOT_PATH . '/config/app.php';
date_default_timezone_set($cfg['timezone'] ?? 'Europe/Warsaw');

echo '[' . date('Y-m-d H:i:s') . "] Webhook worker starting...\n";

try {
    $processed = \App\Helpers\Webhooks\WebhookDispatcher::deliverPending(100);
    echo '[' . date('Y-m-d H:i:s') . "] Processed {$processed} webhook deliveries.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}
