<?php

namespace App\Helpers\Gateway;

/**
 * Standardowy DTO dla createCheckout — wszystkie adaptery używają.
 */
final class CheckoutRequest
{
    public function __construct(
        public readonly int $clubId,
        public readonly int $memberId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $description,
        public readonly string $successUrl,
        public readonly string $cancelUrl,
        /** Webhook URL — provider potwierdza tutaj transakcję */
        public readonly string $notifyUrl,
        /** Klubowy reference (np. "due#42" / "online_payment#7") */
        public readonly string $internalReference,
        /** Opcjonalny email klienta — pre-fill checkout */
        public readonly ?string $customerEmail = null,
        /** Dodatkowe metadata przekazywane providerowi (provider-specific) */
        public readonly array $metadata = [],
    ) {
    }
}
