<?php

namespace App\Helpers\Shipping;

/**
 * DTO dla createShipment — opis przesyłki do utworzenia.
 *
 * Dla paczkomatu wystarczy targetLockerId. Dla kuriera potrzebny pełny adres.
 */
final class ShipmentRequest
{
    public function __construct(
        public readonly int $clubId,
        public readonly string $recipientName,
        public readonly string $recipientEmail,
        public readonly string $recipientPhone,
        /** Rozmiar paczki: A (8x38x64), B (19x38x64), C (41x38x64) */
        public readonly string $size = 'A',
        /** np. inpost_locker_standard / inpost_courier_standard */
        public readonly string $service = 'inpost_locker_standard',
        /** ID paczkomatu docelowego (wymagane gdy service=locker) */
        public readonly ?string $targetLockerId = null,
        /** Adres odbiorcy — wymagany dla kuriera, opcjonalny dla paczkomatu */
        public readonly ?string $recipientStreet = null,
        public readonly ?string $recipientBuilding = null,
        public readonly ?string $recipientCity = null,
        public readonly ?string $recipientPostCode = null,
        /** Opis zawartości (opcjonalny) */
        public readonly ?string $description = null,
        /** Wymiary / waga (kg) — opcjonalne, fallback to defaults per size */
        public readonly ?float $weightKg = null,
        /** Powiązanie z członkiem klubu (nullable — wysyłka może być inną osobą) */
        public readonly ?int $memberId = null,
        public readonly ?string $internalNote = null,
    ) {
    }
}
