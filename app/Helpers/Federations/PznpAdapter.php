<?php

namespace App\Helpers\Federations;

/**
 * Adapter PZPN (Polski Związek Piłki Nożnej) — system Łączy Nas Piłka / Extranet.
 *
 * STATUS: STUB. Sygnatury + sketch flow + komentarze z linkami do dokumentacji.
 * Pełna implementacja wymaga konta klubu w Extranet i (w wielu przypadkach)
 * przejścia weryfikacji przez PZPN — osobny ticket.
 *
 * Dokumentacja:
 *   - Portal kibica/klubu:   https://laczynaspilka.pl
 *   - Extranet (admin):      https://extranet.pzpn.pl
 *   - REST API:              brak publicznej dokumentacji — Wielu klubów
 *                            integruje się przez eksport CSV + ręczny import,
 *                            lub przez umowę z PZPN o dostęp do API.
 *
 * Konfiguracja (z club_federation_credentials):
 *   - api_username      → login klubu w Extranet
 *   - api_password      → hasło
 *   - organization_id   → numer klubu PZPN (np. "3-2024-0012345")
 *   - is_sandbox        → tryb test (jeśli PZPN udostępni)
 *
 * Operacje:
 *   - exportMember()   = "Zgłoszenie zawodnika do rejestru piłkarzy"
 *   - updateMember()   = aktualizacja danych (zmiana klubu, transfer, edycja)
 *   - fetchMemberStatus() = lookup statusu licencji + uprawnień do gry
 */
class PznpAdapter implements FederationExporterInterface
{
    public function __construct(
        private readonly array $config, // z ClubFederationCredentialModel::findByFederation('PZPN')
    ) {
    }

    public function federationCode(): string
    {
        return 'PZPN';
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        // TODO: POST /extranet/players (lub multipart upload formularza)
        // Pola wymagane:
        //   pesel, first_name, last_name, birth_date, gender, citizenship,
        //   address, club_id (organization_id z config), position?, foot?
        // Wymaga sesji zalogowanej klubu (cookie) — login przez POST do
        // https://extranet.pzpn.pl/login z api_username/api_password.
        //
        // Zob. https://laczynaspilka.pl/strefa-kluby (instrukcje dla klubów)
        throw new FederationException('PZPN exportMember: not implemented (stub). See docs in class header.');
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        // TODO: PUT /extranet/players/{externalId}
        // externalId = player_id nadane przy rejestracji (zapisujemy w
        // członkostwie sport-specific lub w extras).
        throw new FederationException('PZPN updateMember: not implemented (stub).');
    }

    public function fetchMemberStatus(string $externalId): array
    {
        // TODO: GET /extranet/players/{externalId}
        // Zwraca array z: license_status, license_valid_until, transfer_status,
        // suspensions[], cards[], itp.
        throw new FederationException('PZPN fetchMemberStatus: not implemented (stub).');
    }

    public function testConnection(): array
    {
        // Sanity check: czy credentials wyglądają na kompletne
        if (empty($this->config['api_username']) || empty($this->config['api_password'])) {
            return ['ok' => false, 'message' => 'Brak credentiali (login/hasło Extranet).'];
        }
        if (empty($this->config['organization_id'])) {
            return ['ok' => false, 'message' => 'Brak organization_id (numeru klubu PZPN).'];
        }
        // TODO: faktyczna próba logowania do Extranet (HEAD/GET portal)
        return [
            'ok'      => true,
            'message' => 'Konfiguracja kompletna (sanity check). Pełny test wymaga implementacji loginu do Extranet.',
            'mode'    => !empty($this->config['is_sandbox']) ? 'sandbox' : 'production',
        ];
    }
}
