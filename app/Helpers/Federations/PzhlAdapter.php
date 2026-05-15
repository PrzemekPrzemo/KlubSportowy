<?php

namespace App\Helpers\Federations;

/**
 * Adapter PZHL (Polski Związek Hokeja na Lodzie).
 *
 * STATUS: SCRAPING publicznych wyników (hokej.net / pzhl.org.pl).
 *
 * Realne operacje:
 *   - testConnection()    → HEAD do pzhl.org.pl
 *   - fetchMemberStatus() → best-effort lookup po nazwisku/external_id w
 *                           publicznych listach (zwraca link do weryfikacji
 *                           gdy nie udało się sparsować strukturalnie)
 *   - exportMember()      → CSV-row (brak push API)
 *
 * Konfiguracja:
 *   - organization_id (numer klubu PZHL) — opcjonalne
 */
class PzhlAdapter implements FederationExporterInterface
{
    private const PORTAL_BASE = 'https://www.pzhl.org.pl';
    private const PUBLIC_RESULTS = 'https://www.hokej.net';

    private FederationScrapingClient $http;

    public function __construct(
        private readonly array $config,
        ?FederationScrapingClient $http = null,
    ) {
        $this->http = $http ?? new FederationScrapingClient();
    }

    public function federationCode(): string
    {
        return 'PZHL';
    }

    public function adapterStatus(): string
    {
        return self::STATUS_SCRAPING;
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: '',
            message:    'PZHL nie udostępnia push API — przygotowano wiersz CSV.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: $member->externalId ?? '',
            message:    'Aktualizacja w PZHL wymaga ręcznego potwierdzenia — wiersz CSV przygotowany.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function fetchMemberStatus(string $externalId): array
    {
        $externalId = trim($externalId);
        if ($externalId === '') {
            return ['status' => 'invalid_input', 'message' => 'Pusty identyfikator zawodnika.'];
        }

        // PZHL publikuje rejestry w PDF + portal hokej.net — robimy best-effort
        // fetch strony głównej i sprawdzamy czy ID/nazwisko występuje.
        $html = $this->http->get(self::PUBLIC_RESULTS . '/');
        if ($html === null) {
            return [
                'status'     => 'portal_unavailable',
                'verify_url' => self::PORTAL_BASE,
                'message'    => 'Portal hokej.net niedostępny — zweryfikuj ręcznie na pzhl.org.pl.',
            ];
        }
        $needle = preg_quote($externalId, '/');
        if (preg_match('/\b' . $needle . '\b/u', $html)) {
            return [
                'status'      => 'mentioned',
                'external_id' => $externalId,
                'verify_url'  => self::PUBLIC_RESULTS,
                'source'      => 'hokej_net_mention',
            ];
        }

        return [
            'status'      => 'unknown',
            'external_id' => $externalId,
            'verify_url'  => self::PORTAL_BASE,
            'message'     => 'Nie znaleziono identyfikatora — zweryfikuj ręcznie w PZHL.',
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
                ? 'Portal PZHL dostępny. Scraping publicznych wyników (hokej.net) aktywny.'
                : "Portal PZHL niedostępny (HTTP $code).",
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
            'klub_id_pzhl'    => $this->config['organization_id'] ?? '',
            'pozycja'         => $m->extras['pozycja'] ?? '',
            'numer'           => $m->extras['numer'] ?? '',
            'email'           => $m->email,
            'telefon'         => $m->phone,
        ];
    }
}
