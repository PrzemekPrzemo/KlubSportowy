<?php

namespace App\Helpers\Shipping;

/**
 * Wspólny kontrakt dla adapterów wysyłki (InPost, w przyszłości DHL/UPS/etc.).
 *
 * Każdy adapter implementuje:
 *   1. createShipment()  — tworzy przesyłkę po stronie providera
 *   2. fetchLabel()      — zwraca URL/binarkę PDF z etykietą
 *   3. trackShipment()   — pobiera aktualny status + historię tracking
 *   4. listPaczkomats()  — wyszukuje punkty odbioru (paczkomaty/oddziały)
 *
 * Wzorowane na App\Helpers\Gateway\GatewayAdapterInterface.
 */
interface ShippingAdapterInterface
{
    public function providerKey(): string;

    public function createShipment(ShipmentRequest $request): ShipmentResult;

    /**
     * Zwraca URL do PDF z etykietą lub bezpośrednio binarkę (provider-specific).
     * InPost: zwraca URL na ich storage (signed, ważny przez ograniczony czas).
     */
    public function fetchLabel(string $externalId): string;

    /**
     * Zwraca tablicę z aktualnym statusem i historią tracking.
     * Klucze: status (string), history (array), raw (array).
     */
    public function trackShipment(string $externalId): array;

    /**
     * Wyszukuje punkty odbioru (paczkomaty) w okolicy kodu pocztowego.
     *
     * @return array<int, array{name:string, address:string, city:string, post_code:string}>
     */
    public function listPaczkomats(string $postCode, int $limit = 20): array;
}
