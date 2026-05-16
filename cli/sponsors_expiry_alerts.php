<?php
// ============================================================
// cli/sponsors_expiry_alerts.php
//
// Cron alert dla zarzadu klubu o wygasajacych kontraktach sponsorskich.
// Sprawdza sponsorów z contract_end == today+30d, +14d, +7d
// i wysyla email z templateu 'sponsor_expiring'.
//
// Idempotent: tabela sponsor_alert_log uniemozliwia wyslanie tego samego
// alertu (sponsor_id + alert_type) drugi raz.
//
// Usage: 0 7 * * * /opt/plesk/php/8.3/bin/php /path/to/cli/sponsors_expiry_alerts.php
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

use App\Helpers\Database;
use App\Helpers\EmailService;
use App\Models\SponsorModel;

$pdo = Database::pdo();
$model = new SponsorModel();

echo "[" . date('Y-m-d H:i:s') . "] Sponsors expiry alerts starting…\n";

$tiersOfDays = [
    30 => 'expiring_30d',
    14 => 'expiring_14d',
    7  => 'expiring_7d',
];

$totalQueued = 0;
$totalSkipped = 0;

foreach ($tiersOfDays as $days => $alertType) {
    $rows = $model->expiringExactlyInDays($days);
    foreach ($rows as $r) {
        $sponsorId = (int)$r['id'];

        if ($model->alertAlreadySent($sponsorId, $alertType)) {
            $totalSkipped++;
            continue;
        }

        // Wyznacz email odbiorcy — preferuj club_email z tabeli clubs,
        // fallback: super admin systemu (omijamy w MVP — pomijamy alert)
        $recipient = trim((string)($r['club_email'] ?? ''));
        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            echo "  [skip] sponsor #{$sponsorId} ({$r['name']}) — brak validnego club.email\n";
            $totalSkipped++;
            continue;
        }

        $vars = [
            'sponsor.name'           => (string)$r['name'],
            'sponsor.tier'           => (string)($r['tier'] ?? 'partner'),
            'sponsor.contract_end'   => (string)($r['contract_end'] ?? ''),
            'sponsor.contract_value' => $r['contract_value'] !== null
                                          ? number_format((float)$r['contract_value'], 2, ',', ' ') . ' PLN'
                                          : 'brak danych',
            'sponsor.days_left'      => (string)($r['days_left'] ?? $days),
            'sponsor.contact_person' => (string)($r['contact_person'] ?? '—'),
            'sponsor.email'          => (string)($r['email'] ?? '—'),
            'sponsor.phone'          => (string)($r['phone'] ?? '—'),
            'club.name'              => (string)$r['club_name'],
        ];

        try {
            $queued = EmailService::queueFromTemplate(
                (int)$r['club_id'],
                'sponsor_expiring',
                $recipient,
                $vars,
                $r['club_name']
            );
            if ($queued) {
                $model->logAlert($sponsorId, $alertType);
                $totalQueued++;
                echo "  [queue] sponsor #{$sponsorId} ({$r['name']}) → {$recipient} ({$alertType})\n";
            } else {
                // Brak templateu — log + fallback: zwykly tekst
                $subject = 'Kontrakt sponsorski ' . $r['name'] . ' wygasa za ' . $days . ' dni';
                $body  = "Zarzad klubu {$r['club_name']},\n\n"
                       . "Kontrakt sponsorski z {$r['name']} (tier: {$r['tier']}) wygasa {$r['contract_end']} "
                       . "(za {$days} dni).\n\nOsoba kontaktowa: " . ($r['contact_person'] ?? '—')
                       . " (" . ($r['email'] ?? '—') . ").\n\nZalecamy rozpoczecie procesu odnowienia.\n\n--\nClubDesk";
                EmailService::queue((int)$r['club_id'], $recipient, $subject, $body, $r['club_name'], 'sponsor_expiring');
                $model->logAlert($sponsorId, $alertType);
                $totalQueued++;
                echo "  [queue-fallback] sponsor #{$sponsorId} ({$r['name']}) → {$recipient} ({$alertType})\n";
            }
        } catch (\Throwable $e) {
            echo "  [error] sponsor #{$sponsorId}: " . $e->getMessage() . "\n";
            error_log("sponsors_expiry_alerts: " . $e->getMessage());
        }
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Done. Queued: {$totalQueued}, skipped: {$totalSkipped}\n";
