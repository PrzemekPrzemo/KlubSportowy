<?php
// ============================================================
// cli/run_scheduled_reports.php
//
// Cron worker dla scheduled PDF dashboards (migracja 096).
// Iteruje scheduled_reports WHERE active=1 AND next_send_at <= NOW(),
// generuje PDF, zapisuje pod storage/reports/{club}/, wysyla email
// do recipient_emails (z PDF jako attachment).
//
// Cron schedule (zalecane co godzine):
//   0 * * * * /opt/plesk/php/8.2/bin/php /var/www/.../cli/run_scheduled_reports.php
//
// Flags:
//   --limit=N   (domyslnie 50)
//   --dry-run   (nie generuje, tylko liczy due)
// ============================================================
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap/php_version_check.php';

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

$limit = 50;
$dryRun = false;
foreach ($argv ?? [] as $arg) {
    if ($arg === '--dry-run') {
        $dryRun = true;
    } elseif (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $limit = (int)$m[1];
    }
}

$stamp = date('Y-m-d H:i:s');
echo "[{$stamp}] Scheduled reports worker " . ($dryRun ? '(DRY RUN)' : 'starting…') . "\n";

try {
    $pdo = \App\Helpers\Database::pdo();
    $stmt = $pdo->query(
        "SELECT COUNT(*) FROM scheduled_reports
         WHERE active = 1 AND next_send_at IS NOT NULL AND next_send_at <= NOW()"
    );
    $dueCount = (int)$stmt->fetchColumn();
    echo "  → Due: {$dueCount} report(s)\n";

    if ($dryRun) {
        echo "  → DRY RUN — nothing sent.\n";
        exit(0);
    }
    if ($dueCount === 0) {
        echo "  → Nothing to do.\n";
        exit(0);
    }

    $runner = new \App\Helpers\Reports\ScheduledReportRunner();
    $count  = $runner->processDue($limit);

    echo "[" . date('Y-m-d H:i:s') . "] Processed: {$count}\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
