<?php

namespace App\Helpers\Federations;

/**
 * Adapter PZPS (Polski Związek Piłki Siatkowej).
 *
 * STATUS: SCRAPING publicznych danych (PlusLiga / PZPS portal).
 *
 * Realne operacje:
 *   - testConnection()    → HEAD do pzps.pl
 *   - fetchMemberStatus() → próba scrapingu profilu/listy zawodników po
 *                           external_id w plusliga.pl
 *   - exportMember()      → CSV-row (brak push API; rejestracja idzie przez
 *                           panel klubu PZPS)
 *
 * Konfiguracja:
 *   - organization_id (numer klubu PZPS)
 */
class PzpsAdapter implements FederationExporterInterface
{
    private const PORTAL_BASE = 'https://www.pzps.pl';
    private const PLUSLIGA_BASE = 'https://www.plusliga.pl';

    private FederationScrapingClient $http;

    public function __construct(
        private readonly array $config,
        ?FederationScrapingClient $http = null,
    ) {
        $this->http = $http ?? new FederationScrapingClient();
    }

    public function federationCode(): string
    {
        return 'PZPS';
    }

    public function adapterStatus(): string
    {
        return self::STATUS_SCRAPING;
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: '',
            message:    'PZPS nie udostępnia push API — przygotowano wiersz CSV.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: $member->externalId ?? '',
            message:    'Aktualizacja w PZPS wymaga ręcznego potwierdzenia — wiersz CSV przygotowany.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function fetchMemberStatus(string $externalId): array
    {
        $externalId = trim($externalId);
        if ($externalId === '') {
            return ['status' => 'invalid_input', 'message' => 'Pusty identyfikator zawodnika.'];
        }

        $url = self::PLUSLIGA_BASE . '/players/id/' . urlencode($externalId) . '.html';
        $html = $this->http->get($url);
        if ($html === null) {
            return [
                'status'     => 'not_found_or_unavailable',
                'verify_url' => $url,
                'message'    => 'Nie udało się pobrać profilu z PlusLiga — zweryfikuj ręcznie.',
            ];
        }

        $name = null;
        if (preg_match('/<h1[^>]*>(?P<name>[^<]+)<\/h1>/u', $html, $m)) {
            $name = trim(html_entity_decode($m['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return [
            'status'      => $name !== null ? 'found' : 'page_returned_no_name',
            'external_id' => $externalId,
            'name'        => $name,
            'verify_url'  => $url,
            'source'      => 'plusliga_scrape',
        ];
    }

    public function testConnection(): array
    {
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

        $ok = ($code >= 200 && $code < 400);
        return [
            'ok'          => $ok,
            'message'     => $ok
                ? 'Portal PZPS dostępny. Scraping PlusLiga aktywny.'
                : "Portal PZPS niedostępny (HTTP $code).",
            'portal_http' => $code,
            'mode'        => 'scraping_public',
        ];
    }

    private function toCsvRow(MemberPayload $m): array
    {
        return [
            'pesel'           => $m->pesel,
            'imie'            => $m->firstName,
            'nazwisko'        => $m->lastName,
            'data_urodzenia'  => $m->birthDate,
            'plec'            => $m->gender,
            'klub_id_pzps'    => $this->config['organization_id'] ?? '',
            'pozycja'         => $m->extras['pozycja'] ?? '',
            'wzrost'          => $m->extras['wzrost'] ?? '',
            'email'           => $m->email,
            'telefon'         => $m->phone,
        ];
    }
}
