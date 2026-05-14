<?php
// ============================================================
// cli/google_calendar_sync.php — Google Calendar sync runner
//
// Iteruje wszystkie kluby z club_google_calendar.is_active=1 i wykonuje
// GoogleCalendarSyncer::syncClub() dla każdego. Cron-friendly — uruchamiać
// co 15 minut:
//
//   */15 * * * * /usr/bin/php /var/www/clubdesk/cli/google_calendar_sync.php
//
// Tryb dry-run (tylko log, bez wywołań Google API):
//   php cli/google_calendar_sync.php --dry-run
//
// Filtr per-klub:
//   php cli/google_calendar_sync.php --club=42
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

// Wczytaj globalne helpery (url(), csrf_field() etc.) — niektóre helpery
// mogą być potrzebne pośrednio.
$helpers = ROOT_PATH . '/app/Helpers/Helpers.php';
if (file_exists($helpers)) require_once $helpers;

$localApp = ROOT_PATH . '/config/app.local.php';
$cfg = file_exists($localApp) ? require $localApp : require ROOT_PATH . '/config/app.php';
date_default_timezone_set($cfg['timezone'] ?? 'Europe/Warsaw');

$dryRun = in_array('--dry-run', $argv ?? [], true);
$onlyClub = null;
foreach ($argv ?? [] as $a) {
    if (str_starts_with($a, '--club=')) {
        $onlyClub = (int)substr($a, 7);
    }
}

$ts = function (): string { return '[' . date('Y-m-d H:i:s') . ']'; };
echo "{$ts()} Google Calendar sync runner " . ($dryRun ? '(DRY RUN) ' : '') . "starting…\n";

try {
    $model = new \App\Models\ClubGoogleCalendarModel();
    $active = $model->listActive();
} catch (\Throwable $e) {
    echo "{$ts()} BŁĄD inicjalizacji: " . $e->getMessage() . "\n";
    exit(2);
}

if ($onlyClub !== null) {
    $active = array_values(array_filter($active, fn(array $r): bool => (int)$r['club_id'] === $onlyClub));
}

echo "{$ts()} → " . count($active) . " aktywnych integracji do przetworzenia\n";

$totalPushed = 0;
$totalPulled = 0;
$totalUpdated = 0;
$totalDeleted = 0;
$totalErrors = 0;

foreach ($active as $row) {
    $clubId   = (int)$row['club_id'];
    $clubName = (string)($row['club_name'] ?? "Klub #{$clubId}");
    echo "{$ts()}   • Klub {$clubId} '{$clubName}'… ";

    if ($dryRun) {
        echo "DRY-RUN (skip)\n";
        continue;
    }

    try {
        $r = \App\Helpers\Calendar\GoogleCalendarSyncer::syncClub($clubId);
    } catch (\Throwable $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
        $totalErrors++;
        continue;
    }

    $totalPushed  += $r['pushed'];
    $totalPulled  += $r['pulled'];
    $totalUpdated += $r['updated'];
    $totalDeleted += $r['deleted'];
    $totalErrors  += count($r['errors']);

    echo sprintf(
        "pushed=%d updated=%d pulled=%d deleted=%d errors=%d\n",
        $r['pushed'], $r['updated'], $r['pulled'], $r['deleted'], count($r['errors'])
    );
    foreach ($r['errors'] as $err) {
        echo "      ! {$err}\n";
    }
}

echo "{$ts()} ✓ Done — pushed={$totalPushed} updated={$totalUpdated} pulled={$totalPulled} deleted={$totalDeleted} errors={$totalErrors}\n";

exit($totalErrors > 0 ? 1 : 0);
