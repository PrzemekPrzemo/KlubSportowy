<?php
// ============================================================
// cli/ksef_send_worker.php — KSeF send queue processor (Phase 3).
//
// Cron: * * * * * /opt/plesk/php/8.3/bin/php /var/www/clubdesk/cli/ksef_send_worker.php >> /var/log/ksef.log 2>&1
//
// Per uruchomienie bierze do 10 wpisow z `ksef_send_queue` (SKIP LOCKED) i
// przechodzi po state machine: queued → signing → sending → awaiting_upo →
// completed (lub retrying / failed po MAX_ATTEMPTS).
//
// Retry policy: 1m, 5m, 30m, 2h, 12h. Max 5 prob → 'failed' wymaga manual
// force-retry przez super admina (/admin/platform/ksef/queue).
// ============================================================
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap/php_version_check.php';

define('ROOT_PATH', dirname(__DIR__));

// Autoload (App\ namespace)
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

$batchSize = (int)($argv[1] ?? 10);
if ($batchSize <= 0 || $batchSize > 100) $batchSize = 10;

echo '[' . date('Y-m-d H:i:s') . "] KSeF send worker starting (batch={$batchSize})...\n";

try {
    $worker    = new \App\Helpers\Ksef\KsefSendWorker();
    $processed = $worker->processBatch($batchSize);
    echo '[' . date('Y-m-d H:i:s') . "] Processed: {$processed}\n";
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}
