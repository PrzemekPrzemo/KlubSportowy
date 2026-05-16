<?php
// ============================================================
// cli/process_gdpr_exports.php
//
// Worker generujacy ZIP eksporty dla zatwierdzonych prosb GDPR
// (art. 20 RODO — prawo do przenoszenia danych).
//
// Iteruje gdpr_requests WHERE request_type='export'
//                         AND status='in_progress'
//                         AND export_file_path IS NULL.
// Per request:
//   1) wywoluje MemberDataExporter::export()
//   2) zapisuje path do gdpr_requests + status=completed
//      + export_file_expires_at = NOW() + INTERVAL 7 DAY
//   3) kolejkuje email 'gdpr_export_ready' do czlonka
//
// Max 5 requestow per run (avoid long-running cron).
//
// Schedule:
//   */5 * * * * /opt/plesk/php/8.3/bin/php /var/www/.../cli/process_gdpr_exports.php
// ============================================================

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/php_version_check.php';

define('ROOT_PATH', dirname(__DIR__));

// Autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = ROOT_PATH . '/app/' . $relative . '.php';
    if (file_exists($file)) require $file;
});

$vendor = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($vendor)) require $vendor;

// Helpers (url(), csrf_field(), itp.)
$helpersFile = ROOT_PATH . '/app/Helpers/Helpers.php';
if (file_exists($helpersFile)) require_once $helpersFile;

$localApp = ROOT_PATH . '/config/app.local.php';
$cfg = file_exists($localApp) ? require $localApp : require ROOT_PATH . '/config/app.php';
date_default_timezone_set($cfg['timezone'] ?? 'Europe/Warsaw');

// BASE_URL musi byc zdefiniowane dla logiki appki (linki w mailach).
if (!defined('BASE_URL')) {
    define('BASE_URL', (string)($cfg['base_url'] ?? 'http://localhost'));
}

use App\Helpers\Database;
use App\Helpers\EmailService;
use App\Helpers\Gdpr\MemberDataExporter;
use App\Models\ClubModel;
use App\Models\GdprRequestModel;
use App\Models\TenantAccessLogModel;

const MAX_REQUESTS_PER_RUN = 5;

$startedAt = date('Y-m-d H:i:s');
echo "[{$startedAt}] GDPR export worker starting...\n";

$processed = 0;
$failed = 0;

try {
    $pdo = Database::pdo();

    $stmt = $pdo->prepare(
        "SELECT id, club_id, member_id
         FROM gdpr_requests
         WHERE request_type = 'export'
           AND status = 'in_progress'
           AND export_file_path IS NULL
         ORDER BY requested_at ASC
         LIMIT " . (int)MAX_REQUESTS_PER_RUN
    );
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo "  Brak prosb do przetworzenia.\n";
    }

    $model = new GdprRequestModel();

    foreach ($rows as $row) {
        $reqId    = (int)$row['id'];
        $memberId = (int)$row['member_id'];
        $clubId   = (int)$row['club_id'];

        echo "  Przetwarzam request_id={$reqId} member={$memberId} club={$clubId}...\n";

        try {
            $exporter = new MemberDataExporter();
            $zipPath  = $exporter->export($memberId, $reqId, $clubId);

            $model->markCompleted(
                $reqId,
                null,
                'Eksport wygenerowany automatycznie przez worker. Link wygasa za 7 dni.',
                $zipPath
            );

            // Pobierz email do wyslania powiadomienia
            $stmt2 = $pdo->prepare("SELECT email, first_name FROM members WHERE id = ? AND club_id = ? LIMIT 1");
            $stmt2->execute([$memberId, $clubId]);
            $mem = $stmt2->fetch(PDO::FETCH_ASSOC);

            $clubName = 'KlubSportowy';
            try {
                $club = (new ClubModel())->findById($clubId);
                $clubName = (string)($club['name'] ?? 'KlubSportowy');
            } catch (\Throwable) {}

            if (!empty($mem['email'])) {
                $email = (string)$mem['email'];

                // Probujemy template z catalog; fallback do raw queue.
                $vars = [
                    'first_name' => (string)($mem['first_name'] ?? ''),
                    'club_name'  => $clubName,
                ];
                $queued = EmailService::queueFromTemplate(
                    $clubId,
                    'gdpr_export_ready',
                    $email,
                    $vars,
                    (string)($mem['first_name'] ?? '')
                );

                if ($queued === null) {
                    // Template nie skonfigurowany dla klubu — fallback raw email.
                    $body = "Czesc " . ($mem['first_name'] ?? '') . ",\n\n"
                          . "Twoj eksport danych zostal wygenerowany. Mozesz go pobrac w portalu "
                          . "w sekcji 'Moje dane (RODO)'.\n\n"
                          . "Link wygasa za 7 dni.\n\n"
                          . "Pozdrawiamy,\nKlub {$clubName}";
                    EmailService::queue($clubId, $email, 'Twoj eksport danych jest gotowy', $body);
                }
            }

            $processed++;
            echo "    OK -> {$zipPath} (" . (file_exists($zipPath) ? filesize($zipPath) : 0) . " B)\n";
        } catch (\Throwable $e) {
            $failed++;
            $errMsg = substr($e->getMessage(), 0, 400);
            echo "    BLAD: {$errMsg}\n";

            $model->markRejected($reqId, null, 'Blad generacji eksportu (worker): ' . $errMsg);

            try {
                (new TenantAccessLogModel())->logBypass(
                    'gdpr_requests',
                    'write',
                    __FILE__,
                    __LINE__,
                    'cli/process_gdpr_exports.php',
                    'critical',
                    'GDPR export worker FAILED req=' . $reqId . ' err=' . $errMsg
                );
            } catch (\Throwable) {}
        }
    }
} catch (\Throwable $e) {
    fwrite(STDERR, "FATAL: " . $e->getMessage() . "\n");
    exit(1);
}

$finishedAt = date('Y-m-d H:i:s');
echo "[{$finishedAt}] Done. processed={$processed} failed={$failed}\n";
