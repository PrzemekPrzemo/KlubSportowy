<?php

namespace App\Helpers\Federations;

/**
 * Adapter PZKosz (Polski Związek Koszykówki) — system Probasket / Extranet.
 *
 * STATUS: STUB. PZKosz prowadzi licencje przez wewnętrzny system; klub
 * uzyskuje dostęp przez umowę z PZKosz.
 *
 * Dokumentacja:
 *   - Portal PZKosz: https://www.pzkosz.pl
 *   - System licencji (Probasket): https://probasket.pl
 *   - Brak publicznego REST API — integracja przez panel klubu (login/hasło).
 *
 * Konfiguracja:
 *   - api_username, api_password (Probasket)
 *   - organization_id  → numer klubu w PZKosz
 *
 * Operacje:
 *   - exportMember     = zgłoszenie zawodnika do rejestracji
 *   - updateMember     = aktualizacja danych zawodnika
 *   - fetchMemberStatus= status licencji + transferów
 */
class PzkoszAdapter implements FederationExporterInterface
{
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function federationCode(): string
    {
        return 'PZKosz';
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        // TODO: integracja z Probasket (POST do panelu klubu).
        throw new FederationException('PZKosz exportMember: not implemented (stub).');
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        throw new FederationException('PZKosz updateMember: not implemented (stub).');
    }

    public function fetchMemberStatus(string $externalId): array
    {
        throw new FederationException('PZKosz fetchMemberStatus: not implemented (stub).');
    }

    public function testConnection(): array
    {
        if (empty($this->config['api_username']) || empty($this->config['api_password'])) {
            return ['ok' => false, 'message' => 'Brak credentiali (Probasket login/hasło).'];
        }
        return [
            'ok'      => true,
            'message' => 'Konfiguracja kompletna (sanity check). Pełny login do Probasket = osobny ticket.',
        ];
    }
}
