<?php

namespace App\Helpers\Gateway;

/**
 * Standardowy event z webhooka — wszystkie providery normalizujemy do
 * tego samego formatu.
 */
final class WebhookEvent
{
    public const STATUS_PAID      = 'paid';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_REFUNDED  = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';

    public function __construct(
        public readonly string $externalId,
        /** PAID|PENDING|FAILED|REFUNDED|CANCELLED */
        public readonly string $status,
        /** Kwota faktycznie wpłacona */
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        /** Internal reference przekazany w createCheckout (round-trip) */
        public readonly ?string $internalReference = null,
        /** Surowy payload (audit) */
        public readonly array $rawPayload = [],
    ) {
    }
}
