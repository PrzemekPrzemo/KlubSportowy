<?php

namespace App\Helpers\Federations;

/**
 * Adapter PZB (Polski Związek Bokserski).
 *
 * STATUS: LOGIN. Lista licencjonowanych zawodników w PZB nie jest publiczna
 * — wymaga loginu klubu w panelu pzb.com.pl. Implementacja idzie wzorem
 * PzkoszAdapter/PztsAdapter: HEAD + sanity-check credentiali, honest fallback
 * "login_required" / "not_implemented" przy fetchMemberStatus.
 *
 * Realne operacje:
 *   - testConnection()    → HEAD do pzb.com.pl + sanity check credentials
 *   - fetchMemberStatus() → bez loginu zwraca login_required; z loginem
 *                           not_implemented (cookie flow w osobnym tickecie).
 *   - exportMember()      → CSV-row do manualnego importu w panelu klubu
 *
 * Konfiguracja:
 *   - api_username, api_password → login klubu w panelu PZB
 *   - organization_id → numer klubu PZB
 */
class PzbAdapter implements FederationExporterInterface
{
    private const PORTAL_BASE = 'https://pzb.com.pl';

    private FederationScrapingClient $http;

    public function __construct(
        private readonly array $config,
        ?FederationScrapingClient $http = null,
    ) {
        $this->http = $http ?? new FederationScrapingClient();
    }

    public function federationCode(): string
    {
        return 'PZB';
    }

    public function adapterStatus(): string
    {
        return self::STATUS_LOGIN;
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: '',
            message:    'PZB export — login klubu wymagany. Wiersz CSV przygotowany do manualnego importu w panelu PZB.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: $member->externalId ?? '',
            message:    'Aktualizacja w PZB — manualne potwierdzenie wymagane.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function fetchMemberStatus(string $externalId): array
    {
        $externalId = trim($externalId);
        if ($externalId === '') {
            return ['status' => 'invalid_input', 'message' => 'Pusty identyfikator zawodnika.'];
        }

        $hasCreds = !empty($this->config['api_username']) && !empty($this->config['api_password']);
        if (!$hasCreds) {
            return [
                'status'     => 'login_required',
                'verify_url' => self::PORTAL_BASE . '/',
                'message'    => 'PZB wymaga loginu klubu — skonfiguruj credentials lub zweryfikuj ręcznie.',
            ];
        }

        // TODO: cookie login flow do panelu pzb.com.pl (osobny ticket — wymaga
        // reverse-engineering formularza logowania i autoryzowanego GET do
        // listy licencjonowanych zawodników klubu).
        return [
            'status'      => 'not_implemented',
            'external_id' => $externalId,
            'verify_url'  => self::PORTAL_BASE . '/',
            'message'     => 'Cookie login do pzb.com.pl nie jest jeszcze zaimplementowany — zweryfikuj ręcznie.',
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

        $portalOk = ($code >= 200 && $code < 400);
        $hasCreds = !empty($this->config['api_username']) && !empty($this->config['api_password']);

        return [
            'ok'             => $portalOk,
            'message'        => $portalOk
                ? ($hasCreds
                    ? 'Portal PZB dostępny. Credentiale klubu zapisane — pełny login flow w osobnym tickecie.'
                    : 'Portal PZB dostępny. Bez credentiali — tylko link do panelu (login wymagany).')
                : "Portal PZB niedostępny (HTTP {$code}).",
            'portal_http'    => $code,
            'has_club_creds' => $hasCreds,
            'mode'           => $hasCreds ? 'login_configured' : 'login_required',
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
            'klub_id_pzb'     => $this->config['organization_id'] ?? '',
            'kategoria_wagowa'=> $m->extras['kategoria_wagowa'] ?? '',
            'klasa'           => $m->extras['klasa'] ?? '',
            'badania_do'      => $m->extras['badania_do'] ?? '',
            'email'           => $m->email,
            'telefon'         => $m->phone,
        ];
    }
}
