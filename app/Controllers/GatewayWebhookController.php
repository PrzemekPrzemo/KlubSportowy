<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Gateway\GatewayException;
use App\Helpers\Gateway\GatewayFactory;
use App\Helpers\Gateway\WebhookEvent;
use App\Models\ClubPaymentGatewayModel;
use App\Models\MemberSubscriptionModel;
use App\Models\OnlinePaymentModel;
use App\Models\PaymentDueModel;
use App\Models\SubscriptionChargeModel;

/**
 * Faza T.3 — uniwersalny webhook router dla wszystkich bramek.
 *
 * Endpoint: POST /api/v1/payment/webhook/:provider
 * (BEZ CSRF — uwierzytelniane sygnaturą HMAC per adapter)
 *
 * Logic:
 *   1. Wyłuskaj provider z URL → wybierz adapter z GatewayFactory
 *   2. adapter.verifyWebhook(rawPayload, headers) → WebhookEvent
 *   3. Znajdź online_payment po external_id (provider_id) lub
 *      internalReference
 *   4. Dla statusu PAID:
 *      - online_payments.markPaid()
 *      - bookToPayments() → INSERT do payments
 *      - PaymentDueModel.applyPayment() jeśli ref pasuje do due#X
 *   5. Zwróć 200 OK
 *
 * Stary endpoint /webhook/payment (PaymentWebhookController) pozostaje
 * dla SaaS billing klubów (billing_invoices) — różny use case.
 */
class GatewayWebhookController extends BaseController
{
    public function handle(string $provider): void
    {
        $rawPayload = file_get_contents('php://input') ?: '';

        // Wyłuskaj club_id — albo z payload (Stripe metadata / member_subscriptions
        // lookup po sub_id), albo z hint param ?club_id=X w URL (P24/PayU/Tpay).
        $clubId = $this->detectClubId($provider, $rawPayload);
        if ($clubId === null) {
            $this->json(['error' => 'cannot determine club_id'], 400);
        }

        // Załaduj config bramki dla klubu (z P.5)
        $gatewayConfig = (new ClubPaymentGatewayModel())->findByProvider($provider);
        if (!$gatewayConfig || (int)$gatewayConfig['club_id'] !== $clubId) {
            $this->json(['error' => 'gateway not configured for club'], 404);
        }

        // Headers (case-insensitive, normalize)
        $headers = $this->getHeaders();

        // ── Stripe subscription events branch ──
        // verifyWebhook() w adapterze obsługuje 1-shot charge events i nie
        // mapuje invoice.payment_succeeded → subscription update. Detect tu
        // i routing do processSubscriptionEvent().
        if ($provider === 'stripe' && $this->isSubscriptionEvent($rawPayload)) {
            try {
                $event = $this->verifyStripeRawEvent($rawPayload, $headers, $gatewayConfig);
            } catch (GatewayException $e) {
                error_log('Stripe sub webhook verify failed: ' . $e->getMessage());
                $this->json(['error' => 'verification failed: ' . $e->getMessage()], 403);
            }
            $this->processSubscriptionEvent($event, $clubId);
            $this->json(['status' => 'ok'], 200);
        }

        $adapter = GatewayFactory::forProvider($provider, $gatewayConfig);
        if ($adapter === null) {
            $this->json(['error' => 'unsupported provider: ' . $provider], 400);
        }

        try {
            $event = $adapter->verifyWebhook($rawPayload, $headers);
        } catch (GatewayException $e) {
            error_log('Gateway webhook verification failed: ' . $e->getMessage());
            $this->json(['error' => 'verification failed: ' . $e->getMessage()], 403);
        }

        $this->processEvent($event, $clubId);
        $this->json(['status' => 'ok'], 200);
    }

    private function isSubscriptionEvent(string $rawPayload): bool
    {
        $decoded = json_decode($rawPayload, true);
        $type = $decoded['type'] ?? '';
        return in_array($type, [
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'customer.subscription.paused',
            'customer.subscription.resumed',
            'invoice.payment_succeeded',
            'invoice.payment_failed',
            'checkout.session.completed', // dla subscription mode
        ], true);
    }

