<?php

namespace App\Helpers\Federations;

/**
 * Adapter PZSZACH (Polski Związek Szachowy).
 *
 * STATUS: SCRAPING Centralnego Rejestru Zawodników (cr-pzszach.pl) + CSV fallback.
 *
 * Co adapter REALNIE robi:
 *   - testConnection()    → HEAD do cr-pzszach.pl (Centralny Rejestr Zawodników
 *                           — best source dla statusów + ELO).
 *   - fetchMemberStatus() → scraping CR-PZSZACH po ID zawodnika. Próbuje
 *                           wyciągnąć ELO (FIDE / PZSzach), kategorię i klub.
 *                           Przy zmianie layoutu zwraca status=unknown z linkiem
 *                           do ręcznej weryfikacji. Nigdy nie rzuca wyjątkiem.
 *   - exportMember()      → PZSZACH nie udostępnia push API. Wiersz CSV.
 *   - updateMember()      → identyczny CSV mechanizm.
 *
 * Konfiguracja (opcjonalna):
 *   - organization_id → numer klubu PZSzach
 *   - api_username    → login klubu (na przyszłość)
 *   - api_password    → hasło
 */
class PzszachAdapter implements FederationExporterInterface
{
    private const PORTAL_BASE        = 'https://www.pzszach.pl';
    private const CR_PORTAL_BASE     = 'https://www.cr-pzszach.pl';
    private const CR_PROFILE_URL_FMT = self::CR_PORTAL_BASE . '/zawodnik/%s';

    private FederationScrapingClient $http;

    public function __construct(
        private readonly array $config,
        ?FederationScrapingClient $http = null,
    ) {
        $this->http = $http ?? new FederationScrapingClient();
    }

    public function federationCode(): string
    {
        return 'PZSZACH';
    }

    public function adapterStatus(): string
    {
        return self::STATUS_SCRAPING;
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: '',
            message:    'PZSZACH nie udostępnia push API — wiersz CSV gotowy do importu w CR-PZSzach.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: $member->externalId ?? '',
            message:    'Aktualizacja PZSZACH wymaga manualnego potwierdzenia w CR-PZSzach — wiersz CSV przygotowany.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function fetchMemberStatus(string $externalId): array
    {
        $externalId = trim($externalId);
        if ($externalId === '') {
            return ['status' => 'invalid_input', 'message' => 'Pusty identyfikator zawodnika.'];
        }

        // Strategia 1: profil w Centralnym Rejestrze Zawodników (best source)
        $profileUrl = sprintf(self::CR_PROFILE_URL_FMT, rawurlencode($externalId));
        $html = $this->http->get($profileUrl);
        if ($html !== null && stripos($html, $externalId) !== false) {
            $elo = $this->extractEloFromHtml($html);
            $category = $this->extractCategoryFromHtml($html);
            return array_filter([
                'status'      => 'found',
                'external_id' => $externalId,
                'verify_url'  => $profileUrl,
                'source'      => 'cr_pzszach_profile',
                'elo_fide'    => $elo['fide'] ?? null,
                'elo_pzszach' => $elo['pzszach'] ?? null,
                'kategoria'   => $category,
            ], fn($v) => $v !== null);
        }

        // Strategia 2: strona główna CR (czy ID się gdzieś pojawia)
        $html = $this->http->get(self::CR_PORTAL_BASE . '/');
        if ($html !== null) {
            $needle = preg_quote($externalId, '/');
            if (preg_match('/\b' . $needle . '\b/u', $html)) {
                return [
                    'status'      => 'mentioned',
                    'external_id' => $externalId,
                    'verify_url'  => self::CR_PORTAL_BASE,
                    'source'      => 'cr_pzszach_mention',
                ];
            }
        }

        // Strategia 3: główny portal pzszach.pl
        $html = $this->http->get(self::PORTAL_BASE . '/');
        if ($html === null) {
            return [
                'status'     => 'portal_unavailable',
                'verify_url' => self::CR_PORTAL_BASE,
                'message'    => 'Portal CR-PZSZACH niedostępny — zweryfikuj ręcznie.',
            ];
        }

        return [
            'status'      => 'unknown',
            'external_id' => $externalId,
            'verify_url'  => self::CR_PORTAL_BASE,
            'message'     => 'Nie znaleziono ID w Centralnym Rejestrze Zawodników — zweryfikuj ręcznie.',
        ];
    }

    public function testConnection(): array
    {
        $ch = curl_init(self::CR_PORTAL_BASE . '/');
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
                ? 'Portal CR-PZSZACH dostępny. Scraping profili zawodników + ELO aktywny.'
                : "Portal CR-PZSZACH niedostępny (HTTP $code).",
            'portal_http' => $code,
            'mode'        => 'scraping_public',
        ];
    }

    /**
     * Wyciągnij ranking ELO z profilu CR-PZSzach. Próbuje znaleźć FIDE / PZSzach
     * — różne layouty, defensywne wzorce.
     *
     * @return array{fide?: string, pzszach?: string}
     */
    private function extractEloFromHtml(string $html): array
    {
        $out = [];
        // FIDE ELO — np. "FIDE: 2150", "ELO FIDE 2150"
        if (preg_match('/FIDE[^0-9<]{0,20}(\d{3,4})\b/iu', $html, $m)) {
            $out['fide'] = $m[1];
        }
        // PZSzach ELO — np. "ELO PZSzach: 1950" / "PZSz: 1950"
        if (preg_match('/PZ[Ss]z(?:ach)?[^0-9<]{0,20}(\d{3,4})\b/u', $html, $m)) {
            $out['pzszach'] = $m[1];
        }
        return $out;
    }

    /**
     * Wyciągnij kategorię szachową z profilu CR-PZSzach.
     * Kategorie PZSzach: V → I, kandydat na mistrza (kkm), mistrz krajowy (mk),
     * mistrz międzynarodowy (im, gm).
     */
    private function extractCategoryFromHtml(string $html): ?string
    {
        if (preg_match('/Kategoria[^<:]*[:>]\s*([IVXkmgKMG][a-zA-Z]{0,10})/u', $html, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/\b(GM|IM|FM|CM|WGM|WIM|WFM|WCM|kkm|mk)\b/iu', $html, $m)) {
            return $m[1];
        }
        return null;
    }

    private function toCsvRow(MemberPayload $m): array
    {
        $extras = $m->extras;
        return [
            'pesel'              => $m->pesel,
            'imie'               => $m->firstName,
            'nazwisko'           => $m->lastName,
            'data_urodzenia'     => $m->birthDate,
            'plec'               => $m->gender,
            'klub_id_pzszach'    => $this->config['organization_id'] ?? '',
            'id_centralny'       => $m->externalId ?? '',
            'kategoria_szachowa' => $extras['kategoria_szachowa'] ?? '',  // V, IV, ..., I, kkm, mk, im, gm
            'elo_fide'           => $extras['elo_fide'] ?? '',
            'elo_pzszach'        => $extras['elo_pzszach'] ?? '',
            'id_fide'            => $extras['id_fide'] ?? '',
            'email'              => $m->email,
            'telefon'            => $m->phone,
        ];
    }
}
