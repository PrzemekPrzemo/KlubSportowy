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
}
