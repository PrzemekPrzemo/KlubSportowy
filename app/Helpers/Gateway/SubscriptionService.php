<?php

namespace App\Helpers\Gateway;

use App\Helpers\Database;
use App\Models\ClubPaymentGatewayModel;
use App\Models\FeeRateModel;
use App\Models\MemberSubscriptionModel;
use App\Models\SubscriptionChargeModel;

/**
 * Cienka warstwa nad adapterami — koordynuje:
 *   - tworzenie Checkout Session dla recurring
 *   - mapowanie webhook events → updates na member_subscriptions / subscription_charges
 *   - audit log do tenant_access_log przy cancel/pause
 *
 * Multi-tenant: każda metoda przyjmuje $clubId i ładuje credentials klubu
 * z ClubPaymentGatewayModel (zaszyfrowane w P.5).
 */
class SubscriptionService
{
    /**
     * Utwórz nową subskrypcję — pierwsza faza: insert pending_setup row +
     * stworzenie checkout session w Stripe.
     *
     * Zwraca array:
     *   - redirect_url: URL do redirectu usera
     *   - subscription_id: lokalny ID member_subscriptions.id
     */
    public static function createSetup(
        int    $clubId,
        int    $memberId,
        int    $feeRateId,
        string $provider,
        string $billingPeriod,
        string $successUrl,
        string $cancelUrl,
        ?string $customerEmail = null
    ): array {
        if (!in_array($billingPeriod, array_keys(MemberSubscriptionModel::PERIODS), true)) {
            throw new GatewayException('Invalid billing_period: ' . $billingPeriod);
        }

        // Załaduj fee rate (bypass scope — chcemy konkretny ID)
        $feeRate = (new FeeRateModel())->withoutScope()->findById($feeRateId);
        if (!$feeRate || (int)$feeRate['club_id'] !== $clubId) {
            throw new GatewayException('Fee rate not found or not in this club');
        }
        $amount = (float)$feeRate['amount'];
        if ($amount <= 0) {
            throw new GatewayException('Fee rate amount must be > 0');
        }

        // Załaduj credentials klubu — wymagamy active per provider
        $gatewayConfig = (new ClubPaymentGatewayModel())->findByProvider($provider);
        if (!$gatewayConfig || (int)$gatewayConfig['club_id'] !== $clubId || empty($gatewayConfig['is_active'])) {
            throw new GatewayException("Gateway {$provider} not configured/active for club {$clubId}");
        }

        // INSERT row jako pending_setup — zachowamy ID dla return-handlingu
        $db = Database::pdo();
        $stmt = $db->prepare(
            "INSERT INTO member_subscriptions
                (club_id, member_id, fee_rate_id, gateway_provider, amount, currency,
                 billing_period, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pending_setup', NOW())"
        );
        $stmt->execute([
            $clubId, $memberId, $feeRateId, $provider, $amount,
            $feeRate['currency'] ?? 'PLN', $billingPeriod,
        ]);
        $localSubId = (int)$db->lastInsertId();

        $metadata = [
            'club_id'         => (string)$clubId,
            'member_id'       => (string)$memberId,
            'fee_rate_id'     => (string)$feeRateId,
            'local_sub_id'    => (string)$localSubId,
            'billing_period'  => $billingPeriod,
            'reference'       => 'msub#' . $localSubId,
        ];

        if ($provider === 'stripe') {
            $adapter = new StripeAdapter($gatewayConfig);
            $periodMeta = MemberSubscriptionModel::PERIODS[$billingPeriod];
            $result = $adapter->createSubscriptionCheckoutSession(
                amount:         $amount,
                currency:       $feeRate['currency'] ?? 'PLN',
                productName:    'Składka: ' . ($feeRate['name'] ?? 'Składka klubowa'),
                stripeInterval: $periodMeta['stripe_interval'],
                intervalCount:  $periodMeta['stripe_count'],
                successUrl:     $successUrl . (str_contains($successUrl, '?') ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}',
                cancelUrl:      $cancelUrl,
                metadata:       $metadata,
                customerEmail:  $customerEmail,
            );

            // Zapisz session ID dla return-handlingu
            $db->prepare("UPDATE member_subscriptions SET setup_session_id = ? WHERE id = ?")
               ->execute([$result['session_id'], $localSubId]);

            return [
                'redirect_url'    => $result['redirect_url'],
                'subscription_id' => $localSubId,
                'session_id'      => $result['session_id'],
            ];
        }

        if ($provider === 'przelewy24') {
            // P24 cyclic — pierwsza transakcja jest "wallet setup" + first charge
            $adapter = new Przelewy24Adapter($gatewayConfig);
            $req = new CheckoutRequest(
                clubId:            $clubId,
                memberId:          $memberId,
                amount:            $amount,
                currency:          $feeRate['currency'] ?? 'PLN',
                description:       'Składka klubowa (cykl): ' . ($feeRate['name'] ?? ''),
                successUrl:        $successUrl,
                cancelUrl:         $cancelUrl,
                notifyUrl:         '', // ustawia caller
                internalReference: 'msub#' . $localSubId,
                customerEmail:     $customerEmail,
                metadata:          $metadata,
            );
            $r = $adapter->createRecurringSetup($req);

            $db->prepare("UPDATE member_subscriptions SET setup_session_id = ? WHERE id = ?")
               ->execute([$r->externalId, $localSubId]);

            return [
                'redirect_url'    => $r->redirectUrl,
                'subscription_id' => $localSubId,
                'session_id'      => $r->externalId,
            ];
        }

        throw new GatewayException('Unsupported provider for recurring: ' . $provider);
    }

    /**
     * Handle return z Stripe checkout — pull session_id z URL, retrieve sesję
     * ze Stripe, zaktualizuj member_subscriptions o subscription_id +
     * customer_id (zanim webhook dotrze).
     */
    public static function handleStripeReturn(int $clubId, string $sessionId): ?array
    {
        $msubModel = new MemberSubscriptionModel();
        $sub = $msubModel->findBySetupSession($sessionId);
        if (!$sub || (int)$sub['club_id'] !== $clubId) {
            return null;
        }

        $gatewayConfig = (new ClubPaymentGatewayModel())->findByProvider('stripe');
        if (!$gatewayConfig) return null;

        try {
            $adapter = new StripeAdapter($gatewayConfig);
            $session = $adapter->retrieveCheckoutSession($sessionId);
            if (!$session['subscription_id']) {
                return $sub; // pending — webhook uzupełni
            }

            $subData = $adapter->retrieveSubscription($session['subscription_id']);
            $db = Database::pdo();
            $db->prepare(
                "UPDATE member_subscriptions
                 SET external_subscription_id = ?,
                     external_customer_id     = ?,
                     status                   = ?,
                     current_period_start     = FROM_UNIXTIME(?),
                     current_period_end       = FROM_UNIXTIME(?),
                     next_charge_at           = FROM_UNIXTIME(?)
                 WHERE id = ?"
            )->execute([
                $session['subscription_id'],
                $session['customer_id'],
                $subData['status'] === 'active' ? 'active' : 'pending_setup',
                $subData['current_period_start'] ?: time(),
                $subData['current_period_end'] ?: (time() + 30 * 86400),
                $subData['current_period_end'] ?: (time() + 30 * 86400),
                (int)$sub['id'],
            ]);
            return $msubModel->findBySetupSession($sessionId);
        } catch (\Throwable $e) {
            error_log('SubscriptionService handleStripeReturn failed: ' . $e->getMessage());
            return $sub;
        }
    }

    /**
     * Anuluj subskrypcję.
     */
    public static function cancel(array $subscription, bool $atPeriodEnd = true, ?string $reason = null): array
    {
        $clubId = (int)$subscription['club_id'];
        $provider = (string)$subscription['gateway_provider'];

        $gatewayConfig = (new ClubPaymentGatewayModel())->findByProvider($provider);
        if (!$gatewayConfig) {
            throw new GatewayException("Gateway {$provider} not configured");
        }

        $now = date('Y-m-d H:i:s');
        $db = Database::pdo();

        if ($provider === 'stripe' && !empty($subscription['external_subscription_id'])) {
            $adapter = new StripeAdapter($gatewayConfig);
            $r = $adapter->cancelSubscription($subscription['external_subscription_id'], $atPeriodEnd);
            $newStatus = ($atPeriodEnd && ($r['cancel_at_period_end'] ?? false)) ? 'active' : 'cancelled';
            $db->prepare(
                "UPDATE member_subscriptions
                 SET status = ?, cancelled_at = ?, cancellation_reason = ?
                 WHERE id = ?"
            )->execute([$newStatus, $now, $reason, (int)$subscription['id']]);
        } elseif ($provider === 'przelewy24') {
            $adapter = new Przelewy24Adapter($gatewayConfig);
            if (!empty($subscription['external_customer_id'])) {
                $adapter->cancelRecurring($subscription['external_customer_id']);
            }
            $db->prepare(
                "UPDATE member_subscriptions
                 SET status = 'cancelled', cancelled_at = ?, cancellation_reason = ?, next_charge_at = NULL
                 WHERE id = ?"
            )->execute([$now, $reason, (int)$subscription['id']]);
        }

        // Audit
        self::audit($clubId, (int)$subscription['id'], 'subscription_cancelled', [
            'at_period_end' => $atPeriodEnd,
            'reason'        => $reason,
        ]);

        return ['ok' => true];
    }

    public static function pause(array $subscription): array
    {
        if ($subscription['gateway_provider'] !== 'stripe') {
            throw new GatewayException('Pause is supported only for Stripe subscriptions');
        }
        $gatewayConfig = (new ClubPaymentGatewayModel())->findByProvider('stripe');
        if (!$gatewayConfig) throw new GatewayException('Stripe not configured');
        if (empty($subscription['external_subscription_id'])) {
            throw new GatewayException('Subscription has no external_subscription_id');
        }

        $adapter = new StripeAdapter($gatewayConfig);
        $adapter->pauseSubscription($subscription['external_subscription_id']);

        Database::pdo()->prepare(
            "UPDATE member_subscriptions SET status = 'paused' WHERE id = ?"
        )->execute([(int)$subscription['id']]);

        self::audit((int)$subscription['club_id'], (int)$subscription['id'], 'subscription_paused', []);
        return ['ok' => true];
    }

    public static function resume(array $subscription): array
    {
        if ($subscription['gateway_provider'] !== 'stripe') {
            throw new GatewayException('Resume is supported only for Stripe subscriptions');
        }
        $gatewayConfig = (new ClubPaymentGatewayModel())->findByProvider('stripe');
        if (!$gatewayConfig) throw new GatewayException('Stripe not configured');
        if (empty($subscription['external_subscription_id'])) {
            throw new GatewayException('Subscription has no external_subscription_id');
        }

        $adapter = new StripeAdapter($gatewayConfig);
        $adapter->resumeSubscription($subscription['external_subscription_id']);

        Database::pdo()->prepare(
            "UPDATE member_subscriptions SET status = 'active' WHERE id = ?"
        )->execute([(int)$subscription['id']]);

        self::audit((int)$subscription['club_id'], (int)$subscription['id'], 'subscription_resumed', []);
        return ['ok' => true];
    }

    /**
     * Force-charge — admin manualnie wywołuje retry latest invoice (Stripe)
     * lub chargeRecurring (P24).
     */
    public static function forceCharge(array $subscription): array
    {
        $clubId = (int)$subscription['club_id'];
        $provider = (string)$subscription['gateway_provider'];
        $gatewayConfig = (new ClubPaymentGatewayModel())->findByProvider($provider);
        if (!$gatewayConfig) throw new GatewayException("Gateway {$provider} not configured");

        if ($provider === 'stripe') {
            if (empty($subscription['external_subscription_id'])) {
                throw new GatewayException('No external_subscription_id');
            }
            $adapter = new StripeAdapter($gatewayConfig);
            $r = $adapter->retryLatestInvoice($subscription['external_subscription_id']);

            (new SubscriptionChargeModel())->insertUnscoped([
                'club_id'             => $clubId,
                'subscription_id'     => (int)$subscription['id'],
                'external_invoice_id' => $r['invoice_id'],
                'amount'              => (float)$subscription['amount'],
                'currency'            => $subscription['currency'],
                'status'              => $r['paid'] ? 'succeeded' : 'failed',
                'charged_at'          => $r['paid'] ? date('Y-m-d H:i:s') : null,
            ]);

            self::audit($clubId, (int)$subscription['id'], 'subscription_force_charge', $r);
            return $r;
        }

        if ($provider === 'przelewy24') {
            if (empty($subscription['external_customer_id'])) {
                throw new GatewayException('No external_customer_id (P24 clientId)');
            }
            $adapter = new Przelewy24Adapter($gatewayConfig);
            $r = $adapter->chargeRecurring(
                $subscription['external_customer_id'],
                (int)round((float)$subscription['amount'] * 100),
                'msub#' . $subscription['id'],
                $subscription['currency']
            );

            (new SubscriptionChargeModel())->insertUnscoped([
                'club_id'             => $clubId,
                'subscription_id'     => (int)$subscription['id'],
                'external_payment_id' => $r['sessionId'] ?? null,
                'amount'              => (float)$subscription['amount'],
                'currency'            => $subscription['currency'],
                'status'              => ($r['status'] ?? null) === 'success' ? 'succeeded' : 'failed',
                'failure_reason'      => $r['error'] ?? null,
                'charged_at'          => ($r['status'] ?? null) === 'success' ? date('Y-m-d H:i:s') : null,
            ]);
            self::audit($clubId, (int)$subscription['id'], 'subscription_force_charge', $r);
            return $r;
        }

        throw new GatewayException('Unsupported provider');
    }

    /**
     * Audit log do activity_log — best-effort.
     */
    private static function audit(int $clubId, int $subscriptionId, string $action, array $details): void
    {
        try {
            Database::pdo()->prepare(
                "INSERT INTO activity_log (club_id, action, entity, entity_id, details, ip_address)
                 VALUES (?, ?, 'member_subscription', ?, ?, ?)"
            )->execute([
                $clubId, $action, $subscriptionId,
                json_encode($details, JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (\Throwable $e) {
            error_log('SubscriptionService audit failed: ' . $e->getMessage());
        }
    }
}
