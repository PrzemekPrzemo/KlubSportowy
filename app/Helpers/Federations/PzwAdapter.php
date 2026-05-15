<?php

namespace App\Helpers\Federations;

/**
 * Adapter PZW (Polski Związek Wrotkarstwa) — pzw.eu.
 *
 * STATUS: SCRAPING publicznych stron + CSV fallback.
 *
 * UWAGA: Skrót "PZW" jest niejednoznaczny w polskim sporcie — adapter
 * obsługuje WROTKARSTWO (https://pzw.eu / Polski Związek Wrotkarski). Dla
 * Polskiego Związku Wędkarskiego użyj GenericCsvExporter pod innym kodem.
 *
 * Operacje:
 *   - testConnection()    → HEAD do pzw.eu
 *   - fetchMemberStatus() → best-effort lookup w newsach / wynikach
 *   - exportMember()      → CSV-row
 */
class PzwAdapter implements FederationExporterInterface
{
    private const PORTAL_BASE = 'https://pzw.eu';

    private FederationScrapingClient $http;

    public function __construct(
        private readonly array $config,
        ?FederationScrapingClient $http = null,
    ) {
        $this->http = $http ?? new FederationScrapingClient();
    }

    public function federationCode(): string
    {
        return 'PZW';
    }

    public function adapterStatus(): string
    {
        return self::STATUS_SCRAPING;
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: '',
            message:    'PZW nie udostępnia push API — przygotowano wiersz CSV.',
            raw:        ['csv_row' => $this->toCsvRow($member), 'manual_action_required' => true],
        );
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: $member->externalId ?? '',
            message:    'Aktualizacja PZW wymaga manualnego potwierdzenia — wiersz CSV przygotowany.',
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
                'message'    => 'Portal PZW niedostępny — zweryfikuj ręcznie.',
            ];
        }
        $needle = preg_quote($externalId, '/');
        if (preg_match('/\b' . $needle . '\b/u', $html)) {
            return [
                'status'      => 'mentioned',
                'external_id' => $externalId,
                'verify_url'  => self::PORTAL_BASE,
                'source'      => 'pzw_mention',
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
                ? 'Portal PZW (wrotkarstwo) dostępny. Scraping publicznych danych aktywny.'
                : "Portal PZW niedostępny (HTTP $code).",
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
            'klub_id_pzw'     => $this->config['organization_id'] ?? '',
            'konkurencja'     => $m->extras['konkurencja'] ?? '',
            'email'           => $m->email,
            'telefon'         => $m->phone,
        ];
    }
}
