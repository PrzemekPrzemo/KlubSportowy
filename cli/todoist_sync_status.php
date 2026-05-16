<?php
// ============================================================
// cli/todoist_sync_status.php
//
// Polluje Todoist API co 5 min (cron) zeby wykryc zamkniete /
// usuniete zadania i zaktualizowac support_reports lokalnie.
//
// Flagi:
//   --dry-run       — pokaz co zostanie zmienione bez UPDATE
//   --verbose       — log per task
//   --limit=N       — max N tasków (default: wszystko, batch po 50)
//
// Cron (Plesk PHP 8.2):
//   */5 * * * * /opt/plesk/php/8.2/bin/php /var/www/.../cli/todoist_sync_status.php \
//     >> /var/log/clubdesk-todoist-sync.log 2>&1
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

$argvList = $argv ?? [];
$dryRun  = in_array('--dry-run', $argvList, true);
$verbose = in_array('--verbose', $argvList, true);
$limit   = 0;
foreach ($argvList as $a) {
    if (preg_match('/^--limit=(\d+)$/', $a, $m)) {
        $limit = (int)$m[1];
    }
}

$BATCH_SIZE = 50;
$SLEEP_MS   = 50; // delikatny throttle pomiedzy callami (200ms = ~300 req/min, safe < 450 limit)

$ts = fn() => '[' . date('Y-m-d H:i:s') . ']';
echo $ts() . ' Todoist sync status' . ($dryRun ? ' (DRY RUN)' : '') . " starting…\n";

try {
    $client = new \App\Helpers\TodoistClient();
} catch (\Throwable $e) {
    echo $ts() . " ERROR creating client: " . $e->getMessage() . "\n";
    exit(2);
}

if (!$client->isConfigured()) {
    echo $ts() . " skipped: Todoist API not configured (config/todoist.local.php missing).\n";
    exit(0);
}

$db = \App\Helpers\Database::pdo();

// Fetch all candidates: support_reports z task_id i ze statusem nadal aktywnym
$sql = "SELECT id, todoist_task_id, status
        FROM support_reports
        WHERE todoist_task_id IS NOT NULL
          AND todoist_task_id <> ''
          AND status IN ('new', 'in_progress')
        ORDER BY id ASC";
if ($limit > 0) {
    $sql .= " LIMIT " . $limit;
}
$stmt = $db->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll();

$total      = count($rows);
$processed  = 0;
$resolved   = 0;
$deleted    = 0;
$reopened   = 0;
$errors     = 0;
$skipped    = 0;

echo $ts() . " {$total} ticket(s) z aktywnym todoist_task_id do sprawdzenia\n";

if ($total === 0) {
    echo $ts() . " Done — nothing to sync.\n";
    exit(0);
}

// Batch loop
$updateStmt = $db->prepare(
    "UPDATE support_reports
        SET status = ?, resolved_at = ?, resolution_notes = ?, todoist_synced_at = ?, todoist_sync_error = NULL
      WHERE id = ?"
);
$syncedOnlyStmt = $db->prepare(
    "UPDATE support_reports SET todoist_synced_at = ?, todoist_sync_error = NULL WHERE id = ?"
);
$errStmt = $db->prepare(
    "UPDATE support_reports SET todoist_sync_error = ? WHERE id = ?"
);

$chunks = array_chunk($rows, $BATCH_SIZE);
foreach ($chunks as $chunkIdx => $chunk) {
    if ($verbose) {
        echo $ts() . " --- batch " . ($chunkIdx + 1) . "/" . count($chunks) . " (" . count($chunk) . " items) ---\n";
    }
    foreach ($chunk as $row) {
        $ticketId = (int)$row['id'];
        $taskId   = (string)$row['todoist_task_id'];
        $curStatus = (string)$row['status'];
        $processed++;

        try {
            $task = $client->getTask($taskId);

            if ($task === null) {
                // 404 — task usuniety w Todoist
                if ($verbose || $dryRun) {
                    echo $ts() . "   ticket #{$ticketId} task={$taskId}: DELETED in Todoist → resolved\n";
                }
                if (!$dryRun) {
                    $updateStmt->execute([
                        'resolved',
                        date('Y-m-d H:i:s'),
                        'Task deleted in Todoist',
                        date('Y-m-d H:i:s'),
                        $ticketId,
                    ]);
                }
                $deleted++;
                continue;
            }

            $isCompleted = !empty($task['is_completed']) || !empty($task['completed_at']) || !empty($task['checked']);

            if ($isCompleted) {
                if ($verbose || $dryRun) {
                    echo $ts() . "   ticket #{$ticketId} task={$taskId}: COMPLETED in Todoist → resolved\n";
                }
                if (!$dryRun) {
                    $updateStmt->execute([
                        'resolved',
                        date('Y-m-d H:i:s'),
                        'Closed in Todoist',
                        date('Y-m-d H:i:s'),
                        $ticketId,
                    ]);
                }
                $resolved++;
            } else {
                // Task wciaz otwarty w Todoist — tylko update synced_at
                if ($verbose) {
                    echo $ts() . "   ticket #{$ticketId} task={$taskId}: still open (local={$curStatus})\n";
                }
                if (!$dryRun) {
                    $syncedOnlyStmt->execute([date('Y-m-d H:i:s'), $ticketId]);
                }
                $skipped++;
            }
        } catch (\Throwable $e) {
            $errors++;
            $msg = mb_substr($e->getMessage(), 0, 1000);
            echo $ts() . "   ERR ticket #{$ticketId} task={$taskId}: {$msg}\n";
            if (!$dryRun) {
                try {
                    $errStmt->execute([$msg, $ticketId]);
                } catch (\Throwable) {}
            }
        }

        // light throttle
        if ($SLEEP_MS > 0) {
            usleep($SLEEP_MS * 1000);
        }
    }
}

echo $ts() . " Done — processed={$processed}, resolved_completed={$resolved}, deleted={$deleted}, still_open={$skipped}, errors={$errors}"
    . ($dryRun ? ' (DRY RUN — no changes written)' : '')
    . "\n";

exit($errors > 0 && !$dryRun ? 1 : 0);
