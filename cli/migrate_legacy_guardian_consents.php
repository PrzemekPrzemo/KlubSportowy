<?php
// ============================================================
// cli/migrate_legacy_guardian_consents.php
//
// Migracja danych z minor_consents (legacy 1-tier zgody) do
// nowego modelu guardian_portal:
//   minor_consents.guardian_email -> guardians (nowe konto)
//   minor_consents.guardian_name  -> guardians.first/last_name
//   photo_consent / media_consent / travel_consent / medical_decisions
//                                 -> guardian_minor_consents
//
// Tryby:
//   --dry-run     — pokaz co zostanie zrobione, nic nie zapisuj
//   --send-invites — wyslij email aktywacyjny do nowych opiekunow
//   (default: zapisz tylko bez wysylania emaili)
//
// Usage:
//   php cli/migrate_legacy_guardian_consents.php --dry-run
//   php cli/migrate_legacy_guardian_consents.php
//   php cli/migrate_legacy_guardian_consents.php --send-invites
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

require ROOT_PATH . '/app/Helpers/Helpers.php';

use App\Controllers\GuardianAuthController;
use App\Helpers\Database;
use App\Models\GuardianMemberModel;
use App\Models\GuardianMinorConsentModel;
use App\Models\GuardianModel;

$args     = $_SERVER['argv'] ?? [];
$dryRun   = in_array('--dry-run', $args, true);
$sendInv  = in_array('--send-invites', $args, true);

echo "[" . date('Y-m-d H:i:s') . "] Legacy guardian consents migration\n";
echo "  dry-run     = " . ($dryRun ? 'YES' : 'no') . "\n";
echo "  send-invites = " . ($sendInv ? 'YES' : 'no') . "\n\n";

$pdo = Database::pdo();

$stmt = $pdo->query("SELECT * FROM minor_consents WHERE guardian_email IS NOT NULL AND guardian_email != ''");
$rows = $stmt->fetchAll();
echo "Found " . count($rows) . " minor_consents records\n";

$created = 0;
$linked  = 0;
$consents = 0;
$invitesSent = 0;
$skipped = 0;

$guardianModel = new GuardianModel();
$linkModel     = new GuardianMemberModel();
$consentModel  = new GuardianMinorConsentModel();

foreach ($rows as $r) {
    $clubId   = (int)$r['club_id'];
    $memberId = (int)$r['member_id'];
    $email    = decryptIfNeeded((string)$r['guardian_email']);
    $name     = (string)($r['guardian_name'] ?? '');
    $phone    = decryptIfNeeded((string)($r['guardian_phone'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "  [SKIP] member#{$memberId}: invalid email\n";
        $skipped++;
        continue;
    }

    [$first, $last] = parseName($name);

    if ($dryRun) {
        echo "  [DRY] club#{$clubId} member#{$memberId} -> guardian {$email} ({$first} {$last})\n";
        continue;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM guardians WHERE club_id = ? AND email = ?");
        $stmt->execute([$clubId, strtolower($email)]);
        $existingId = $stmt->fetchColumn();

        if ($existingId) {
            $guardianId = (int)$existingId;
            $token = null;
        } else {
            $invited = $guardianModel->invite($clubId, $email, $first, $last, $phone);
            $guardianId = (int)$invited['guardian']['id'];
            $token = $invited['token'];
            $created++;
        }

        $linkModel->linkGuardianToMember(
            $guardianId, $memberId, $clubId,
            'parent', true, true, true, null
        );
        $linked++;

        $mappings = [
            'photo_consent'     => 'image_use',
            'media_consent'     => 'image_use',
            'travel_consent'    => 'tournament_participation',
            'medical_decisions' => 'medical_treatment',
        ];
        foreach ($mappings as $legacyKey => $newType) {
            if (!empty($r[$legacyKey])) {
                $consentModel->grantConsent(
                    $guardianId, $memberId, $clubId, $newType,
                    null, 'cli/legacy-migration', 'Migrated from minor_consents.' . $legacyKey
                );
                $consents++;
            }
        }

        $consentModel->grantConsent(
            $guardianId, $memberId, $clubId, 'data_processing',
            null, 'cli/legacy-migration', 'Implicit from legacy minor_consents'
        );
        $consents++;

        if ($sendInv && $token !== null) {
            try {
                $stmt = $pdo->prepare("SELECT first_name, last_name FROM members WHERE id = ?");
                $stmt->execute([$memberId]);
                $memberRec = $stmt->fetch() ?: [];
                $stmt = $pdo->prepare("SELECT * FROM guardians WHERE id = ?");
                $stmt->execute([$guardianId]);
                $grec = $stmt->fetch();
                if ($grec) {
                    GuardianAuthController::sendInvitation($clubId, $grec, $token, $memberRec);
                    $invitesSent++;
                }
            } catch (\Throwable $e) {
                echo "  [WARN] invite send failed for guardian#{$guardianId}: " . $e->getMessage() . "\n";
            }
        }
    } catch (\Throwable $e) {
        echo "  [ERR] member#{$memberId}: " . $e->getMessage() . "\n";
    }
}

echo "\nSummary:\n";
echo "  guardians created : {$created}\n";
echo "  links created     : {$linked}\n";
echo "  consents recorded : {$consents}\n";
echo "  invitations sent  : {$invitesSent}\n";
echo "  skipped           : {$skipped}\n";

function parseName(string $full): array
{
    $full = trim($full);
    if ($full === '') return [null, null];
    $parts = preg_split('/\s+/', $full, 2);
    return [$parts[0] ?? null, $parts[1] ?? null];
}

function decryptIfNeeded(string $value): string
{
    if (class_exists('\\App\\Helpers\\Encryption')
        && \App\Helpers\Encryption::isConfigured()
        && str_starts_with($value, 'enc:')
    ) {
        try {
            return (string)\App\Helpers\Encryption::decrypt($value);
        } catch (\Throwable) {
            return $value;
        }
    }
    return $value;
}
