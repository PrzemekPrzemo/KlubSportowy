<?php

namespace App\Helpers\Gateway;

/**
 * Wynik createCheckout — adapter zwraca URL do przekierowania + external_id.
 */
final class CheckoutResult
{
    public function __construct(
        /** URL do redirectu (Stripe Checkout, Przelewy24 trnRequest, ...) */
        public readonly string $redirectUrl,
        /** External transaction ID providera (do reconciliation) */
        public readonly string $externalId,
        /** Surowa odpowiedź providera (dla debug/audit) */
        public readonly array $rawResponse = [],
    ) {
    }
}
