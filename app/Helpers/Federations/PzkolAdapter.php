<?php

namespace App\Helpers\Federations;

/**
 * Adapter PZKOL (Polski Związek Kolarski).
 *
 * STATUS: SCRAPING publicznych stron pzkol.pl + CSV fallback.
 *
 * Co adapter REALNIE robi:
 *   - testConnection()    → HEAD do www.pzkol.pl, sanity check.
 *   - fetchMemberStatus() → best-effort scraping rankingu UCI i profili
 *                           zawodnika z publicznego portalu. Próbuje 3
 *                           strategii. Przy zmianie layoutu portalu zwraca
 *                           status=unknown z linkiem do ręcznej weryfikacji.
 *   - exportMember()      → PZKOL nie udostępnia push API. Wiersz CSV
 *                           z numerem licencji UCI / kategorią do importu.
 *   - updateMember()      → identyczny CSV mechanizm.
 *
 * Konfiguracja (opcjonalna):
 *   - organization_id → numer klubu PZKol
 *   - api_username    → login klubu (na przyszłość)
 *   - api_password    → hasło
 */
class PzkolAdapter implements FederationExporterInterface
{
    private const PORTAL_BASE     = 'https://www.pzkol.pl';
    private const PROFILE_URL_FMT = self::PORTAL_BASE . '/zawodnicy/%s';
    private const RANKING_URL     = self::PORTAL_BASE . '/ranking';

    private FederationScrapingClient $http;

    public function __construct(
        private readonly array $config,
        ?FederationScrapingClient $http = null,
    ) {
        $this->http = $http ?? new FederationScrapingClient();
    }

    public function federationCode(): string
    {
        return 'PZKOL';
    }

    public function adapterStatus(): string
    {
        return self::STATUS_SCRAPING;
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: '',
            message:    'PZKOL nie udostępnia push API — wiersz CSV gotowy do importu manualnego.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: $member->externalId ?? '',
            message:    'Aktualizacja PZKOL wymaga manualnego potwierdzenia — wiersz CSV przygotowany.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function fetchMemberStatus(string $externalId): array
    {
        $externalId = trim($externalId);
        if ($externalId === '') {
            return ['status' => 'invalid_input', 'message' => 'Pusty identyfikator zawodnika.'];
        }

        // Strategia 1: profil zawodnika
        $profileUrl = sprintf(self::PROFILE_URL_FMT, rawurlencode($externalId));
        $html = $this->http->get($profileUrl);
        if ($html !== null && stripos($html, $externalId) !== false) {
            $rank = $this->extractRankFromHtml($html);
            return array_filter([
                'status'      => 'found',
                'external_id' => $externalId,
                'verify_url'  => $profileUrl,
                'source'      => 'pzkol_profile',
                'uci_rank'    => $rank,
            ]);
        }

        // Strategia 2: ranking UCI
        $html = $this->http->get(self::RANKING_URL);
        if ($html !== null) {
            $needle = preg_quote($externalId, '/');
            if (preg_match('/\b' . $needle . '\b/u', $html)) {
                return [
                    'status'      => 'mentioned',
                    'external_id' => $externalId,
                    'verify_url'  => self::RANKING_URL,
                    'source'      => 'pzkol_uci_ranking',
                ];
            }
        }

        // Strategia 3: strona główna
        $html = $this->http->get(self::PORTAL_BASE . '/');
        if ($html === null) {
            return [
                'status'     => 'portal_unavailable',
                'verify_url' => self::PORTAL_BASE,
                'message'    => 'Portal PZKOL niedostępny — zweryfikuj ręcznie.',
            ];
        }

        return [
            'status'      => 'unknown',
            'external_id' => $externalId,
            'verify_url'  => self::PORTAL_BASE,
            'message'     => 'Nie znaleziono identyfikatora — zweryfikuj ręcznie w portalu PZKol.',
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
                ? 'Portal PZKOL dostępny. Scraping rankingu UCI i profili zawodników aktywny.'
                : "Portal PZKOL niedostępny (HTTP $code).",
            'portal_http' => $code,
            'mode'        => 'scraping_public',
        ];
    }

    /**
     * Próbuje wyciągnąć pozycję w rankingu UCI z HTML profilu — defensywnie.
     */
    private function extractRankFromHtml(string $html): ?string
    {
        if (preg_match('/UCI[^<]*?(?:miejsce|pozycja|rank)[^<]*?(\d{1,5})/iu', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/<(?:span|td|div)[^>]*class="[^"]*(?:uci|ranking)[^"]*"[^>]*>(?P<rank>\d{1,5})</iu', $html, $m)) {
            return $m['rank'];
        }
        return null;
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
            'klub_id_pzkol'   => $this->config['organization_id'] ?? '',
            'licencja_uci'    => $extras['licencja_uci'] ?? $m->licenseNumber ?? '',
            'kategoria'       => $extras['kategoria'] ?? '',          // elita / U23 / junior / młodzik
            'specjalnosc'     => $extras['specjalnosc'] ?? '',        // szosa / MTB / torowe / przełaj
            'email'           => $m->email,
            'telefon'         => $m->phone,
        ];
    }
}
