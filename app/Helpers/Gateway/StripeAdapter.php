<?php

namespace App\Helpers\Gateway;

/**
 * Stripe adapter — używa stripe/stripe-php SDK.
 *
 * Konfiguracja (z club_payment_gateways z P.5):
 *   - api_key       (sk_test_xxx / sk_live_xxx)
 *   - webhook_secret (whsec_xxx)
 *
 * Webhook URL musi być zarejestrowany w Stripe dashboard:
 *   https://your-domain/api/v1/payment/webhook?provider=stripe
 *
 * Lub stary Stripe-only endpoint /payment/webhook (kompatybilność wsteczna).
 */
class StripeAdapter implements GatewayAdapterInterface
{
    public function __construct(
        private readonly array $config, // z ClubPaymentGatewayModel.findByProvider
    ) {
    }

    public function providerKey(): string
    {
        return 'stripe';
    }

    public function createCheckout(CheckoutRequest $req): CheckoutResult
    {
        if (empty($this->config['api_key'])) {
            throw new GatewayException('Stripe api_key not configured');
        }
        if (!class_exists('\Stripe\Stripe')) {
            throw new GatewayException('Stripe SDK missing — composer require stripe/stripe-php');
        }

        try {
            \Stripe\Stripe::setApiKey($this->config['api_key']);
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency'     => strtolower($req->currency ?: 'pln'),
                        'product_data' => ['name' => $req->description],
                        'unit_amount'  => (int)round($req->amount * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode'        => 'payment',
                'success_url' => $req->successUrl,
                'cancel_url'  => $req->cancelUrl,
                'customer_email' => $req->customerEmail,
                'client_reference_id' => $req->internalReference,
                'metadata' => array_merge([
                    'club_id'   => (string)$req->clubId,
                    'member_id' => (string)$req->memberId,
                    'reference' => $req->internalReference,
                ], $req->metadata),
            ]);
            return new CheckoutResult(
                redirectUrl: $session->url,
                externalId:  $session->id,
                rawResponse: ['session_id' => $session->id, 'payment_intent' => $session->payment_intent ?? null],
            );
        } catch (\Throwable $e) {
            throw new GatewayException('Stripe checkout error: ' . $e->getMessage(), 0, $e);
        }
    }

    public function verifyWebhook(string $rawPayload, array $headers): WebhookEvent
    {
        $secret = $this->config['webhook_secret'] ?? '';
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

        $obj = $event->data->object ?? null;

        // Mapowanie typu zdarzenia do statusu
        $status = match ($event->type) {
            'checkout.session.completed', 'payment_intent.succeeded' => WebhookEvent::STATUS_PAID,
            'payment_intent.canceled', 'checkout.session.expired'     => WebhookEvent::STATUS_CANCELLED,
            'payment_intent.payment_failed'                            => WebhookEvent::STATUS_FAILED,
            'charge.refunded'                                          => WebhookEvent::STATUS_REFUNDED,
            default                                                    => WebhookEvent::STATUS_PENDING,
        };

        $externalId = $obj->id ?? '';
        $amount = isset($obj->amount_total) ? ((int)$obj->amount_total) / 100
                  : (isset($obj->amount) ? ((int)$obj->amount) / 100 : null);
        $currency = strtoupper($obj->currency ?? 'PLN');
        $internalRef = $obj->client_reference_id
            ?? ($obj->metadata->reference ?? null)
            ?? null;

        return new WebhookEvent(
            externalId:        $externalId,
            status:            $status,
            amount:            $amount,
            currency:          $currency,
            internalReference: $internalRef,
            rawPayload:        ['type' => $event->type, 'object_id' => $externalId],
        );
    }

    public function testConnection(): array
    {
        if (empty($this->config['api_key'])) {
            return ['ok' => false, 'message' => 'Stripe api_key not configured', 'details' => []];
        }
        if (!class_exists('\Stripe\Stripe')) {
            return ['ok' => false, 'message' => 'Stripe SDK missing (composer require stripe/stripe-php)', 'details' => []];
        }

        try {
            \Stripe\Stripe::setApiKey($this->config['api_key']);
            $account = \Stripe\Account::retrieve();
            return [
                'ok'      => true,
                'message' => 'Połączenie OK',
                'details' => [
                    'account_id' => $account->id ?? null,
                    'country'    => $account->country ?? null,
                    'mode'       => str_starts_with((string)$this->config['api_key'], 'sk_test_') ? 'test' : 'live',
                ],
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'details' => []];
        }
    }

    public function fetchStatus(string $externalId): TransactionStatus
    {
        if (empty($this->config['api_key'])) {
            throw new GatewayException('Stripe api_key not configured');
        }
        if (!class_exists('\Stripe\Stripe')) {
            throw new GatewayException('Stripe SDK missing');
        }

        try {
            \Stripe\Stripe::setApiKey($this->config['api_key']);
            $session = \Stripe\Checkout\Session::retrieve($externalId);

            $status = match ($session->payment_status) {
                'paid'       => WebhookEvent::STATUS_PAID,
                'unpaid'     => WebhookEvent::STATUS_PENDING,
                'no_payment_required' => WebhookEvent::STATUS_PAID,
                default      => WebhookEvent::STATUS_PENDING,
            };

            return new TransactionStatus(
                externalId:  $externalId,
                status:      $status,
                amount:      ($session->amount_total ?? 0) / 100,
                currency:    strtoupper($session->currency ?? 'PLN'),
                rawResponse: ['payment_status' => $session->payment_status],
            );
        } catch (\Throwable $e) {
            throw new GatewayException('Stripe fetch error: ' . $e->getMessage(), 0, $e);
        }
    }

    // ────────────────────────────────────────────────────────────────────
    // Subscriptions API (recurring payments — migracja 076)
    // Wszystkie metody wymagają załadowanych credentials klubu w $this->config.
    // ────────────────────────────────────────────────────────────────────

    /**
     * Utwórz Checkout Session w trybie `subscription` — po sukcesie Stripe
     * tworzy customer + payment method + subscription i wysyła webhook
     * customer.subscription.created (+ pierwsze invoice.payment_succeeded).
     *
     * Zwraca tablicę:
     *   - redirect_url:  URL Stripe Checkout
     *   - session_id:    cs_xxx (zapis do member_subscriptions.setup_session_id)
     *   - price_id:      price_xxx
     */
    public function createSubscriptionCheckoutSession(
        float  $amount,
        string $currency,
        string $productName,
        string $stripeInterval,  // 'month' | 'year'
        int    $intervalCount,    // 1, 3, 12
        string $successUrl,
        string $cancelUrl,
        array  $metadata = [],
        ?string $customerEmail = null
    ): array {
        $this->assertSdkReady();
        \Stripe\Stripe::setApiKey($this->config['api_key']);

        try {
            $session = \Stripe\Checkout\Session::create([
                'mode'        => 'subscription',
                'payment_method_types' => ['card'],
                'line_items'  => [[
                    'price_data' => [
                        'currency'     => strtolower($currency ?: 'pln'),
                        'product_data' => ['name' => $productName],
                        'unit_amount'  => (int)round($amount * 100),
                        'recurring'    => [
                            'interval'       => $stripeInterval,
                            'interval_count' => $intervalCount,
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'success_url'    => $successUrl,
                'cancel_url'     => $cancelUrl,
                'customer_email' => $customerEmail,
                'metadata'             => $metadata,
                'subscription_data'    => [
                    'metadata' => $metadata,
                ],
            ]);

            return [
                'redirect_url' => $session->url,
                'session_id'   => $session->id,
                // price_id i subscription_id będą znane dopiero po sukcesie
                // (via webhook checkout.session.completed → session.subscription)
            ];
        } catch (\Throwable $e) {
            throw new GatewayException('Stripe subscription checkout error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Pobierz pełen state Checkout Session (po returnie z Stripe).
     * Zwraca m.in. subscription ID i customer ID, żebyśmy mogli uzupełnić
     * member_subscriptions od razu (bez czekania na webhook).
     */
    public function retrieveCheckoutSession(string $sessionId): array
    {
        $this->assertSdkReady();
        \Stripe\Stripe::setApiKey($this->config['api_key']);

        try {
            $session = \Stripe\Checkout\Session::retrieve([
                'id'     => $sessionId,
                'expand' => ['subscription'],
            ]);
            $sub = $session->subscription ?? null;

            return [
                'session_id'      => $session->id,
                'payment_status'  => $session->payment_status,
                'mode'            => $session->mode,
                'customer_id'     => is_string($session->customer ?? null) ? $session->customer : ($session->customer->id ?? null),
                'subscription_id' => is_object($sub) ? ($sub->id ?? null) : (is_string($sub) ? $sub : null),
                'subscription'    => $sub,
            ];
        } catch (\Throwable $e) {
            throw new GatewayException('Stripe session retrieve error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Anuluj subskrypcję. atPeriodEnd=true → członek dopłaca okres bieżący,
     * potem subscription jest cancelled. false → natychmiast.
     */
    public function cancelSubscription(string $subscriptionId, bool $atPeriodEnd = true): array
    {
        $this->assertSdkReady();
        \Stripe\Stripe::setApiKey($this->config['api_key']);

        try {
            if ($atPeriodEnd) {
                $sub = \Stripe\Subscription::update($subscriptionId, [
                    'cancel_at_period_end' => true,
                ]);
            } else {
                $sub = \Stripe\Subscription::retrieve($subscriptionId)->cancel();
            }
            return [
                'id'                    => $sub->id,
                'status'                => $sub->status,
                'cancel_at_period_end'  => $sub->cancel_at_period_end ?? false,
                'current_period_end'    => $sub->current_period_end ?? null,
            ];
        } catch (\Throwable $e) {
            throw new GatewayException('Stripe cancel error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Pauza subskrypcji — Stripe pause_collection behavior='void' (lub
     * 'mark_uncollectible'). Member nie jest charged dopóki resumeSubscription.
     */
    public function pauseSubscription(string $subscriptionId): array
    {
        $this->assertSdkReady();
        \Stripe\Stripe::setApiKey($this->config['api_key']);

        try {
            $sub = \Stripe\Subscription::update($subscriptionId, [
                'pause_collection' => ['behavior' => 'void'],
            ]);
            return ['id' => $sub->id, 'status' => $sub->status];
        } catch (\Throwable $e) {
            throw new GatewayException('Stripe pause error: ' . $e->getMessage(), 0, $e);
        }
    }

    public function resumeSubscription(string $subscriptionId): array
    {
        $this->assertSdkReady();
        \Stripe\Stripe::setApiKey($this->config['api_key']);

        try {
            $sub = \Stripe\Subscription::update($subscriptionId, [
                'pause_collection' => '',
            ]);
            return ['id' => $sub->id, 'status' => $sub->status];
        } catch (\Throwable $e) {
            throw new GatewayException('Stripe resume error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Pobierz fresh data subskrypcji ze Stripe (np. po force-charge retry).
     */
    public function retrieveSubscription(string $subscriptionId): array
    {
        $this->assertSdkReady();
        \Stripe\Stripe::setApiKey($this->config['api_key']);

        try {
            $sub = \Stripe\Subscription::retrieve($subscriptionId);
            return [
                'id'                    => $sub->id,
                'status'                => $sub->status,
                'current_period_start'  => $sub->current_period_start ?? null,
                'current_period_end'    => $sub->current_period_end ?? null,
                'cancel_at_period_end'  => $sub->cancel_at_period_end ?? false,
                'customer'              => is_string($sub->customer ?? null) ? $sub->customer : ($sub->customer->id ?? null),
            ];
        } catch (\Throwable $e) {
            throw new GatewayException('Stripe subscription retrieve error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Manualny retry charge — używa najbardziej recent latest_invoice i robi
     * pay() na nim (jeśli failed/open).
     */
    public function retryLatestInvoice(string $subscriptionId): array
    {
        $this->assertSdkReady();
        \Stripe\Stripe::setApiKey($this->config['api_key']);

        try {
            $sub = \Stripe\Subscription::retrieve([
                'id' => $subscriptionId,
                'expand' => ['latest_invoice'],
            ]);
            $invoice = $sub->latest_invoice ?? null;
            if (!$invoice) {
                throw new GatewayException('No latest invoice for subscription');
            }
            $invId = is_object($invoice) ? $invoice->id : $invoice;
            $paid = \Stripe\Invoice::retrieve($invId)->pay();
            return [
                'invoice_id' => $paid->id,
                'status'     => $paid->status,
                'paid'       => (bool)($paid->paid ?? false),
            ];
        } catch (\Throwable $e) {
            throw new GatewayException('Stripe retry error: ' . $e->getMessage(), 0, $e);
        }
    }

    private function assertSdkReady(): void
    {
        if (empty($this->config['api_key'])) {
            throw new GatewayException('Stripe api_key not configured');
        }
        if (!class_exists('\Stripe\Stripe')) {
            throw new GatewayException('Stripe SDK missing — composer require stripe/stripe-php');
        }
    }
}
