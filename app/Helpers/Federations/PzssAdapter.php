<?php

namespace App\Helpers\Federations;

/**
 * Adapter PZSS (Polski Związek Strzelectwa Sportowego).
 *
 * STATUS: SCRAPING (publiczne dane) + CSV export fallback dla rejestracji.
 *
 * Co adapter REALNIE robi:
 *   - testConnection()    → HEAD do portalu PZSS, sprawdza dostępność.
 *   - fetchMemberStatus() → scraping publicznej strony patentów/licencji
 *                           (https://www.pzss.org.pl/patenty-licencje). Stara
 *                           się wyciągnąć dane z HTML — przy zmianie layoutu
 *                           gracefully zwraca status=unknown z linkiem do
 *                           ręcznej weryfikacji.
 *   - exportMember()      → PZSS nie udostępnia push API. Adapter generuje
 *                           wiersz CSV w formacie zgodnym z PZSS i zwraca go
 *                           jako $rawResponse['csv_row']. Klub potem wgrywa
 *                           ręcznie (lub przez GenericCsvExporter::downloadCsv
 *                           dla całej listy).
 *   - updateMember()      → identyczny mechanizm CSV.
 *
 * Konfiguracja (opcjonalna):
 *   - api_username    → login klubu w portalu PZSS
 *   - api_password    → hasło
 *   - organization_id → numer klubu PZSS (5-cyfrowy)
 *
 * Bez credentiali adapter dalej działa — tylko publiczne lookup.
 */
class PzssAdapter implements FederationExporterInterface
{
    private const PORTAL_BASE = 'https://www.pzss.org.pl';
    private const LICENSE_PORTAL_URL = self::PORTAL_BASE . '/patenty-licencje';

    private FederationScrapingClient $http;

    public function __construct(
        private readonly array $config,
        ?FederationScrapingClient $http = null,
    ) {
        $this->http = $http ?? new FederationScrapingClient();
    }

    public function federationCode(): string
    {
        return 'PZSS';
    }

    public function adapterStatus(): string
    {
        // Publiczne dane scrapujemy. Rejestracja = CSV-only (brak push API).
        return self::STATUS_SCRAPING;
    }

    /**
     * Brak realnego push API → zwracamy CSV-row jako ack, klub wgrywa ręcznie.
     */
    public function exportMember(MemberPayload $member): ExportResult
    {
        $row = $this->toPzssCsvRow($member);
        return ExportResult::success(
            externalId: '',
            message:    'PZSS nie udostępnia push API. Wiersz CSV przygotowany — pobierz CSV przez bulk export i wgraj w panelu PZSS klubu.',
            raw:        ['csv_row' => $row, 'manual_action_required' => true],
        );
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        $row = $this->toPzssCsvRow($member);
        return ExportResult::success(
            externalId: $member->externalId ?? '',
            message:    'PZSS update wymaga ręcznego potwierdzenia w panelu PZSS — wiersz CSV przygotowany.',
            raw:        ['csv_row' => $row, 'manual_action_required' => true],
        );
    }

    /**
     * Scraping publicznej strony weryfikacji licencji. externalId = numer licencji.
     * Zwraca array z polami status / verify_url / dane wyciągnięte z HTML.
     */
    public function fetchMemberStatus(string $externalId): array
    {
        $externalId = trim($externalId);
        if ($externalId === '') {
            return [
                'status'  => 'invalid_input',
                'message' => 'Pusty numer licencji.',
            ];
        }

        // 1. Strona publiczna z formularzem wyszukiwania zawodnika
        $html = $this->http->get(self::LICENSE_PORTAL_URL);
        if ($html === null) {
            return [
                'status'     => 'portal_unavailable',
                'verify_url' => self::LICENSE_PORTAL_URL,
                'message'    => 'Portal PZSS chwilowo niedostępny — zweryfikuj ręcznie.',
            ];
        }

        // 2. Best-effort extract: szukamy patternu "Licencja {nr} — {status}"
        //    Layout portalu PZSS się czasem zmienia, dlatego nie polegamy
        //    na jednej strukturze. Próbujemy dwóch strategii:
        //
        //    a) Regex po license number w treści (jeśli portal wystawia statyczną listę)
        //    b) Fallback — link weryfikujący + status=unknown
        $found = $this->extractLicenseFromHtml($html, $externalId);
        if ($found !== null) {
            return $found + [
                'license_number' => $externalId,
                'verify_url'     => self::LICENSE_PORTAL_URL,
            ];
        }

        return [
            'status'         => 'unknown',
            'license_number' => $externalId,
            'verify_url'     => self::LICENSE_PORTAL_URL,
            'message'        => 'Nie znaleziono licencji w widocznej części portalu — zweryfikuj ręcznie pod podanym URL.',
        ];
    }

