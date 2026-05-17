<?php
// ============================================================
// cli/republish_finished_tournaments.php
//
// Backfill PDF protokolow dla istniejacych turniejow `status='finished'`,
// ktore nie maja wpisu w `tournament_protocols` (np. zakonczone przed
// wdrozeniem auto-publish, lub stworzone przez import/CLI).
//
// Uzycie:
//   php cli/republish_finished_tournaments.php
//   php cli/republish_finished_tournaments.php --since=2026-01-01
//   php cli/republish_finished_tournaments.php --since=2026-01-01 --limit=50
//   php cli/republish_finished_tournaments.php --dry-run
//
// Cron (raz dziennie, np. 03:30):
//   30 3 * * * /opt/plesk/php/8.3/bin/php /var/www/.../cli/republish_finished_tournaments.php
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

// Helpers (url helper) — opcjonalne, ProtocolPublisher fallbackuje gdy brak.
$helpers = ROOT_PATH . '/app/Helpers/Helpers.php';
if (file_exists($helpers)) require_once $helpers;

$localApp = ROOT_PATH . '/config/app.local.php';
$cfg = file_exists($localApp) ? require $localApp : require ROOT_PATH . '/config/app.php';
date_default_timezone_set($cfg['timezone'] ?? 'Europe/Warsaw');

// --- args ---
$since  = null;
$limit  = 100;
$dryRun = false;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--since=')) {
        $since = substr($arg, 8);
        // Walidacja YYYY-MM-DD.
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $since)) {
            fwrite(STDERR, "Invalid --since format (use YYYY-MM-DD): {$since}\n");
            exit(1);
        }
    } elseif (str_starts_with($arg, '--limit=')) {
        $limit = max(1, min(1000, (int)substr($arg, 8)));
    } elseif ($arg === '--dry-run') {
        $dryRun = true;
    }
}

$ts = static fn() => '[' . date('Y-m-d H:i:s') . ']';
echo $ts() . " Republish finished tournaments — since="
     . ($since ?? '(all)') . " limit={$limit}"
     . ($dryRun ? ' DRY-RUN' : '') . "\n";

try {
    $model = new \App\Models\TournamentProtocolModel();
} catch (\Throwable $e) {
    fwrite(STDERR, "DB init failed: " . $e->getMessage() . "\n");
    exit(2);
}

$rows = $model->finishedTournamentsWithoutProtocol($since);
$total = count($rows);
echo $ts() . " Found {$total} tournaments without protocol.\n";

if ($total === 0) {
    echo $ts() . " Nothing to do.\n";
    exit(0);
}

if ($dryRun) {
    foreach (array_slice($rows, 0, $limit) as $t) {
        echo "  [dry] would publish tournament #{$t['id']} ({$t['name']}) club={$t['club_id']}\n";
    }
    exit(0);
}

$publisher = new \App\Helpers\Tournaments\ProtocolPublisher();
$ok = 0; $fail = 0;
foreach (array_slice($rows, 0, $limit) as $t) {
    $tid = (int)$t['id'];
    try {
        $result = $publisher->publish($tid);
        $ok++;
        echo $ts() . " OK   #{$tid} ({$t['name']}) -> v{$result['version']} {$result['path']}\n";
    } catch (\Throwable $e) {
        $fail++;
        fwrite(STDERR, $ts() . " FAIL #{$tid} ({$t['name']}): " . $e->getMessage() . "\n");
    }
}

echo $ts() . " Done. Published={$ok} Failed={$fail}\n";
exit($fail > 0 ? 1 : 0);