    /**
     * Pełna weryfikacja Stripe-Signature dla subscription events. Zwracamy
     * decoded array (nie WebhookEvent — używamy bogatszej struktury).
     */
    private function verifyStripeRawEvent(string $rawPayload, array $headers, array $gatewayConfig): array
    {
        $secret = $gatewayConfig['webhook_secret'] ?? '';
        if ($secret === '') {
            throw new GatewayException('Stripe webhook_secret not configured');
        }
        if (!class_exists('\Stripe\Webhook')) {
            throw new GatewayException('Stripe SDK missing');
        }
        $sig = $headers['Stripe-Signature']
            ?? $headers['stripe-signature']
            ?? $headers['HTTP_STRIPE_SIGNATURE']
            ?? '';
        if ($sig === '') {
            throw new GatewayException('Missing Stripe-Signature header');
        }
        try {
            $event = \Stripe\Webhook::constructEvent($rawPayload, $sig, $secret);
        } catch (\Throwable $e) {
            throw new GatewayException('Stripe signature mismatch: ' . $e->getMessage(), 0, $e);
        }
        return [
            'type'   => $event->type,
            'object' => $event->data->object,
        ];
    }

    /**
     * Próba wyłuskania club_id z webhook'a:
     *   1. ?club_id=X w query string (rekomendowane dla P24/PayU/Tpay
     *      bo mają to w URL notify)
     *   2. Stripe metadata.club_id w body
     *   3. internalReference parsing — jeśli zawiera due#X, sprawdzamy
     *      payment_due.club_id
     */
    private function detectClubId(string $provider, string $payload): ?int
    {
        // 1. Query param
        if (!empty($_GET['club_id']) && is_numeric($_GET['club_id'])) {
            return (int)$_GET['club_id'];
        }

        // 2. Stripe metadata
        if ($provider === 'stripe') {
            $decoded = json_decode($payload, true);
            $obj = $decoded['data']['object'] ?? [];
            $cid = $obj['metadata']['club_id'] ?? null;
            if (is_numeric($cid)) return (int)$cid;

            // Subscription events: lookup po external_subscription_id w
            // member_subscriptions (jeśli wcześniej zapisaliśmy w return-handler).
            $subId = null;
            if (isset($obj['object']) && $obj['object'] === 'subscription') {
                $subId = $obj['id'] ?? null;
            } elseif (isset($obj['object']) && $obj['object'] === 'invoice') {
                $subId = $obj['subscription'] ?? null;
            } elseif (isset($obj['object']) && $obj['object'] === 'checkout.session') {
                $subId = $obj['subscription'] ?? null;
            }
            if ($subId) {
                $row = (new MemberSubscriptionModel())->findByExternalSubscriptionId('stripe', (string)$subId);
                if ($row) return (int)$row['club_id'];
            }
        }

        // 3. Look up via online_payments by reference
        if (!empty($_GET['ref'])) {
            $stmt = Database::pdo()->prepare(
                "SELECT club_id FROM online_payments WHERE provider_id = ? OR description LIKE ? LIMIT 1"
            );
            $stmt->execute([(string)$_GET['ref'], '%' . $_GET['ref'] . '%']);
            $cid = $stmt->fetchColumn();
            if ($cid !== false) return (int)$cid;
        }

        return null;
    }

