<?php
// ============================================================
// cli/notify_overdue.php — Faza S.0
//
// Przypomnienia o zaległych składkach. Iteruje payment_dues z
// status pending/partial/overdue i due_date < CURDATE(), dopasowuje
// reguły z notification_rules (per klub, per template_type), kolejkuje
// emaile w email_queue.
//
// Anti-spam:
//   - notification_log limit per (member, template_type, target_id)
//   - member_notification_prefs.opted_out blokuje wysyłkę
//
// Cron schedule (zalecane):
//   0 9 * * * /opt/plesk/php/8.2/bin/php /var/www/.../cli/notify_overdue.php
//
// Lub --dry-run flag dla testów (nie wysyła nic, tylko raportuje):
//   php cli/notify_overdue.php --dry-run
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

$dryRun = in_array('--dry-run', $argv ?? [], true);

echo "[" . date('Y-m-d H:i:s') . "] Overdue notification cron " . ($dryRun ? '(DRY RUN)' : 'starting…') . "\n";

$db = \App\Helpers\Database::pdo();

// 1. Refresh statusów overdue (status='pending'/'partial' AND due_date<today → 'overdue')
//    To robimy globalnie (per-klub) zanim zaczniemy wysyłać.
$refreshStmt = $db->prepare(
    "UPDATE payment_dues
     SET status = 'overdue'
     WHERE status IN ('pending','partial')
       AND due_date < CURDATE()"
);
if (!$dryRun) {
    $refreshStmt->execute();
    $refreshed = $refreshStmt->rowCount();
    echo "  → Refreshed $refreshed dues to status='overdue'\n";
}

// 2. Iteruj kluby z aktywnymi regułami fee_reminder
$rulesStmt = $db->prepare(
    "SELECT nr.*, c.name AS club_name
     FROM notification_rules nr
     JOIN clubs c ON c.id = nr.club_id
     WHERE nr.is_active = 1
       AND nr.template_type = 'fee_reminder'
       AND nr.trigger_event = 'days_after_due'
       AND c.is_active = 1
     ORDER BY nr.club_id, nr.days_offset"
);
$rulesStmt->execute();
$rules = $rulesStmt->fetchAll();

echo "  → " . count($rules) . " active fee_reminder rule(s) across clubs\n";

$totalQueued     = 0;
$totalSuppressed = 0;

foreach ($rules as $rule) {
    $clubId    = (int)$rule['club_id'];
    $offset    = (int)$rule['days_offset'];
    $maxPer    = (int)$rule['max_per_target'];
    $tplType   = $rule['template_type'];

    // Find dues that match: due_date == today - $offset AND status overdue/partial
    $duesStmt = $db->prepare(
        "SELECT pd.*, m.first_name, m.last_name, m.email, m.member_number,
                fr.name AS rate_name
         FROM payment_dues pd
         JOIN members m   ON m.id = pd.member_id
         LEFT JOIN fee_rates fr ON fr.id = pd.fee_rate_id
         WHERE pd.club_id = ?
           AND pd.status IN ('overdue', 'partial', 'pending')
           AND pd.due_date = DATE_SUB(CURDATE(), INTERVAL ? DAY)
           AND m.email IS NOT NULL AND m.email != ''
           AND m.status = 'aktywny'"
    );
    $duesStmt->execute([$clubId, $offset]);
    $dues = $duesStmt->fetchAll();

    if (empty($dues)) continue;

    echo "  → Club {$clubId} '{$rule['club_name']}', offset={$offset}d: " . count($dues) . " due(s)\n";

    foreach ($dues as $d) {
        $memberId  = (int)$d['member_id'];
        $dueId     = (int)$d['id'];

        // Anti-spam: sprawdź czy już byl wyslany dla tego targetu
        $logStmt = $db->prepare(
            "SELECT COUNT(*) FROM notification_log
             WHERE club_id = ? AND member_id = ?
               AND template_type = ? AND target_type = 'payment_due' AND target_id = ?
               AND status IN ('queued','sent')"
        );
        $logStmt->execute([$clubId, $memberId, $tplType, $dueId]);
        $alreadySent = (int)$logStmt->fetchColumn();
        if ($alreadySent >= $maxPer) {
            $totalSuppressed++;
            if ($dryRun) {
                echo "    SUPPRESSED (max {$maxPer}): {$d['email']} re due#{$dueId}\n";
            }
            continue;
        }

        // Opt-out check
        $prefModel = new \App\Models\MemberNotificationPrefModel();
        if ($prefModel->isOptedOut($memberId, $tplType, 'email')) {
            $totalSuppressed++;
            if ($dryRun) {
                echo "    OPT-OUT: {$d['email']} re due#{$dueId}\n";
            }
            // Zaloguj suppressed
            if (!$dryRun) {
                $db->prepare(
                    "INSERT INTO notification_log
                     (club_id, member_id, template_type, target_type, target_id,
                      channel, recipient, status, rule_id)
                     VALUES (?, ?, ?, 'payment_due', ?, 'email', ?, 'suppressed', ?)"
                )->execute([
                    $clubId, $memberId, $tplType, $dueId, $d['email'], $rule['id']
                ]);
            }
            continue;
        }

        // Vars dla template'u
        $remaining = (float)$d['net_amount'] - (float)$d['paid_amount'];
        $vars = [
            'first_name'    => $d['first_name'] ?? '',
            'last_name'     => $d['last_name'] ?? '',
            'club_name'     => $rule['club_name'],
            'amount'        => number_format($remaining, 2, ',', ' '),
            'due_date'      => $d['due_date'],
            'member_number' => $d['member_number'] ?? '',
            'days_overdue'  => $offset,
            'rate_name'     => $d['rate_name'] ?? 'Składka',
        ];

        if ($dryRun) {
            echo "    WOULD QUEUE: {$d['email']} re due#{$dueId}, kwota {$vars['amount']}\n";
            $totalQueued++;
            continue;
        }

        $queueId = \App\Helpers\EmailService::queueFromTemplate(
            $clubId, $tplType, (string)$d['email'], $vars,
            ($d['first_name'] . ' ' . $d['last_name']) ?: null
        );

        // Audit log
        $db->prepare(
            "INSERT INTO notification_log
             (club_id, member_id, template_type, target_type, target_id,
              channel, recipient, email_queue_id, status, rule_id)
             VALUES (?, ?, ?, 'payment_due', ?, 'email', ?, ?, ?, ?)"
        )->execute([
            $clubId, $memberId, $tplType, $dueId,
            $d['email'], $queueId, $queueId ? 'queued' : 'failed', $rule['id'],
        ]);

        $totalQueued++;
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Done — queued: {$totalQueued}, suppressed: {$totalSuppressed}\n";
