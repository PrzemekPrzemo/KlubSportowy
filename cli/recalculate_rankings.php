<?php
// ============================================================
// cli/recalculate_rankings.php
//
// Pełne auto-przeliczenie rankingów sportowych (RankingEngine).
// Iteruje po sportach z aktywnymi/zakończonymi turniejami w bieżącym
// sezonie i replayuje wyniki przez wybraną strategię (elo/league_points/best_time).
//
// Użycie:
//   php cli/recalculate_rankings.php
//   php cli/recalculate_rankings.php --sport=tennis
//   php cli/recalculate_rankings.php --sport=football --season=2025
//
// Cron (codzienny, np. 02:30):
//   30 2 * * * /usr/bin/php /var/www/.../cli/recalculate_rankings.php
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

// --- args ---
$onlySport = null;
$season    = null;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--sport=')) {
        $onlySport = substr($arg, 8);
    } elseif (str_starts_with($arg, '--season=')) {
        $season = substr($arg, 9);
    }
}
$season ??= (string)(int)date('Y');

$ts = static fn() => '[' . date('Y-m-d H:i:s') . ']';
echo $ts() . " Ranking recalc — season={$season}" . ($onlySport ? " sport={$onlySport}" : '') . "\n";

$db = \App\Helpers\Database::pdo();

// Wybierz sporty do przeliczenia: dystynktne klucze z tournaments.
$sql = "SELECT DISTINCT sport_key FROM `tournaments`
        WHERE YEAR(date_start) = ?
          AND status IN ('finished','active')";
$params = [(int)$season];
if ($onlySport !== null) {
    $sql .= " AND sport_key = ?";
    $params[] = $onlySport;
}
$sql .= " ORDER BY sport_key";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$sports = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'sport_key');

if ($sports === []) {
    echo $ts() . " No sports with finished/active tournaments in season {$season}.\n";
    exit(0);
}

$totalMembers = 0;
foreach ($sports as $sportKey) {
    try {
        $result = \App\Helpers\Ranking\RankingEngine::recalculateForSport($sportKey, $season);
        $count  = count($result);
        $totalMembers += $count;
        echo $ts() . " sport={$sportKey} → przeliczono {$count} członków\n";
    } catch (\Throwable $e) {
        echo $ts() . " sport={$sportKey} ERROR: " . $e->getMessage() . "\n";
    }
}

echo $ts() . " Done. Sports: " . count($sports) . ", member entries updated: {$totalMembers}\n";
exit(0);
