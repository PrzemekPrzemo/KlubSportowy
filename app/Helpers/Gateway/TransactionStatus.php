<?php

namespace App\Helpers\Gateway;

/**
 * Wynik fetchStatus — używane do reconciliation gdy webhook nie dotarł.
 */
final class TransactionStatus
{
    public function __construct(
        public readonly string $externalId,
        public readonly string $status, // mirror WebhookEvent::STATUS_*
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        public readonly array $rawResponse = [],
    ) {
    }
}
