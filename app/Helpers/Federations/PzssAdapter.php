<?php

namespace App\Helpers\Federations;

/**
 * Adapter PZSS (Polski Związek Strzelectwa Sportowego).
 *
 * STATUS: STUB. PZSS używa systemu portala https://system.pzss.pl/
 * z weryfikacją patentów/licencji. Część endpointów jest publiczna (lookup
 * licencji po numerze), reszta wymaga zalogowanego klubu.
 *
 * Dokumentacja / źródła:
 *   - Portal PZSS:       https://www.pzss.org.pl
 *   - System PZSS:       https://system.pzss.pl/
 *   - Weryfikacja licencji (publiczna):
 *     https://www.pzss.org.pl/patenty-licencje/licencja-zawodnicza
 *
 * Konfiguracja:
 *   - api_username  → login klubu w portalu PZSS (jeśli klub ma konto)
 *   - api_password  → hasło
 *   - organization_id → numer klubu PZSS (5-cyfrowy)
 *
 * Lookup licencji bez credentiali jest możliwy — FederationClient::pzssCheckLicense
 * realizuje tę funkcję od dawna. Ten adapter to "next step": rejestracja
 * zawodnika w klubie i odnowienia licencji per-klub.
 */
class PzssAdapter implements FederationExporterInterface
{
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function federationCode(): string
    {
        return 'PZSS';
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        // TODO: POST do https://system.pzss.pl/... — formularz dodania zawodnika
        // Pola wymagane: pesel, imię, nazwisko, data urodzenia, klub
        // (organization_id), patent strzelecki (jeśli zawodnik posiada).
        //
        // UWAGA: PZSS aktualnie nie udostępnia formalnego REST API.
        // Realna integracja = scraping HTML lub eksport CSV z manualnym
        // importem przez klub.
        throw new FederationException('PZSS exportMember: not implemented (stub).');
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        // TODO: aktualizacja kartoteki zawodnika w systemie PZSS.
        throw new FederationException('PZSS updateMember: not implemented (stub).');
    }

    public function fetchMemberStatus(string $externalId): array
    {
        // Częściowo: re-use FederationClient::pzssCheckLicense — externalId
        // może być numerem licencji. Tutaj pełniejszy lookup (patent + klasy).
        //
        // TODO: scraping https://system.pzss.pl/profile/{license}
        throw new FederationException('PZSS fetchMemberStatus: not implemented (stub). Use FederationClient::pzssCheckLicense for license verification.');
    }

    public function testConnection(): array
    {
        // PZSS dopuszcza lookup publiczny — testujemy dostępność portalu.
        $url = 'https://www.pzss.org.pl/patenty-licencje/';
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $portalOk = ($code >= 200 && $code < 400);
        $hasClubCreds = !empty($this->config['api_username']) && !empty($this->config['api_password']);

        return [
            'ok'              => $portalOk,
            'message'         => $portalOk
                ? ($hasClubCreds
                    ? 'Portal PZSS dostępny. Credentiale klubu zapisane (faktyczny login do testów = osobny ticket).'
                    : 'Portal PZSS dostępny. Bez credentiali — tylko publiczny lookup licencji.')
                : "Portal PZSS niedostępny (HTTP $code).",
            'portal_http'     => $code,
            'has_club_creds'  => $hasClubCreds,
        ];
    }
}
