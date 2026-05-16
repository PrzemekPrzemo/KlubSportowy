<?php
// ============================================================
// cli/evaluate_achievements.php
//
// Nightly batch evaluation: dla kazdego aktywnego klubu uruchamia
// AchievementEvaluator::evaluateAllInClub, ktore z kolei iteruje
// po aktywnych czlonkach i przyznaje brakujace odznaki.
//
// Idempotentne — UNIQUE KEY (member_id, achievement_id) w
// member_achievements zapobiega duplikatom.
//
// Cron (przyklad):
//   0 2 * * * /opt/plesk/php/8.2/bin/php /var/www/.../cli/evaluate_achievements.php
//
// Flagi:
//   --club=ID   tylko dla wybranego klubu
//   --dry-run   nic nie zapisuj (na razie nie wspierane — evaluator zawsze zapisuje)
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

$clubFilter = null;
foreach ($argv ?? [] as $a) {
    if (str_starts_with($a, '--club=')) {
        $clubFilter = (int)substr($a, 7);
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Achievements evaluator starting"
    . ($clubFilter ? " (club={$clubFilter})" : ' (all clubs)')
    . "\n";

$db = \App\Helpers\Database::pdo();
$sql = "SELECT id, name FROM clubs WHERE is_active = 1";
$params = [];
if ($clubFilter !== null) {
    $sql .= " AND id = ?";
    $params[] = $clubFilter;
}
$sql .= " ORDER BY id";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grandMembers = 0;
$grandAwards  = 0;

foreach ($clubs as $club) {
    $cid = (int)$club['id'];
    $tStart = microtime(true);
    try {
        $stats = \App\Helpers\Achievements\AchievementEvaluator::evaluateAllInClub($cid);
    } catch (\Throwable $e) {
        echo "  ! Club {$cid} '{$club['name']}' failed: " . $e->getMessage() . "\n";
        continue;
    }
    $elapsed = round((microtime(true) - $tStart) * 1000);
    echo "  - Club {$cid} '{$club['name']}': "
        . "{$stats['members_evaluated']} members, "
        . "{$stats['awards_total']} nowe odznaki ({$elapsed} ms)\n";
    $grandMembers += $stats['members_evaluated'];
    $grandAwards  += $stats['awards_total'];
}

echo "[" . date('Y-m-d H:i:s') . "] Done. "
    . count($clubs) . " klubów, {$grandMembers} czlonkow, {$grandAwards} nowych odznak.\n";
