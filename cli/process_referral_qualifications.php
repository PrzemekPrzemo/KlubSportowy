<?php
// ============================================================
// cli/process_referral_qualifications.php — Migracja 081
//
// Affiliate / referral program — nightly worker.
//
// Iteruje wszystkie club_referrals.status='pending', sprawdza czy
// polecony klub przeszedl na platny plan (club_subscriptions.status='active')
// i czy minelo min_paid_months z referral_rewards_config. Jesli tak —
// aplikuje reward dla referrera (discount/months_free/credit), aktualizuje
// rekord (status=qualified, qualified_at=NOW, reward_applied=1) i kolejkuje
// email "referral_qualified".
//
// Cron schedule (zalecane, nightly):
//   15 3 * * * /usr/bin/php /var/www/.../cli/process_referral_qualifications.php
//
// Dry-run flag:
//   php cli/process_referral_qualifications.php --dry-run
// ============================================================
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap/php_version_check.php';

define('ROOT_PATH', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = ROOT_PATH . '/app/' . $relative . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

$vendor = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($vendor)) {
    require $vendor;
}

$helpersFile = ROOT_PATH . '/app/Helpers/Helpers.php';
if (file_exists($helpersFile)) {
    require_once $helpersFile;
}

$localApp = ROOT_PATH . '/config/app.local.php';
$cfg = file_exists($localApp) ? require $localApp : require ROOT_PATH . '/config/app.php';
date_default_timezone_set($cfg['timezone'] ?? 'Europe/Warsaw');

$dryRun = in_array('--dry-run', $argv ?? [], true);
$verbose = in_array('-v', $argv ?? [], true) || in_array('--verbose', $argv ?? [], true);

echo "[" . date('Y-m-d H:i:s') . "] Referral qualification cron "
   . ($dryRun ? '(DRY RUN)' : 'starting...') . "\n";

try {
    $referrals = (new \App\Models\ReferralModel())->listPending();
} catch (\Throwable $e) {
    fwrite(STDERR, "[ERR] Cannot load pending referrals: " . $e->getMessage() . "\n");
    exit(1);
}

$total      = count($referrals);
$qualified  = 0;
$skipped    = 0;
$errors     = 0;

echo "[..] Processing {$total} pending referrals.\n";

foreach ($referrals as $r) {
    $id = (int)$r['id'];
    try {
        if ($verbose) {
            echo "  -> #{$id} referred_club={$r['referred_club_id']} ";
        }
        if ($dryRun) {
            echo "(dry-run, skipped)\n";
            $skipped++;
            continue;
        }
        $applied = \App\Helpers\ReferralRewardService::processQualification($r);
        if ($applied) {
            $qualified++;
            if ($verbose) {
                echo "QUALIFIED\n";
            }
        } else {
            $skipped++;
            if ($verbose) {
                echo "not ready\n";
            }
        }
    } catch (\Throwable $e) {
        $errors++;
        fwrite(STDERR, "[ERR] referral #{$id}: " . $e->getMessage() . "\n");
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Done."
   . " qualified={$qualified} skipped={$skipped} errors={$errors}\n";

exit($errors > 0 ? 2 : 0);