    public function testConnection(): array
    {
        // 1. HEAD do strony głównej PZSS
        $ch = curl_init(self::PORTAL_BASE . '/');
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => FederationScrapingClient::USER_AGENT,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $portalOk = ($code >= 200 && $code < 400);
        $hasClubCreds = !empty($this->config['api_username']) && !empty($this->config['api_password']);

        $msg = $portalOk
            ? 'Portal PZSS dostępny. Scraping publicznych danych aktywny.'
            : "Portal PZSS niedostępny (HTTP $code).";
        if ($hasClubCreds) {
            $msg .= ' Credentiale klubu zapisane (login do system.pzss.pl wymagałby przeglądarki headless — out of scope).';
        }

        return [
            'ok'             => $portalOk,
            'message'        => $msg,
            'portal_http'    => $code,
            'has_club_creds' => $hasClubCreds,
            'mode'           => 'scraping_public',
        ];
    }

    // ---------------------------------------------------------------- private

    /**
     * Mapowanie MemberPayload → wiersz CSV w formacie używanym przez panel PZSS.
     * (Faktyczny format CSV PZSS bywa eksportowany ze systemu klubowego —
     * tutaj generujemy zestaw najczęściej wymaganych pól.)
     */
    private function toPzssCsvRow(MemberPayload $m): array
    {
        $extras = $m->extras;
        return [
            'pesel'           => $m->pesel,
            'imie'            => $m->firstName,
            'nazwisko'        => $m->lastName,
            'data_urodzenia'  => $m->birthDate,
            'plec'            => $m->gender,
            'klub_id'         => $this->config['organization_id'] ?? '',
            'patent_nr'       => $extras['license_number'] ?? $m->licenseNumber ?? '',
            'klasa'           => $extras['license_class'] ?? '',
            'konkurencje'     => isset($extras['disciplines'])
                ? (is_array($extras['disciplines']) ? implode(';', $extras['disciplines']) : (string)$extras['disciplines'])
                : '',
            'adres'           => trim(($m->addressStreet ?? '') . ', ' . ($m->addressPostal ?? '') . ' ' . ($m->addressCity ?? ''), ', '),
            'email'           => $m->email,
            'telefon'         => $m->phone,
        ];
    }

    /**
     * Próbuje znaleźć w HTML stronicy PZSS sekcję dotyczącą podanej licencji.
     * Implementacja celowo defensywna — różne wersje layoutu, nie crashuje.
     */
    private function extractLicenseFromHtml(string $html, string $licenseNumber): ?array
    {
        // Sanitize — w niektórych miejscach numer może być w formacie "12345/2024" lub "12345"
        $needle = preg_quote($licenseNumber, '/');

        // Pattern 1: tabela "Nr licencji | Status | Ważna do"
        if (preg_match(
            '/' . $needle . '\s*<\/td>\s*<td[^>]*>(?P<status>[^<]+)<\/td>\s*<td[^>]*>(?P<valid_until>[^<]+)/iu',
            $html,
            $m
        )) {
            return [
                'status'      => trim(strip_tags($m['status'])),
                'valid_until' => trim(strip_tags($m['valid_until'])),
                'source'      => 'html_table',
            ];
        }

        // Pattern 2: tekstowy fragment "Licencja {nr} jest ważna do {data}"
        if (preg_match(
            '/Licencja\s+' . $needle . '[^.]*?do\s+(?P<valid_until>\d{4}-\d{2}-\d{2}|\d{2}\.\d{2}\.\d{4})/iu',
            $html,
            $m
        )) {
            return [
                'status'      => 'active',
                'valid_until' => $m['valid_until'],
                'source'      => 'html_text',
            ];
        }

        // Pattern 3: po prostu wystąpienie numeru → "found, status unknown"
        if (preg_match('/\b' . $needle . '\b/u', $html)) {
            return [
                'status' => 'mentioned',
                'source' => 'html_mention',
                'message'=> 'Numer występuje w portalu — szczegóły wymagają ręcznej weryfikacji.',
            ];
        }

        return null;
    }
}
