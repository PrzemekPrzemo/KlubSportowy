<?php

namespace App\Helpers\Gateway;

/**
 * Wspólny kontrakt dla adapterów bramek płatności (Stripe, Przelewy24,
 * PayU, Tpay).
 *
 * Każdy adapter implementuje 3 metody:
 *   1. createCheckout() — tworzy sesję płatności i zwraca redirectUrl
 *   2. verifyWebhook()  — weryfikuje sygnaturę webhook'a, parsuje payload
 *   3. fetchStatus()    — sprawdza aktualny status transakcji (poll/reconciliation)
 *
 * Dane wejściowe (CheckoutRequest) i wyjściowe (CheckoutResult,
 * WebhookEvent) są standardowe — pozwala kontrolerom traktować bramki
 * uniformly.
 */
interface GatewayAdapterInterface
{
    /**
     * Tworzy sesję checkout i zwraca URL do przekierowania klienta.
     */
    public function createCheckout(CheckoutRequest $request): CheckoutResult;

    /**
     * Weryfikuje webhook (sygnatura HMAC lub dedicated header).
     * Rzuca GatewayException przy niepoprawnym podpisie.
     */
    public function verifyWebhook(string $rawPayload, array $headers): WebhookEvent;

    /**
     * Pobiera aktualny status transakcji po external_id.
     * Używane do reconciliation gdy webhook nie dotarł.
     */
    public function fetchStatus(string $externalId): TransactionStatus;

    /**
     * Identyfikator providera (przelewy24/payu/stripe/tpay).
     */
    public function providerKey(): string;

    /**
     * Realny ping API providera weryfikujący credentials.
     * Zwraca ['ok' => bool, 'message' => string, 'details' => array].
     * Nie rzuca — błędy mapuje na ok=false z opisem.
     */
    public function testConnection(): array;
}
