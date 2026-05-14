<?php
// ============================================================
// cli/run_federation_exports.php — FederationExporter
//
// Auto-eksport zawodników do federacji sportowych (PZPN/PZSS/PZKosz/PZLA/…).
//
// Algorytm:
//   1. Iteruj wszystkie wiersze z club_federation_credentials WHERE is_active=1
//   2. Dla każdego klubu pobierz członków zmienionych od ostatniego eksportu
//      (members.updated_at >= last_export_at LUB last_export_at IS NULL)
//   3. Dla każdego członka:
//      - sprawdź czy wcześniej eksportowany (federation_export_log success?)
//        → updateMember(); inaczej → exportMember()
//      - zaloguj operację w federation_export_log
//      - przy błędach kontynuuj (nie przerywaj batch'a)
//   4. Po zakończeniu: zapisz last_export_at / last_export_status
//
// Cron schedule (zalecane):
//   0 3 * * * /opt/plesk/php/8.2/bin/php /var/www/.../cli/run_federation_exports.php
//
// Tryb dry-run:
//   php cli/run_federation_exports.php --dry-run
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
echo "[" . date('Y-m-d H:i:s') . "] Federation export runner " . ($dryRun ? '(DRY RUN)' : 'starting…') . "\n";

$db = \App\Helpers\Database::pdo();

// 1. Wszystkie aktywne credentiale (bez scope — runner globalny)
$stmt = $db->query(
    "SELECT cfc.*, c.name AS club_name
     FROM club_federation_credentials cfc
     JOIN clubs c ON c.id = cfc.club_id
     WHERE cfc.is_active = 1 AND c.is_active = 1
     ORDER BY cfc.club_id, cfc.federation_code"
);
$activeCreds = $stmt->fetchAll();
echo "  → " . count($activeCreds) . " active federation credential(s) across clubs\n";

$totalExported = 0;
$totalFailed   = 0;
$totalSkipped  = 0;

foreach ($activeCreds as $cred) {
    $clubId         = (int)$cred['club_id'];
    $federationCode = (string)$cred['federation_code'];
    $clubName       = $cred['club_name'];
    $lastExportAt   = $cred['last_export_at']; // może być null

    echo "  → Club {$clubId} '{$clubName}', federation={$federationCode}\n";

    // 2. Pobierz członków zmienionych od ostatniego eksportu
    if ($lastExportAt) {
        $memberStmt = $db->prepare(
            "SELECT * FROM members
             WHERE club_id = ? AND status = 'aktywny' AND updated_at >= ?
             ORDER BY id"
        );
        $memberStmt->execute([$clubId, $lastExportAt]);
    } else {
        $memberStmt = $db->prepare(
            "SELECT * FROM members
             WHERE club_id = ? AND status = 'aktywny'
             ORDER BY id"
        );
        $memberStmt->execute([$clubId]);
    }
    $members = $memberStmt->fetchAll();

    if (empty($members)) {
        echo "      → Brak nowych/zmienionych członków do eksportu\n";
        continue;
    }
    echo "      → " . count($members) . " członków do przetworzenia\n";

    // 3. Zbuduj exporter — decrypt creds ręcznie (poza ClubScopedModel context)
    $configDecrypted = $cred;
    foreach (['api_username', 'api_password', 'api_token'] as $f) {
        $enc = $cred[$f . '_enc'] ?? null;
        $configDecrypted[$f] = $enc ? \App\Helpers\Encryption::decrypt($enc) : null;
    }

    $exporter = \App\Helpers\Federations\FederationExporterFactory::forCode(
        $federationCode, $configDecrypted
    );
    if (!$exporter) {
        echo "      ! Brak adaptera dla {$federationCode}, skip\n";
        $totalSkipped += count($members);
        continue;
    }

    // 4. Eksport per-member (z log entries)
    $exportedNow = 0;
    $failedNow   = 0;

    foreach ($members as $m) {
        $memberId = (int)$m['id'];
        $payload  = \App\Helpers\Federations\MemberPayload::fromMemberRow($m);

        if ($dryRun) {
            echo "      WOULD EXPORT: member#{$memberId} {$m['first_name']} {$m['last_name']}\n";
            $exportedNow++;
            continue;
        }

        // Czy wcześniej eksportowano? (last success)
        $prevStmt = $db->prepare(
            "SELECT COUNT(*) FROM federation_export_log
             WHERE club_id = ? AND federation_code = ? AND member_id = ?
               AND status = 'success'"
        );
        $prevStmt->execute([$clubId, $federationCode, $memberId]);
        $previouslyExported = (int)$prevStmt->fetchColumn() > 0;
        $operation = $previouslyExported ? 'update' : 'register';

        // Log queued
        $insertLog = $db->prepare(
            "INSERT INTO federation_export_log
             (club_id, federation_code, member_id, operation, status, request_payload, triggered_at)
             VALUES (?, ?, ?, ?, 'queued', ?, NOW())"
        );
        $insertLog->execute([
            $clubId, $federationCode, $memberId, $operation,
            json_encode($payload->toArray(), JSON_UNESCAPED_UNICODE),
        ]);
        $logId = (int)$db->lastInsertId();

        try {
            $result = $previouslyExported
                ? $exporter->updateMember($payload)
                : $exporter->exportMember($payload);

            if ($result->ok) {
                $db->prepare(
                    "UPDATE federation_export_log
                     SET status='success', response_payload=?, completed_at=NOW()
                     WHERE id=?"
                )->execute([
                    json_encode($result->rawResponse, JSON_UNESCAPED_UNICODE),
                    $logId,
                ]);
                $exportedNow++;
            } else {
                $db->prepare(
                    "UPDATE federation_export_log
                     SET status='failed', error_message=?, response_payload=?, completed_at=NOW()
                     WHERE id=?"
                )->execute([
                    mb_substr($result->message, 0, 1000),
                    json_encode($result->rawResponse, JSON_UNESCAPED_UNICODE),
                    $logId,
                ]);
                $failedNow++;
            }
        } catch (\Throwable $e) {
            $db->prepare(
                "UPDATE federation_export_log
                 SET status='failed', error_message=?, completed_at=NOW()
                 WHERE id=?"
            )->execute([mb_substr($e->getMessage(), 0, 1000), $logId]);
            $failedNow++;
            echo "      ! Member#{$memberId} {$operation} EXCEPTION: " . $e->getMessage() . "\n";
        }
    }

    // 5. Update last_export_at status
    if (!$dryRun) {
        $status = $failedNow === 0 ? 'success' : ($exportedNow === 0 ? 'failed' : 'partial');
        $db->prepare(
            "UPDATE club_federation_credentials
             SET last_export_at = NOW(), last_export_status = ?
             WHERE id = ?"
        )->execute([$status, (int)$cred['id']]);
    }

    echo "      → exported={$exportedNow}, failed={$failedNow}\n";
    $totalExported += $exportedNow;
    $totalFailed   += $failedNow;
}

echo "[" . date('Y-m-d H:i:s') . "] Done — exported: {$totalExported}, failed: {$totalFailed}, skipped: {$totalSkipped}\n";
