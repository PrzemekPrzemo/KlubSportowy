<?php
// ============================================================
// cli/audit_log_cleanup.php
//
// Retencja audit logow — czysci stare wpisy zgodnie z polityka:
//   - tenant_access_log        : keep 90 dni
//   - activity_log             : keep 180 dni
//   - sensitive_access_log     : keep 5 lat (1825 dni, RODO art. 30)
//   - member_consents          : NEVER prune (compliance)
//
// Sygnaly:
//   --dry-run    : nie usuwa, tylko raportuje co by zostalo usuniete
//   --table=NAME : ogranicz do jednej tabeli
//
// Cron schedule (zalecane):
//   0 3 * * 0 /usr/bin/php /var/www/.../cli/audit_log_cleanup.php
//
// Zobacz docs/admin-guide.md (sekcja "Retencja audit logow").
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

$dryRun  = in_array('--dry-run', $argv ?? [], true);
$onlyOne = null;
foreach (($argv ?? []) as $a) {
    if (str_starts_with($a, '--table=')) {
        $onlyOne = substr($a, 8);
    }
}

echo '[' . date('Y-m-d H:i:s') . '] Audit log cleanup ' . ($dryRun ? '(DRY RUN)' : 'running…') . "\n";

$db = \App\Helpers\Database::pdo();

$policy = [
    'tenant_access_log'    => ['column' => 'occurred_at', 'days' => 90,   'comment' => 'Cross-tenant access bypass log'],
    'activity_log'         => ['column' => 'created_at',  'days' => 180,  'comment' => 'User activity audit'],
    'sensitive_access_log' => ['column' => 'created_at',  'days' => 1825, 'comment' => 'RODO art. 30 access log'],
];

$totalDeleted = 0;
foreach ($policy as $table => $rule) {
    if ($onlyOne !== null && $onlyOne !== $table) continue;

    try {
        $col  = $rule['column'];
        $days = (int)$rule['days'];
        $countStmt = $db->prepare(
            "SELECT COUNT(*) FROM `{$table}`
             WHERE `{$col}` < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $countStmt->execute([$days]);
        $eligible = (int)$countStmt->fetchColumn();

        echo sprintf(
            "  · %-22s keep=%4dd  eligible=%6d  (%s)\n",
            $table, $days, $eligible, $rule['comment']
        );

        if ($eligible === 0 || $dryRun) continue;

        $delStmt = $db->prepare(
            "DELETE FROM `{$table}`
             WHERE `{$col}` < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $delStmt->execute([$days]);
        $deleted = (int)$delStmt->rowCount();
        $totalDeleted += $deleted;
        echo "    → deleted={$deleted}\n";
    } catch (\Throwable $e) {
        fwrite(STDERR, "  ! Error on {$table}: " . $e->getMessage() . "\n");
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Done. Total deleted: {$totalDeleted}\n";
exit(0);
