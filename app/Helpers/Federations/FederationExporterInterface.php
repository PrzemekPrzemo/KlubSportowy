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
    /**
     * Status implementacji adaptera — używany przez UI do honest oznaczenia,
     * co adapter realnie robi.
     *
     *   'scraping'      — publiczne dane scrapowane z portalu federacji
     *   'login'         — wymaga loginu klubu (cookie session)
     *   'api'           — formalne REST API
     *   'csv_only'      — tylko CSV fallback
     *   'stub'          — sygnatury bez impl (wymaga umowy partnerskiej, etc.)
     */
    public const STATUS_SCRAPING = 'scraping';
    public const STATUS_LOGIN    = 'login';
    public const STATUS_API      = 'api';
    public const STATUS_CSV_ONLY = 'csv_only';
    public const STATUS_STUB     = 'stub';

    /** Identyfikator federacji w naszym systemie: PZPN/PZSS/PZKosz/PZLA/… */
    public function federationCode(): string;

    /**
     * Status adaptera — jedna z STATUS_* stałych. UI używa do oznaczenia
     * realnej możliwości integracji (zielone/żółte/czerwone/niebieskie badge).
     */
    public function adapterStatus(): string;

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
