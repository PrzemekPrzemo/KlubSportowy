<?php

namespace App\Helpers\Federations;

/**
 * Adapter PZTAEK (Polski Związek Taekwondo Olimpijskiego).
 *
 * STATUS: SCRAPING publicznych stron pztaekwondo.pl + CSV fallback.
 *
 * Co adapter REALNIE robi:
 *   - testConnection()    → HEAD do pztaekwondo.pl, sanity check.
 *   - fetchMemberStatus() → best-effort scraping publicznego portalu po
 *                           external_id (numer licencji / ID zawodnika).
 *                           Próbuje 3 strategii (URL profilu, lista klubów,
 *                           mention w treści). Przy zmianie layoutu zwraca
 *                           status=unknown z linkiem do ręcznej weryfikacji
 *                           — nigdy nie rzuca wyjątkiem.
 *   - exportMember()      → PZTAEK nie udostępnia push API. Wiersz CSV
 *                           gotowy do wgrania w panelu klubu.
 *   - updateMember()      → identyczny CSV mechanizm.
 *
 * Konfiguracja (opcjonalna):
 *   - organization_id → numer klubu PZTAEK (dla CSV)
 *   - api_username    → login klubu (gdy w przyszłości pojawi się cookie-flow)
 *   - api_password    → hasło
 */
class PztaekAdapter implements FederationExporterInterface
{
    private const PORTAL_BASE     = 'https://pztaekwondo.pl';
    private const PROFILE_URL_FMT = self::PORTAL_BASE . '/zawodnicy/%s';
    private const CLUBS_LIST_URL  = self::PORTAL_BASE . '/kluby';

    private FederationScrapingClient $http;

    public function __construct(
        private readonly array $config,
        ?FederationScrapingClient $http = null,
    ) {
        $this->http = $http ?? new FederationScrapingClient();
    }

    public function federationCode(): string
    {
        return 'PZTAEK';
    }

    public function adapterStatus(): string
    {
        return self::STATUS_SCRAPING;
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: '',
            message:    'PZTAEK nie udostępnia push API — wiersz CSV gotowy do ręcznego importu.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: $member->externalId ?? '',
            message:    'Aktualizacja PZTAEK wymaga manualnego potwierdzenia — wiersz CSV przygotowany.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function fetchMemberStatus(string $externalId): array
    {
        $externalId = trim($externalId);
        if ($externalId === '') {
            return ['status' => 'invalid_input', 'message' => 'Pusty identyfikator zawodnika.'];
        }

        // Strategia 1: bezpośredni URL profilu (jeśli portal ma /zawodnicy/{id})
        $profileUrl = sprintf(self::PROFILE_URL_FMT, rawurlencode($externalId));
        $html = $this->http->get($profileUrl);
        if ($html !== null && stripos($html, $externalId) !== false) {
            return [
                'status'      => 'found',
                'external_id' => $externalId,
                'verify_url'  => $profileUrl,
                'source'      => 'pztaek_profile',
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
                    'source'      => 'pztaek_clubs_list',
                ];
            }
        }

        // Strategia 3: strona główna
        $html = $this->http->get(self::PORTAL_BASE . '/');
        if ($html === null) {
            return [
                'status'     => 'portal_unavailable',
                'verify_url' => self::PORTAL_BASE,
                'message'    => 'Portal PZTAEK niedostępny — zweryfikuj ręcznie.',
            ];
        }

        return [
            'status'      => 'unknown',
            'external_id' => $externalId,
            'verify_url'  => self::PORTAL_BASE,
            'message'     => 'Nie znaleziono identyfikatora — zweryfikuj ręcznie w portalu.',
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
                ? 'Portal PZTAEK dostępny. Scraping publicznych danych aktywny.'
                : "Portal PZTAEK niedostępny (HTTP $code).",
            'portal_http' => $code,
            'mode'        => 'scraping_public',
        ];
    }

    private function toCsvRow(MemberPayload $m): array
    {
        $extras = $m->extras;
        return [
            'pesel'           => $m->pesel,
            'imie'            => $m->firstName,
            'nazwisko'        => $m->lastName,
            'data_urodzenia'  => $m->birthDate,
            'plec'            => $m->gender,
            'klub_id_pztaek'  => $this->config['organization_id'] ?? '',
            'stopien'         => $extras['stopien'] ?? '',         // np. "8 kup", "1 dan"
            'kategoria_wagowa'=> $extras['kategoria_wagowa'] ?? '',
            'konkurencja'     => $extras['konkurencja'] ?? '',     // kyorugi / poomsae
            'email'           => $m->email,
            'telefon'         => $m->phone,
        ];
    }
}
