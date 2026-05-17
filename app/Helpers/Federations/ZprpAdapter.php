<?php

namespace App\Helpers\Federations;

/**
 * Adapter ZPRP (Związek Piłki Ręcznej w Polsce).
 *
 * STATUS: SCRAPING publicznych danych (zprp.pl) + CSV fallback dla rejestracji.
 *
 * Realne operacje:
 *   - testConnection()    → HEAD do zprp.pl
 *   - fetchMemberStatus() → best-effort lookup zawodnika w publicznym portalu
 *                           (profile + ewentualnie wzmianka na stronie głównej).
 *                           Przy zmianie layoutu zwracamy status=unknown z
 *                           linkiem do ręcznej weryfikacji.
 *   - exportMember()      → CSV-row do manualnego importu w panelu klubu ZPRP
 *
 * Konfiguracja (opcjonalna):
 *   - api_username, api_password → login klubu w panelu ZPRP (cookie flow
 *     wymagałby osobnego ticketu; tutaj sygnatury + honest fallback)
 *   - organization_id → numer klubu ZPRP
 *
 * Bez credentials adapter dalej działa — tylko publiczne lookup.
 */
class ZprpAdapter implements FederationExporterInterface
{
    private const PORTAL_BASE = 'https://zprp.pl';

    private FederationScrapingClient $http;

    public function __construct(
        private readonly array $config,
        ?FederationScrapingClient $http = null,
    ) {
        $this->http = $http ?? new FederationScrapingClient();
    }

    public function federationCode(): string
    {
        return 'ZPRP';
    }

    public function adapterStatus(): string
    {
        return self::STATUS_SCRAPING;
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: '',
            message:    'ZPRP nie udostępnia push API — przygotowano wiersz CSV do ręcznego importu w panelu klubu.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: $member->externalId ?? '',
            message:    'Aktualizacja w ZPRP wymaga ręcznego potwierdzenia — wiersz CSV przygotowany.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    /**
     * Strategie lookupu (best-effort, graceful fallback):
     *   1) Bezpośredni URL profilu po external_id (jeśli portal stosuje
     *      /zawodnik/{id}).
     *   2) Wzmianka identyfikatora na stronie głównej (mentioned).
     *   3) Fallback unknown + verify_url.
     */
    public function fetchMemberStatus(string $externalId): array
    {
        $externalId = trim($externalId);
        if ($externalId === '') {
            return ['status' => 'invalid_input', 'message' => 'Pusty identyfikator zawodnika.'];
        }

        // 1. Próba bezpośredniego profilu
        $profileUrl = self::PORTAL_BASE . '/zawodnik/' . urlencode($externalId);
        $html = $this->http->get($profileUrl);
        if ($html !== null && $html !== '') {
            $name = null;
            if (preg_match('/<h1[^>]*>(?P<name>[^<]+)<\/h1>/u', $html, $m)) {
                $name = trim(html_entity_decode($m['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
            if ($name !== null && $name !== '') {
                return [
                    'status'      => 'active',
                    'external_id' => $externalId,
                    'name'        => $name,
                    'verify_url'  => $profileUrl,
                    'source'      => 'zprp_profile_scrape',
                ];
            }
        }

        // 2. Fallback — wzmianka na stronie głównej
        $home = $this->http->get(self::PORTAL_BASE . '/');
        if ($home === null) {
            return [
                'status'     => 'portal_unavailable',
                'verify_url' => self::PORTAL_BASE,
                'message'    => 'Portal ZPRP chwilowo niedostępny — zweryfikuj ręcznie.',
            ];
        }
        $needle = preg_quote($externalId, '/');
        if (preg_match('/\b' . $needle . '\b/u', $home)) {
            return [
                'status'      => 'mentioned',
                'external_id' => $externalId,
                'verify_url'  => self::PORTAL_BASE,
                'source'      => 'zprp_mention',
            ];
        }

        return [
            'status'      => 'unknown',
            'external_id' => $externalId,
            'verify_url'  => self::PORTAL_BASE,
            'message'     => 'Nie znaleziono identyfikatora w widocznej części portalu — zweryfikuj ręcznie.',
        ];
    }

    public function testConnection(): array
    {
        $ch = curl_init(self::PORTAL_BASE . '/');
        if ($ch === false) {
            return ['ok' => false, 'message' => 'curl init failed'];
        }
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => FederationScrapingClient::USER_AGENT,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $ok = ($code >= 200 && $code < 400);
        $hasClubCreds = !empty($this->config['api_username']) && !empty($this->config['api_password']);

        $msg = $ok
            ? 'Portal ZPRP dostępny. Scraping publicznych danych aktywny.'
            : "Portal ZPRP niedostępny (HTTP {$code}).";
        if ($hasClubCreds) {
            $msg .= ' Credentiale klubu zapisane (pełny login flow w osobnym tickecie).';
        }

        return [
            'ok'             => $ok,
            'message'        => $msg,
            'portal_http'    => $code,
            'has_club_creds' => $hasClubCreds,
            'mode'           => 'scraping_public',
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
            'klub_id_zprp'    => $this->config['organization_id'] ?? '',
            'pozycja'         => $m->extras['pozycja'] ?? '',
            'numer'           => $m->extras['numer'] ?? '',
            'kategoria'       => $m->extras['kategoria'] ?? '',
            'email'           => $m->email,
            'telefon'         => $m->phone,
        ];
    }
}