    private function getHeaders(): array
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders() ?: [];
        }
        // Fallback do $_SERVER (nginx FPM bez getallheaders)
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $name = ucwords(strtolower($name), '-');
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    /**
     * Process WebhookEvent — update online_payment, book do payments,
     * apply do payment_dues.
     */
    private function processEvent(WebhookEvent $event, int $clubId): void
    {
        $db = Database::pdo();

        // Znajdź online_payment po external_id (provider_id w DB) lub
        // po internal reference (description match)
        $opModel = new OnlinePaymentModel();
        $opStmt = $db->prepare(
            "SELECT * FROM online_payments
             WHERE club_id = ? AND (provider_id = ? OR description LIKE ?)
             ORDER BY id DESC LIMIT 1"
        );
        $opStmt->execute([$clubId, $event->externalId, '%' . ($event->internalReference ?? '') . '%']);
        $op = $opStmt->fetch();

        if (!$op) {
            error_log("Webhook event without matching online_payment: clubId={$clubId}, ext={$event->externalId}, ref={$event->internalReference}");
            return;
        }

        $opId = (int)$op['id'];

        if ($event->status === WebhookEvent::STATUS_PAID && $op['status'] !== 'paid') {
            $opModel->markPaid($opId, $event->externalId);
            $opModel->bookToPayments($opId);

            // Jeśli internalReference wskazuje na due#X → applyPayment
            if ($event->internalReference && preg_match('/due#(\d+)/', (string)$event->internalReference, $m)) {
                $dueId = (int)$m[1];
                (new PaymentDueModel())->applyPayment($dueId, (float)$op['amount']);
            }

            // Audit
            $db->prepare(
                "INSERT INTO activity_log (club_id, action, entity, details, ip_address)
                 VALUES (?, 'payment_received', 'online_payment', ?, ?)"
            )->execute([
                $clubId,
                json_encode([
                    'op_id' => $opId,
                    'amount' => $op['amount'],
                    'provider' => $event->rawPayload['type'] ?? null,
                ], JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } elseif ($event->status === WebhookEvent::STATUS_FAILED && $op['status'] !== 'paid') {
            $opModel->markFailed($opId, 'webhook reported failed');
        }
    }

    /**
     * Stripe subscription / invoice events → updates member_subscriptions
     * + INSERT subscription_charges.
     */
    private function processSubscriptionEvent(array $event, int $clubId): void
    {
        $type = (string)$event['type'];
        $obj  = $event['object'];

        $subModel = new MemberSubscriptionModel();
        $chModel  = new SubscriptionChargeModel();
        $db = Database::pdo();

        switch ($type) {

            case 'checkout.session.completed': {
                // Setup sukces — uzupełniamy subscription_id + customer_id
                if (($obj->mode ?? '') !== 'subscription') return;
                $sessionId = $obj->id ?? '';
                $subId     = is_object($obj->subscription ?? null) ? ($obj->subscription->id ?? '') : (string)($obj->subscription ?? '');
                $customer  = is_object($obj->customer ?? null) ? ($obj->customer->id ?? '') : (string)($obj->customer ?? '');
                if ($sessionId && $subId) {
                    $db->prepare(
                        "UPDATE member_subscriptions
                         SET external_subscription_id = ?, external_customer_id = ?, status = 'active'
                         WHERE setup_session_id = ? AND club_id = ?"
                    )->execute([$subId, $customer ?: null, $sessionId, $clubId]);
                }
                return;
            }

            case 'customer.subscription.created':
            case 'customer.subscription.updated': {
                $subId  = $obj->id ?? '';
                $status = $obj->status ?? 'active';
                $cancelAtPeriodEnd = (bool)($obj->cancel_at_period_end ?? false);
                $periodStart = $obj->current_period_start ?? null;
                $periodEnd   = $obj->current_period_end ?? null;

                $mapped = match ($status) {
                    'active', 'trialing'           => $cancelAtPeriodEnd ? 'active' : 'active',
                    'past_due', 'unpaid'           => 'past_due',
                    'canceled', 'incomplete_expired' => 'cancelled',
                    'paused'                       => 'paused',
                    'incomplete'                   => 'pending_setup',
                    default                        => 'active',
                };

                $fields = ['status' => $mapped];
                if ($periodStart) $fields['current_period_start'] = date('Y-m-d H:i:s', (int)$periodStart);
                if ($periodEnd) {
                    $fields['current_period_end'] = date('Y-m-d H:i:s', (int)$periodEnd);
                    $fields['next_charge_at']     = date('Y-m-d H:i:s', (int)$periodEnd);
                }
                if ($status === 'canceled' && empty($obj->canceled_at) === false) {
                    $fields['cancelled_at'] = date('Y-m-d H:i:s', (int)$obj->canceled_at);
                }
                $subModel->updateStatusByExternalId('stripe', (string)$subId, $fields);
                return;
            }

            case 'customer.subscription.deleted': {
                $subId = $obj->id ?? '';
                $subModel->updateStatusByExternalId('stripe', (string)$subId, [
                    'status'       => 'cancelled',
                    'cancelled_at' => date('Y-m-d H:i:s'),
                ]);
                return;
            }

            case 'customer.subscription.paused': {
                $subId = $obj->id ?? '';
                $subModel->updateStatusByExternalId('stripe', (string)$subId, ['status' => 'paused']);
                return;
            }

            case 'customer.subscription.resumed': {
                $subId = $obj->id ?? '';
                $subModel->updateStatusByExternalId('stripe', (string)$subId, ['status' => 'active']);
                return;
            }

            case 'invoice.payment_succeeded':
            case 'invoice.payment_failed': {
                $invoiceId = $obj->id ?? '';
                $stripeSubId = is_object($obj->subscription ?? null) ? ($obj->subscription->id ?? '') : (string)($obj->subscription ?? '');
                if (!$stripeSubId) return;

                $sub = $subModel->findByExternalSubscriptionId('stripe', $stripeSubId);
                if (!$sub) {
                    error_log("Stripe invoice event without local subscription: {$stripeSubId}");
                    return;
                }

                $isPaid = $type === 'invoice.payment_succeeded';
                $amount = isset($obj->amount_paid) ? ((int)$obj->amount_paid) / 100
                         : (isset($obj->amount_due) ? ((int)$obj->amount_due) / 100 : (float)$sub['amount']);
                $currency = strtoupper((string)($obj->currency ?? $sub['currency'] ?? 'PLN'));
                $periodStart = $obj->period_start ?? ($obj->lines->data[0]->period->start ?? null);
                $periodEnd   = $obj->period_end   ?? ($obj->lines->data[0]->period->end ?? null);

                $chModel->insertUnscoped([
                    'club_id'             => (int)$sub['club_id'],
                    'subscription_id'     => (int)$sub['id'],
                    'external_invoice_id' => $invoiceId,
                    'external_payment_id' => is_object($obj->payment_intent ?? null)
                                                ? ($obj->payment_intent->id ?? null)
                                                : (string)($obj->payment_intent ?? null),
                    'amount'              => $amount,
                    'currency'            => $currency,
                    'status'              => $isPaid ? 'succeeded' : 'failed',
                    'failure_reason'      => $isPaid ? null : (string)($obj->last_finalization_error->message ?? 'invoice payment_failed'),
                    'period_start'        => $periodStart ? date('Y-m-d H:i:s', (int)$periodStart) : null,
                    'period_end'          => $periodEnd   ? date('Y-m-d H:i:s', (int)$periodEnd)   : null,
                    'charged_at'          => $isPaid ? date('Y-m-d H:i:s') : null,
                ]);

                if ($isPaid) {
                    $subModel->updateStatusByExternalId('stripe', $stripeSubId, [
                        'last_payment_at'      => date('Y-m-d H:i:s'),
                        'last_payment_status'  => 'succeeded',
                        'failed_charges_count' => 0,
                    ]);
                } else {
                    // Inkrementuj failed counter (manual SQL bo updateStatusByExternalId
                    // robi SET ?=?)
                    $db->prepare(
                        "UPDATE member_subscriptions
                         SET failed_charges_count = failed_charges_count + 1,
                             last_payment_status  = 'failed',
                             status               = CASE WHEN status='active' THEN 'past_due' ELSE status END
                         WHERE gateway_provider = 'stripe' AND external_subscription_id = ?"
                    )->execute([$stripeSubId]);
                }
                return;
            }
        }
    }
}
