<?php

namespace App\Helpers\Federations;

/**
 * Adapter PZKARATE (Polski Związek Karate).
 *
 * STATUS: SCRAPING publicznych stron pzkarate.pl + CSV fallback.
 *
 * Co adapter REALNIE robi:
 *   - testConnection()    → HEAD do pzkarate.pl, sanity check dostępności.
 *   - fetchMemberStatus() → best-effort scraping publicznej listy klubów /
 *                           zawodników. Próbuje 4 strategii (URL profilu,
 *                           lista klubów, alt. portal PZKT, strona główna).
 *                           Defensywnie — przy zmianie layoutu zwraca
 *                           status=unknown z linkiem do ręcznej weryfikacji.
 *   - exportMember()      → PZKARATE nie udostępnia push API. CSV-row gotowy
 *                           do ręcznego wgrania.
 *   - updateMember()      → identyczny CSV mechanizm.
 *
 * Konfiguracja (opcjonalna):
 *   - organization_id → numer klubu PZK (dla CSV)
 *   - api_username    → login klubu (na przyszłość)
 *   - api_password    → hasło
 *
 * Alternatywny portal: pzkt.pl (Polski Związek Karate Tradycyjnego).
 */
class PzkarateAdapter implements FederationExporterInterface
{
    private const PORTAL_BASE     = 'https://pzkarate.pl';
    private const ALT_PORTAL      = 'https://pzkt.pl';
    private const CLUBS_LIST_URL  = self::PORTAL_BASE . '/kluby';
    private const PROFILE_URL_FMT = self::PORTAL_BASE . '/zawodnicy/%s';

    private FederationScrapingClient $http;

    public function __construct(
        private readonly array $config,
        ?FederationScrapingClient $http = null,
    ) {
        $this->http = $http ?? new FederationScrapingClient();
    }

    public function federationCode(): string
    {
        return 'PZKARATE';
    }

    public function adapterStatus(): string
    {
        return self::STATUS_SCRAPING;
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: '',
            message:    'PZKARATE nie udostępnia push API — wiersz CSV gotowy do importu manualnego.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: $member->externalId ?? '',
            message:    'Aktualizacja PZKARATE wymaga manualnego potwierdzenia — wiersz CSV przygotowany.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function fetchMemberStatus(string $externalId): array
    {
        $externalId = trim($externalId);
        if ($externalId === '') {
            return ['status' => 'invalid_input', 'message' => 'Pusty identyfikator zawodnika.'];
        }

        // Strategia 1: profil bezpośrednio
        $profileUrl = sprintf(self::PROFILE_URL_FMT, rawurlencode($externalId));
        $html = $this->http->get($profileUrl);
        if ($html !== null && stripos($html, $externalId) !== false) {
            return [
                'status'      => 'found',
                'external_id' => $externalId,
                'verify_url'  => $profileUrl,
                'source'      => 'pzkarate_profile',
            ];
        }

        // Strategia 2: lista klubów / zawodników
        $html = $this->http->get(self::CLUBS_LIST_URL);
        if ($html !== null) {
            $needle = preg_quote($externalId, '/');
            if (preg_match('/\b' . $needle . '\b/u', $html)) {
                return [
                    'status'      => 'mentioned',
                    'external_id' => $externalId,
                    'verify_url'  => self::CLUBS_LIST_URL,
                    'source'      => 'pzkarate_clubs_list',
                ];
            }
        }

        // Strategia 3: alternatywny portal PZKT (Polski Związek Karate Tradycyjnego)
        $html = $this->http->get(self::ALT_PORTAL . '/');
        if ($html !== null) {
            $needle = preg_quote($externalId, '/');
            if (preg_match('/\b' . $needle . '\b/u', $html)) {
                return [
                    'status'      => 'mentioned',
                    'external_id' => $externalId,
                    'verify_url'  => self::ALT_PORTAL,
                    'source'      => 'pzkt_alt_portal',
                ];
            }
        }

        // Strategia 4: strona główna
        $html = $this->http->get(self::PORTAL_BASE . '/');
        if ($html === null) {
            return [
                'status'     => 'portal_unavailable',
                'verify_url' => self::PORTAL_BASE,
                'message'    => 'Portal PZKARATE niedostępny — zweryfikuj ręcznie.',
            ];
        }

        return [
            'status'      => 'unknown',
            'external_id' => $externalId,
            'verify_url'  => self::PORTAL_BASE,
            'message'     => 'Nie znaleziono identyfikatora — zweryfikuj ręcznie w portalu PZKARATE/PZKT.',
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
                ? 'Portal PZKARATE dostępny. Scraping publicznych danych aktywny.'
                : "Portal PZKARATE niedostępny (HTTP $code).",
            'portal_http' => $code,
            'mode'        => 'scraping_public',
        ];
    }

    private function toCsvRow(MemberPayload $m): array
    {
        $extras = $m->extras;
        return [
            'pesel'             => $m->pesel,
            'imie'              => $m->firstName,
            'nazwisko'          => $m->lastName,
            'data_urodzenia'    => $m->birthDate,
            'plec'              => $m->gender,
            'klub_id_pzkarate'  => $this->config['organization_id'] ?? '',
            'stopien'           => $extras['stopien'] ?? '',         // np. "9 kyu", "shodan"
            'styl'              => $extras['styl'] ?? '',            // shotokan / kyokushin / wkf
            'kategoria_wagowa'  => $extras['kategoria_wagowa'] ?? '',
            'konkurencja'       => $extras['konkurencja'] ?? '',     // kumite / kata
            'email'             => $m->email,
            'telefon'           => $m->phone,
        ];
    }
}
