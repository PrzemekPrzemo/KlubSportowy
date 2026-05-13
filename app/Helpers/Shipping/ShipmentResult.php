<?php

namespace App\Helpers\Shipping;

/**
 * Wynik createShipment.
 */
final class ShipmentResult
{
    public function __construct(
        /** Provider's internal shipment id (np. "1234567" w InPost) */
        public readonly string $externalId,
        public readonly ?string $trackingNumber,
        /** URL do etykiety PDF (jeśli wygenerowana od razu) */
        public readonly ?string $labelUrl,
        /** Status początkowy (provider-specific lub "created") */
        public readonly string $status,
        /** Surowa odpowiedź providera — debug/audit */
        public readonly array $rawResponse = [],
    ) {
    }
}
