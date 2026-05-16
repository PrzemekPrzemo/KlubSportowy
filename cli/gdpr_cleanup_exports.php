<?php
// ============================================================
// cli/gdpr_cleanup_exports.php
//
// Cleanup wygasnietych eksportow GDPR (>7 dni od wygenerowania).
//
// Iteruje gdpr_requests WHERE export_file_path IS NOT NULL
//                         AND export_file_expires_at < NOW().
// Per row:
//   1) unlink ZIP z dysku
//   2) UPDATE export_file_path = NULL
//   3) audit log do tenant_access_log (action=gdpr_export_expired)
//
// Schedule:
//   0 3 * * * /opt/plesk/php/8.3/bin/php /var/www/.../cli/gdpr_cleanup_exports.php
//
// Uwaga: poprzednia wersja `cli/gdpr_cleanup.php` wciaz dziala (delegowala do
// GdprService::pruneExpiredExports()). Ten skrypt to nowsza wersja z bogatszym
// audit-log + per-row error handling.
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

$helpersFile = ROOT_PATH . '/app/Helpers/Helpers.php';
if (file_exists($helpersFile)) require_once $helpersFile;

$localApp = ROOT_PATH . '/config/app.local.php';
$cfg = file_exists($localApp) ? require $localApp : require ROOT_PATH . '/config/app.php';
date_default_timezone_set($cfg['timezone'] ?? 'Europe/Warsaw');

if (!defined('BASE_URL')) {
    define('BASE_URL', (string)($cfg['base_url'] ?? 'http://localhost'));
}

use App\Helpers\Database;
use App\Models\TenantAccessLogModel;

$startedAt = date('Y-m-d H:i:s');
echo "[{$startedAt}] GDPR export cleanup starting...\n";

$deleted = 0;
$dbCleared = 0;

try {
    $pdo = Database::pdo();

    $stmt = $pdo->query(
        "SELECT id, club_id, member_id, export_file_path
         FROM gdpr_requests
         WHERE export_file_path IS NOT NULL
           AND export_file_expires_at IS NOT NULL
           AND export_file_expires_at < NOW()"
    );
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo "  Brak wygasnietych eksportow.\n";
    }

    $audit = new TenantAccessLogModel();
    $updateStmt = $pdo->prepare(
        "UPDATE gdpr_requests SET export_file_path = NULL WHERE id = ?"
    );

    foreach ($rows as $row) {
        $reqId  = (int)$row['id'];
        $path   = (string)$row['export_file_path'];
        $clubId = (int)$row['club_id'];

        if ($path !== '' && is_file($path)) {
            if (@unlink($path)) {
                $deleted++;
                echo "  Usunieto plik: {$path}\n";
            } else {
                echo "  BLAD unlink: {$path}\n";
            }
        }

        $updateStmt->execute([$reqId]);
        $dbCleared++;

        try {
            $audit->logBypass(
                'gdpr_requests',
                'gdpr_export_expired',
                __FILE__,
                __LINE__,
                'cli/gdpr_cleanup_exports.php',
                'info',
                'GDPR export expired req=' . $reqId . ' club=' . $clubId . ' path=' . substr($path, 0, 120)
            );
        } catch (\Throwable) {}
    }
} catch (\Throwable $e) {
    fwrite(STDERR, "FATAL: " . $e->getMessage() . "\n");
    exit(1);
}

$finishedAt = date('Y-m-d H:i:s');
echo "[{$finishedAt}] Done. deleted_files={$deleted} db_cleared={$dbCleared}\n";
