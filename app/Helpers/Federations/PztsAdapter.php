<?php

namespace App\Helpers\Federations;

/**
 * Adapter PZTS (Polski Związek Tenisa Stołowego).
 *
 * STATUS: LOGIN. Portal stat.pzts.pl wymaga sesji zalogowanego klubu (cookie).
 *
 * Realne operacje:
 *   - testConnection()    → HEAD do pzts.pl, sprawdza czy credentiale są w configu
 *   - fetchMemberStatus() → wymaga loginu, na razie zwraca link weryfikacyjny
 *   - exportMember()      → CSV-row (manual upload w stat.pzts.pl)
 *
 * Konfiguracja:
 *   - api_username → login klubu w stat.pzts.pl
 *   - api_password → hasło
 *   - organization_id → numer klubu PZTS
 *
 * UWAGA: Pełny login flow (cookie session) wymaga osobnego ticketu —
 * tutaj sygnatury + honest fallback do CSV.
 */
class PztsAdapter implements FederationExporterInterface
{
    private const PORTAL_BASE = 'https://www.pzts.pl';
    private const STAT_BASE = 'https://stat.pzts.pl';

    private FederationScrapingClient $http;

    public function __construct(
        private readonly array $config,
        ?FederationScrapingClient $http = null,
    ) {
        $this->http = $http ?? new FederationScrapingClient();
    }

    public function federationCode(): string
    {
        return 'PZTS';
    }

    public function adapterStatus(): string
    {
        return self::STATUS_LOGIN;
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: '',
            message:    'PZTS export — login klubu wymagany. Wiersz CSV przygotowany do manualnego importu w stat.pzts.pl.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: $member->externalId ?? '',
            message:    'Aktualizacja w PZTS — manualne potwierdzenie wymagane.',
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
                'verify_url' => self::STAT_BASE . '/',
                'message'    => 'PZTS wymaga loginu klubu — skonfiguruj credentials lub zweryfikuj ręcznie.',
            ];
        }

        // TODO: implement cookie-based login flow against stat.pzts.pl
        // (osobny ticket — wymaga reverse-engineering formularza logowania,
        //  ekstrakcji ciasteczek sesji i autoryzowanego GET do profilu).
        return [
            'status'      => 'not_implemented',
            'external_id' => $externalId,
            'verify_url'  => self::STAT_BASE . '/',
            'message'     => 'Cookie login do stat.pzts.pl nie jest jeszcze zaimplementowany — zweryfikuj ręcznie.',
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

        $portalOk = ($code >= 200 && $code < 400);
        $hasCreds = !empty($this->config['api_username']) && !empty($this->config['api_password']);

        return [
            'ok'             => $portalOk,
            'message'        => $portalOk
                ? ($hasCreds
                    ? 'Portal PZTS dostępny. Credentiale klubu zapisane — pełny login flow w osobnym tickecie.'
                    : 'Portal PZTS dostępny. Bez credentiali — tylko link do panelu (login wymagany).')
                : "Portal PZTS niedostępny (HTTP $code).",
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
            'klub_id_pzts'    => $this->config['organization_id'] ?? '',
            'ranking'         => $m->extras['ranking'] ?? '',
            'email'           => $m->email,
            'telefon'         => $m->phone,
        ];
    }
}
