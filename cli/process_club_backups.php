<?php
// ============================================================
// cli/process_club_backups.php
//
// Worker async dla pelnych backupow klubu (ClubExporter -> ZIP).
//
// Iteruje wpisy `club_backups WHERE status='in_progress'` i przerabia
// maks 3 na uruchomienie (long-running, max ~kilka minut na backup).
//
// Usage:
//   php cli/process_club_backups.php           # max 3 zadania
//   php cli/process_club_backups.php --max=10  # zmien limit
//   php cli/process_club_backups.php --id=42   # konkretny backup
//
// Uruchom przez cron co 5 minut:
//   */5 * * * * php /var/www/clubdesk/cli/process_club_backups.php
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

require ROOT_PATH . '/app/Helpers/Helpers.php';

$max  = 3;
$only = null;
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--max=(\d+)$/', $arg, $m)) {
        $max = (int)$m[1];
    } elseif (preg_match('/^--id=(\d+)$/', $arg, $m)) {
        $only = (int)$m[1];
    } elseif ($arg === '-h' || $arg === '--help') {
        echo "Usage: php cli/process_club_backups.php [--max=N] [--id=N]\n";
        exit(0);
    }
}

$db = \App\Helpers\Database::pdo();

if ($only !== null) {
    $stmt = $db->prepare("SELECT id, club_id FROM club_backups WHERE id = ? AND status='in_progress' LIMIT 1");
    $stmt->execute([$only]);
} else {
    $stmt = $db->prepare(
        "SELECT id, club_id FROM club_backups
         WHERE status='in_progress'
         ORDER BY id ASC LIMIT " . (int)$max
    );
    $stmt->execute();
}

$jobs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
if (empty($jobs)) {
    echo "Brak zadan do przetworzenia.\n";
    exit(0);
}

$exporter = new \App\Helpers\Backup\ClubExporter();

foreach ($jobs as $job) {
    $bid = (int)$job['id'];
    $cid = (int)$job['club_id'];
    echo "→ Backup #{$bid} (club={$cid})\n";

    try {
        $path = $exporter->export($cid, $bid);
        $size = is_file($path) ? filesize($path) : 0;
        echo "  ok: {$path} (" . round($size / 1024, 1) . " KB)\n";
    } catch (\Throwable $e) {
        echo "  FAIL: " . $e->getMessage() . "\n";
        try {
            $upd = $db->prepare(
                "UPDATE club_backups SET status='failed', error_message=?, completed_at=NOW() WHERE id=?"
            );
            $upd->execute([$e->getMessage(), $bid]);
        } catch (\Throwable) {}
    }
}

echo "Done.\n";
