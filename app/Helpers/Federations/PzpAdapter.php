<?php

namespace App\Helpers\Federations;

/**
 * Adapter PZP (Polski Związek Pływacki).
 *
 * STATUS: SCRAPING publicznych danych (polswim.pl + livetiming.pl)
 * + CSV fallback dla rejestracji.
 *
 * Pływanie ma rich data — livetiming.pl publikuje wyniki czasowe zawodów.
 *
 * Realne operacje:
 *   - testConnection()    → HEAD do polswim.pl
 *   - fetchMemberStatus() → próba profilu zawodnika + best-effort mention
 *                           w wynikach livetiming.pl. Graceful fallback.
 *   - exportMember()      → CSV-row (PZP nie udostępnia push API)
 *
 * Konfiguracja (opcjonalna):
 *   - api_username, api_password → login klubu (Megazone/Splash) — fallback
 *   - organization_id → numer klubu PZP
 */
class PzpAdapter implements FederationExporterInterface
{
    private const PORTAL_BASE = 'https://www.polswim.pl';
    private const RESULTS_BASE = 'https://livetiming.pl';

    private FederationScrapingClient $http;

    public function __construct(
        private readonly array $config,
        ?FederationScrapingClient $http = null,
    ) {
        $this->http = $http ?? new FederationScrapingClient();
    }

    public function federationCode(): string
    {
        return 'PZP';
    }

    public function adapterStatus(): string
    {
        return self::STATUS_SCRAPING;
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: '',
            message:    'PZP nie udostępnia push API — przygotowano wiersz CSV do ręcznego importu w panelu klubu.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: $member->externalId ?? '',
            message:    'Aktualizacja w PZP wymaga ręcznego potwierdzenia — wiersz CSV przygotowany.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    /**
     * Strategie lookupu:
     *   1) Profil zawodnika w polswim.pl (jeśli portal serwuje /zawodnik/{id}).
     *   2) Wzmianka identyfikatora w wynikach livetiming.pl.
     *   3) Fallback unknown + verify_url.
     */
    public function fetchMemberStatus(string $externalId): array
    {
        $externalId = trim($externalId);
        if ($externalId === '') {
            return ['status' => 'invalid_input', 'message' => 'Pusty identyfikator zawodnika.'];
        }

        // 1. Profil zawodnika
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
                    'source'      => 'polswim_profile_scrape',
                ];
            }
        }

        // 2. Wyniki livetiming.pl — best-effort mention
        $results = $this->http->get(self::RESULTS_BASE . '/');
        if ($results !== null) {
            $needle = preg_quote($externalId, '/');
            if (preg_match('/\b' . $needle . '\b/u', $results)) {
                return [
                    'status'      => 'mentioned',
                    'external_id' => $externalId,
                    'verify_url'  => self::RESULTS_BASE,
                    'source'      => 'livetiming_mention',
                    'message'     => 'Identyfikator widoczny w wynikach livetiming.pl — sprawdź szczegóły zawodów.',
                ];
            }
        } else if ($html === null) {
            return [
                'status'     => 'portal_unavailable',
                'verify_url' => self::PORTAL_BASE,
                'message'    => 'Portale PZP/livetiming chwilowo niedostępne — zweryfikuj ręcznie.',
            ];
        }

        return [
            'status'      => 'unknown',
            'external_id' => $externalId,
            'verify_url'  => self::PORTAL_BASE,
            'message'     => 'Nie znaleziono identyfikatora w widocznej części portali — zweryfikuj ręcznie.',
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
            ? 'Portal PZP (polswim.pl) dostępny. Scraping publicznych danych + wyników livetiming aktywny.'
            : "Portal PZP niedostępny (HTTP {$code}).";
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
            'klub_id_pzp'     => $this->config['organization_id'] ?? '',
            'kategoria'       => $m->extras['kategoria'] ?? '',
            'konkurencje'     => isset($m->extras['konkurencje'])
                ? (is_array($m->extras['konkurencje']) ? implode(';', $m->extras['konkurencje']) : (string)$m->extras['konkurencje'])
                : '',
            'badania_do'      => $m->extras['badania_do'] ?? '',
            'rekord_zyciowy'  => $m->extras['rekord_zyciowy'] ?? '',
            'email'           => $m->email,
            'telefon'         => $m->phone,
        ];
    }
}
