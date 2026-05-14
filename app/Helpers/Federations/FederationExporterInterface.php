<?php

namespace App\Helpers\Federations;

/**
 * Wspólny kontrakt dla exporterów do federacji sportowych (PZPN/PZSS/PZKosz/PZLA/…).
 *
 * Wzorowane na App\Helpers\Gateway\GatewayAdapterInterface — każdy adapter
 * realizuje minimalny zestaw operacji:
 *
 *   1. exportMember()       — rejestracja nowego zawodnika w federacji
 *   2. updateMember()       — aktualizacja zmienionych danych
 *   3. fetchMemberStatus()  — pobranie aktualnego statusu (license valid?)
 *   4. testConnection()     — sanity check credentials (przed save/aktywacją)
 *
 * Wszystkie metody mogą rzucić FederationException przy błędzie komunikacji.
 * exportMember/updateMember zwracają ExportResult (.ok=false bez wyjątku,
 * gdy federacja odpowiedziała "validation error" — adapter ma rozróżniać
 * błąd techniczny od logicznego).
 */
interface FederationExporterInterface
{
    /** Identyfikator federacji w naszym systemie: PZPN/PZSS/PZKosz/PZLA/… */
    public function federationCode(): string;

    /** Rejestracja nowego zawodnika (INSERT). */
    public function exportMember(MemberPayload $member): ExportResult;

    /** Aktualizacja istniejącego zawodnika — wymaga zazwyczaj externalId. */
    public function updateMember(MemberPayload $member): ExportResult;

    /**
     * Pobierz aktualny status członka po external_id federacji.
     * Zwraca surowy array (struktura zależna od federacji).
     */
    public function fetchMemberStatus(string $externalId): array;

    /**
     * Test połączenia — sanity check API credentials.
     * Zwraca array ['ok'=>bool, 'message'=>string, 'details'=>...] analogicznie
     * jak ClubGatewayController::testConnection.
     */
    public function testConnection(): array;
}
