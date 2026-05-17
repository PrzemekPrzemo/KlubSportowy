<?php

namespace App\Helpers\Federations;

/**
 * Adapter PZTen (Polski Związek Tenisowy).
 *
 * STATUS: SCRAPING publicznych rankingów (pzt.pl + pzt.tenis.pl/TIE)
 * + CSV fallback dla rejestracji.
 *
 * Realne operacje:
 *   - testConnection()    → HEAD do pzt.pl
 *   - fetchMemberStatus() → próba profilu w rankingu TIE; fallback do
 *                           wzmianki na portalu PZT.
 *   - exportMember()      → CSV-row (PZT nie udostępnia push API)
 *
 * Konfiguracja (opcjonalna):
 *   - api_username, api_password → login klubu (TIE) — out of scope
 *   - organization_id → numer klubu PZT
 */
class PztenAdapter implements FederationExporterInterface
{
    private const PORTAL_BASE = 'https://www.pzt.pl';
    private const TIE_BASE = 'https://pzt.tenis.pl';

    private FederationScrapingClient $http;

    public function __construct(
        private readonly array $config,
        ?FederationScrapingClient $http = null,
    ) {
        $this->http = $http ?? new FederationScrapingClient();
    }

    public function federationCode(): string
    {
        return 'PZTEN';
    }

    public function adapterStatus(): string
    {
        return self::STATUS_SCRAPING;
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: '',
            message:    'PZT nie udostępnia push API — przygotowano wiersz CSV do ręcznego importu w panelu klubu.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: $member->externalId ?? '',
            message:    'Aktualizacja w PZT wymaga ręcznego potwierdzenia — wiersz CSV przygotowany.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    /**
     * Strategie lookupu:
     *   1) Profil zawodnika w rankingu TIE (pzt.tenis.pl/zawodnik/{id}).
     *   2) Wzmianka identyfikatora na portalu PZT.
     *   3) Fallback unknown + verify_url.
     */
    public function fetchMemberStatus(string $externalId): array
    {
        $externalId = trim($externalId);
        if ($externalId === '') {
            return ['status' => 'invalid_input', 'message' => 'Pusty identyfikator zawodnika.'];
        }

        // 1. TIE — profil zawodnika
        $profileUrl = self::TIE_BASE . '/zawodnik/' . urlencode($externalId);
        $html = $this->http->get($profileUrl);
        if ($html !== null && $html !== '') {
            $name = null;
            if (preg_match('/<h1[^>]*>(?P<name>[^<]+)<\/h1>/u', $html, $m)) {
                $name = trim(html_entity_decode($m['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
            $ranking = null;
            if (preg_match('/ranking[^\d]{0,12}(?P<r>\d{1,5})/iu', $html, $m)) {
                $ranking = $m['r'];
            }
            if ($name !== null && $name !== '') {
                return [
                    'status'      => 'active',
                    'external_id' => $externalId,
                    'name'        => $name,
                    'ranking'     => $ranking,
                    'verify_url'  => $profileUrl,
                    'source'      => 'tie_profile_scrape',
                ];
            }
        }

        // 2. PZT.pl — fallback mention
        $pzt = $this->http->get(self::PORTAL_BASE . '/');
        if ($pzt === null && $html === null) {
            return [
                'status'     => 'portal_unavailable',
                'verify_url' => self::PORTAL_BASE,
                'message'    => 'Portale PZT/TIE chwilowo niedostępne — zweryfikuj ręcznie.',
            ];
        }
        if ($pzt !== null) {
            $needle = preg_quote($externalId, '/');
            if (preg_match('/\b' . $needle . '\b/u', $pzt)) {
                return [
                    'status'      => 'mentioned',
                    'external_id' => $externalId,
                    'verify_url'  => self::PORTAL_BASE,
                    'source'      => 'pzt_mention',
                ];
            }
        }

        return [
            'status'      => 'unknown',
            'external_id' => $externalId,
            'verify_url'  => self::TIE_BASE,
            'message'     => 'Nie znaleziono identyfikatora w rankingu TIE — zweryfikuj ręcznie.',
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
            ? 'Portal PZT dostępny. Scraping publicznego rankingu TIE aktywny.'
            : "Portal PZT niedostępny (HTTP {$code}).";
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
            'klub_id_pzt'     => $this->config['organization_id'] ?? '',
            'kategoria'       => $m->extras['kategoria'] ?? '',
            'ranking'         => $m->extras['ranking'] ?? '',
            'reka'            => $m->extras['reka'] ?? '', // prawa/lewa
            'email'           => $m->email,
            'telefon'         => $m->phone,
        ];
    }
}
