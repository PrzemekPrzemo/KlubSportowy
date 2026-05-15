<?php

namespace App\Helpers\Federations;

/**
 * Adapter PZJ (Polski Związek Judo).
 *
 * STATUS: SCRAPING publicznych stron pzj.pl + CSV fallback.
 *
 * Operacje:
 *   - testConnection()    → HEAD do pzj.pl
 *   - fetchMemberStatus() → best-effort lookup w publicznym serwisie
 *   - exportMember()      → CSV-row (rejestracja przez panel klubu = manual)
 */
class PzjAdapter implements FederationExporterInterface
{
    private const PORTAL_BASE = 'https://www.pzjudo.pl';
    private const ALT_PORTAL = 'https://pzj.pl';

    private FederationScrapingClient $http;

    public function __construct(
        private readonly array $config,
        ?FederationScrapingClient $http = null,
    ) {
        $this->http = $http ?? new FederationScrapingClient();
    }

    public function federationCode(): string
    {
        return 'PZJ';
    }

    public function adapterStatus(): string
    {
        return self::STATUS_SCRAPING;
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: '',
            message:    'PZJ nie udostępnia push API — przygotowano wiersz CSV.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: $member->externalId ?? '',
            message:    'Aktualizacja PZJ wymaga manualnego potwierdzenia — wiersz CSV przygotowany.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function fetchMemberStatus(string $externalId): array
    {
        $externalId = trim($externalId);
        if ($externalId === '') {
            return ['status' => 'invalid_input', 'message' => 'Pusty identyfikator zawodnika.'];
        }

        $html = $this->http->get(self::PORTAL_BASE . '/');
        if ($html === null) {
            return [
                'status'     => 'portal_unavailable',
                'verify_url' => self::PORTAL_BASE,
                'message'    => 'Portal PZJ niedostępny — zweryfikuj ręcznie.',
            ];
        }

        $needle = preg_quote($externalId, '/');
        if (preg_match('/\b' . $needle . '\b/u', $html)) {
            return [
                'status'      => 'mentioned',
                'external_id' => $externalId,
                'verify_url'  => self::PORTAL_BASE,
                'source'      => 'pzj_mention',
            ];
        }

        return [
            'status'      => 'unknown',
            'external_id' => $externalId,
            'verify_url'  => self::PORTAL_BASE,
            'message'     => 'Nie znaleziono identyfikatora — zweryfikuj ręcznie.',
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
                ? 'Portal PZJ dostępny. Scraping publicznych danych aktywny.'
                : "Portal PZJ niedostępny (HTTP $code).",
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
            'klub_id_pzj'     => $this->config['organization_id'] ?? '',
            'stopien'         => $m->extras['stopien'] ?? '',          // 6 kyu, 5 kyu, …, 1 dan
            'kategoria_wagowa'=> $m->extras['kategoria_wagowa'] ?? '',
            'email'           => $m->email,
            'telefon'         => $m->phone,
        ];
    }
}
