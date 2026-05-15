<?php
// ============================================================
// cli/charge_recurring_payments.php — cron driver dla P24 recurring.
//
// Stripe robi auto-charge sam (i wysyła webhook invoice.payment_*),
// więc Stripe subskrypcje są tu OBSERWOWANE tylko dla "past_due
// retry hint" — nie wykonujemy chargeu ze swojej strony.
//
// P24 nie ma natywnego "subscription scheduler" — musimy sami iterować
// po member_subscriptions WHERE status='active' AND provider='przelewy24'
// AND next_charge_at <= NOW() i wywołać chargeRecurring() przez API.
//
// Usage:
//   php cli/charge_recurring_payments.php           # dry-run (log only)
//   php cli/charge_recurring_payments.php --commit  # rzeczywiście charguj
// Cron: */15 * * * * php /path/to/cli/charge_recurring_payments.php --commit
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

use App\Helpers\ClubContext;
use App\Helpers\Database;
use App\Helpers\Gateway\Przelewy24Adapter;
use App\Models\ClubPaymentGatewayModel;
use App\Models\MemberSubscriptionModel;
use App\Models\SubscriptionChargeModel;

$commit = in_array('--commit', $argv, true);
$tag = '[' . date('Y-m-d H:i:s') . '] charge_recurring';

echo "{$tag} starting (commit=" . ($commit ? 'yes' : 'no') . ")\n";

$subs = (new MemberSubscriptionModel())->dueP24Charges();
if (!$subs) {
    echo "{$tag} no P24 subscriptions due. exit.\n";
    exit(0);
}

echo "{$tag} found " . count($subs) . " P24 subscriptions to charge\n";

$ok = 0;
$failed = 0;

foreach ($subs as $sub) {
    $sid = (int)$sub['id'];
    $clubId = (int)$sub['club_id'];

    // Per-club gateway config (every club has own P24 credentials)
    ClubContext::set($clubId);
    $gatewayConfig = (new ClubPaymentGatewayModel())->findByProvider('przelewy24');
    if (!$gatewayConfig || empty($gatewayConfig['is_active'])) {
        echo "{$tag} sub#{$sid} club#{$clubId}: SKIP (gateway inactive)\n";
        continue;
    }
    if (empty($sub['external_customer_id'])) {
        echo "{$tag} sub#{$sid} club#{$clubId}: SKIP (no external_customer_id)\n";
        continue;
    }

    $amountCents = (int)round((float)$sub['amount'] * 100);
    $orderRef = 'msub#' . $sid;

    if (!$commit) {
        $amountDisplay = number_format($amountCents / 100, 2);
        echo "{$tag} sub#{$sid} club#{$clubId}: DRY-RUN charge {$amountDisplay} {$sub['currency']}\n";
        continue;
    }

    try {
        $adapter = new Przelewy24Adapter($gatewayConfig);
        $r = $adapter->chargeRecurring(
            (string)$sub['external_customer_id'],
            $amountCents,
            $orderRef,
            (string)$sub['currency']
        );
        $success = ($r['status'] ?? null) === 'success';

        // Log charge
        (new SubscriptionChargeModel())->insertUnscoped([
            'club_id'             => $clubId,
            'subscription_id'     => $sid,
            'external_payment_id' => $r['sessionId'] ?? null,
            'amount'              => (float)$sub['amount'],
            'currency'            => $sub['currency'],
            'status'              => $success ? 'succeeded' : 'failed',
            'failure_reason'      => $success ? null : (string)($r['error'] ?? 'unknown'),
            'charged_at'          => $success ? date('Y-m-d H:i:s') : null,
        ]);

        $pdo = Database::pdo();
        if ($success) {
            // Advance next_charge_at by period months
            $next = MemberSubscriptionModel::calcNextCharge(new DateTime(), (string)$sub['billing_period']);
            $pdo->prepare(
                "UPDATE member_subscriptions
                 SET last_payment_at = NOW(), last_payment_status = 'succeeded',
                     failed_charges_count = 0,
                     current_period_start = NOW(),
                     current_period_end = ?,
                     next_charge_at = ?
                 WHERE id = ?"
            )->execute([$next->format('Y-m-d H:i:s'), $next->format('Y-m-d H:i:s'), $sid]);
            $ok++;
            echo "{$tag} sub#{$sid}: SUCCESS\n";
        } else {
            $pdo->prepare(
                "UPDATE member_subscriptions
                 SET last_payment_status = 'failed',
                     failed_charges_count = failed_charges_count + 1,
                     status = CASE WHEN status='active' AND failed_charges_count >= 2 THEN 'past_due' ELSE status END
                 WHERE id = ?"
            )->execute([$sid]);
            $failed++;
            echo "{$tag} sub#{$sid}: FAILED (" . ($r['error'] ?? 'unknown') . ")\n";
        }
    } catch (\Throwable $e) {
        $failed++;
        echo "{$tag} sub#{$sid}: EXCEPTION " . $e->getMessage() . "\n";
        error_log("[charge_recurring] sub#{$sid}: " . $e->getMessage());
    }

    ClubContext::clear();
}

echo "{$tag} done. ok={$ok} failed={$failed}\n";
exit(0);
