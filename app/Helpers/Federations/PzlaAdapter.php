<?php

namespace App\Helpers\Federations;

/**
 * Adapter PZLA (Polski Związek Lekkiej Atletyki) — system DOMTEL / PZLA Stat.
 *
 * STATUS: STUB. PZLA udostępnia rejestrację zawodników przez panel klubu
 * (system DOMTEL). Wymaga rejestracji klubu w PZLA i potwierdzenia.
 *
 * Dokumentacja:
 *   - Portal PZLA:      https://www.pzla.pl
 *   - Statystyki/DOMTEL: https://domtel-sport.pl/lekkoatletyka/
 *   - Brak publicznego REST API — integracja przez formularze (scraping)
 *     lub eksport CSV.
 *
 * Konfiguracja:
 *   - api_username, api_password (DOMTEL klubu)
 *   - organization_id (numer klubu PZLA)
 *
 * Pola sport-specific (extras):
 *   - konkurencje[]  → lista konkurencji LA (bieg/skok/rzut/wielobój)
 *   - kategoria      → młodzik / junior / senior / weteran
 */
class PzlaAdapter implements FederationExporterInterface
{
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function federationCode(): string
    {
        return 'PZLA';
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        // TODO: POST do panelu klubu DOMTEL — formularz zgłoszenia.
        // Pola: pesel, imię, nazwisko, data ur., płeć, klub, konkurencje[],
        // kategoria wiekowa (z birth_date), data ważności badań lekarskich.
        throw new FederationException('PZLA exportMember: not implemented (stub).');
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        throw new FederationException('PZLA updateMember: not implemented (stub).');
    }

    public function fetchMemberStatus(string $externalId): array
    {
        // TODO: GET https://domtel-sport.pl/lekkoatletyka/zawodnik/{id}
        // Zwraca: licencja_ważna_do, ranking, ostatnie wyniki.
        throw new FederationException('PZLA fetchMemberStatus: not implemented (stub).');
    }

    public function testConnection(): array
    {
        if (empty($this->config['api_username']) || empty($this->config['api_password'])) {
            return ['ok' => false, 'message' => 'Brak credentiali (DOMTEL login/hasło).'];
        }
        return [
            'ok'      => true,
            'message' => 'Konfiguracja kompletna (sanity check). Pełna integracja z DOMTEL = osobny ticket.',
        ];
    }
}
