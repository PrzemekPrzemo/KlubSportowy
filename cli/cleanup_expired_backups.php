<?php
// ============================================================
// cli/cleanup_expired_backups.php
//
// Codzienny cron — usuwa pliki backupow gdzie expires_at < NOW().
// Wpis w club_backups pozostaje (audit trail), ale plik znika z dysku,
// status zmieniany jest na 'failed' z error_message='expired' (zeby
// UI poprawnie pokazal "wygasl").
//
// Usage:
//   php cli/cleanup_expired_backups.php
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

$db = \App\Helpers\Database::pdo();

$stmt = $db->prepare(
    "SELECT id, backup_path FROM club_backups
     WHERE expires_at IS NOT NULL AND expires_at < NOW()
       AND backup_path IS NOT NULL"
);
$stmt->execute();
$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo "Brak wygasajacych backupow.\n";
    exit(0);
}

$base = realpath(ROOT_PATH . '/storage/backups');
$deleted = 0;

foreach ($rows as $row) {
    $abs  = ROOT_PATH . '/' . ltrim((string)$row['backup_path'], '/');
    $real = realpath($abs);

    if ($real !== false && $base !== false && str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
        if (@unlink($real)) {
            $deleted++;
            echo "  deleted: {$real}\n";
        }
    }

    try {
        $upd = $db->prepare(
            "UPDATE club_backups SET status='failed', error_message='expired', backup_path=NULL WHERE id=?"
        );
        $upd->execute([(int)$row['id']]);
    } catch (\Throwable) {}
}

echo "Done. Deleted {$deleted}/" . count($rows) . " files.\n";
