<?php
// ============================================================
// cli/send_campaigns.php — process pending email/SMS campaigns
// Usage: */5 * * * * php /path/to/cli/send_campaigns.php
//
// Iteruje kampanie w statusie 'sending' lub 'scheduled' (z datą w
// przeszłości) i wysyła do `batchSize` odbiorców per uruchomienie.
// Po wyczerpaniu kolejki kampania przechodzi w status 'sent'.
// ============================================================
declare(strict_types=1);

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

echo "[" . date('Y-m-d H:i:s') . "] Campaign worker starting…\n";

try {
    // Cross-tenant: campaigns z różnych klubów.
    $cModel = new \App\Models\CampaignModel();

    // Pobierz kampanie scheduled w przeszłości — promuj do sending
    $ready = $cModel->fetchReadyToSend(50);
    if (empty($ready)) {
        echo "[" . date('Y-m-d H:i:s') . "] No campaigns to dispatch.\n";
        exit(0);
    }

    $totalSent = 0;
    foreach ($ready as $campaign) {
        // Promote scheduled → sending
        if ($campaign['status'] === 'scheduled') {
            $cModel->markStatus((int)$campaign['id'], 'sending');
        }
        $sent = \App\Controllers\AdminBulkCampaignController::dispatchCampaign(
            (int)$campaign['id'],
            50
        );
        $totalSent += $sent;
        echo sprintf(
            "[%s] Campaign #%d (%s) — dispatched %d recipients\n",
            date('Y-m-d H:i:s'),
            (int)$campaign['id'],
            (string)$campaign['name'],
            $sent
        );
    }

    echo "[" . date('Y-m-d H:i:s') . "] Done. Total dispatched: {$totalSent}\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
