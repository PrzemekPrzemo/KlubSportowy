<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap/php_version_check.php';

// ============================================================
// cli/scan_trainer_conflicts.php
//
// Nightly skan konfliktow planowania trenerow dla najblizszych 14 dni.
// Wykryte nowe konflikty -> INSERT do trainer_schedule_conflicts +
// best-effort email do zarzadu klubu.
//
// Usage:
//   0 3 * * * /opt/plesk/php/8.3/bin/php /path/to/cli/scan_trainer_conflicts.php
// ============================================================

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

use App\Helpers\Database;
use App\Helpers\EmailService;
use App\Helpers\Scheduling\TrainerScheduleService;

$pdo = Database::pdo();
$svc = new TrainerScheduleService($pdo);

$from = new DateTimeImmutable('now');
$to   = $from->modify('+14 days');

echo "[" . date('Y-m-d H:i:s') . "] scan_trainer_conflicts START\n";

// Iteruj po wszystkich klubach
$clubs = $pdo->query("SELECT id, name FROM clubs")->fetchAll(PDO::FETCH_ASSOC);
$totalNew = 0;

foreach ($clubs as $club) {
    $clubId = (int)$club['id'];
    $results = $svc->scanClub($clubId, $from, $to);
    if (empty($results)) continue;

    $newConflictsForClub = [];
    foreach ($results as $r) {
        // Filter — pomin konflikty juz zapisane (taki sam user/training/type/starts_at).
        $deDupStmt = $pdo->prepare(
            "SELECT 1 FROM trainer_schedule_conflicts
             WHERE user_id = ? AND COALESCE(training_id, 0) = ? AND conflict_type = ?
               AND starts_at = ?
             LIMIT 1"
        );
        $newConflicts = [];
        foreach ($r['conflicts'] as $c) {
            $deDupStmt->execute([
                $r['user_id'],
                (int)($r['training_id'] ?? 0),
                $c['type'],
                $c['starts_at'],
            ]);
            if (!$deDupStmt->fetchColumn()) {
                $newConflicts[] = $c;
            }
        }
        if (empty($newConflicts)) continue;
        $svc->persistConflicts($r['user_id'], $clubId, $r['training_id'], $newConflicts);
        $totalNew += count($newConflicts);
        $newConflictsForClub = array_merge($newConflictsForClub, array_map(static function($c) use ($r) {
            $c['training_id'] = $r['training_id'];
            $c['user_id']     = $r['user_id'];
            return $c;
        }, $newConflicts));
    }

    if (!empty($newConflictsForClub)) {
        echo "  club #{$clubId} ({$club['name']}): " . count($newConflictsForClub) . " new conflict(s)\n";

        // Best-effort email do zarzadu klubu
        try {
            $adminStmt = $pdo->prepare(
                "SELECT DISTINCT u.email, u.full_name FROM users u
                 JOIN user_clubs uc ON uc.user_id = u.id
                 WHERE uc.club_id = ? AND uc.role IN ('zarzad','admin') AND uc.is_active = 1
                   AND u.email IS NOT NULL AND u.email <> ''"
            );
            $adminStmt->execute([$clubId]);
            $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($admins)) {
                $lines = ['Wykryto nowe konflikty w planowaniu trenerow:'];
                foreach (array_slice($newConflictsForClub, 0, 10) as $c) {
                    $lines[] = sprintf('  - [%s] %s (trener #%d)',
                        $c['type'], $c['details'] ?? '', (int)($c['user_id'] ?? 0));
                }
                $lines[] = '';
                $lines[] = 'Sprawdz w panelu: /club/trainer-schedule';
                $body = implode("\n", $lines);

                foreach ($admins as $a) {
                    try {
                        EmailService::send(
                            $clubId,
                            (string)$a['email'],
                            'Nowe konflikty planowania trenerow (' . count($newConflictsForClub) . ')',
                            $body,
                            (string)($a['full_name'] ?? '')
                        );
                    } catch (\Throwable $e) {
                        error_log('scan_trainer_conflicts email failed: ' . $e->getMessage());
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('scan_trainer_conflicts admin lookup failed: ' . $e->getMessage());
        }
    }
}

echo "[" . date('Y-m-d H:i:s') . "] scan_trainer_conflicts DONE — {$totalNew} new conflict(s) persisted\n";
