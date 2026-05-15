<?php

namespace App\Helpers\Federations;

use App\Helpers\CsvExporter;

/**
 * Fallback exporter: generuje CSV z listą zawodników do pobrania i ręcznego
 * importu w panelu federacji.
 *
 * Używany dla federacji, które:
 *   - nie udostępniają API,
 *   - mają API ale konfiguracja klubu jest niepełna,
 *   - klub świadomie wybiera tryb manualny (np. compliance/audit).
 *
 * Operacje exportMember/updateMember/fetchMemberStatus zwracają ExportResult
 * z .ok=true + message wskazującą że dane przygotowane, ale wymagają manualnej
 * akcji. Faktyczne pobranie CSV idzie przez downloadCsv() — wołane z
 * kontrolera (bo wymaga header() + exit, niezgodne z interfejsem).
 */
class GenericCsvExporter implements FederationExporterInterface
{
    public function __construct(
        private readonly array $config,
        private readonly string $federationCode = 'GENERIC',
    ) {
    }

    public function federationCode(): string
    {
        return $this->federationCode;
    }

    public function adapterStatus(): string
    {
        return self::STATUS_CSV_ONLY;
    }

    public function exportMember(MemberPayload $member): ExportResult
    {
        // Tryb manualny — nie wysyłamy nigdzie. Generujemy "ack" i logujemy,
        // że dane są gotowe do ręcznego importu.
        return ExportResult::success(
            externalId: '',
            message:    'Tryb manualny — dane przygotowane do eksportu CSV. Pobierz plik i zaimportuj w panelu federacji.',
            raw:        $member->toArray(),
        );
    }

    public function updateMember(MemberPayload $member): ExportResult
    {
        return ExportResult::success(
            externalId: $member->externalId ?? '',
            message:    'Tryb manualny — aktualizacja do potwierdzenia w panelu federacji.',
            raw:        $member->toArray(),
        );
    }

    public function fetchMemberStatus(string $externalId): array
    {
        return [
            'status'  => 'manual',
            'message' => 'Federacja nie udostępnia API — zweryfikuj status w panelu federacji.',
        ];
    }

    public function testConnection(): array
    {
        return [
            'ok'      => true,
            'message' => "Tryb manualny ({$this->federationCode}) — eksport przez CSV. Nie wymaga credentiali.",
        ];
    }

    /**
     * Wygeneruj i wyślij CSV z listą zawodników do przeglądarki.
     * Wywoływane przez kontroler (bulk export).
     *
     * @param MemberPayload[] $members
     * @return never
     */
    public function downloadCsv(array $members, ?string $filename = null): never
    {
        $filename = $filename ?: ('federation_' . strtolower($this->federationCode) . '_' . date('Ymd_His') . '.csv');

        $headers = [
            'member_id', 'first_name', 'last_name', 'pesel', 'birth_date',
            'gender', 'nationality', 'email', 'phone',
            'address_street', 'address_city', 'address_postal',
            'license_number', 'external_id',
        ];

        $rows = [];
        foreach ($members as $m) {
            $rows[] = [
                $m->memberId, $m->firstName, $m->lastName, $m->pesel, $m->birthDate,
                $m->gender, $m->nationality, $m->email, $m->phone,
                $m->addressStreet, $m->addressCity, $m->addressPostal,
                $m->licenseNumber, $m->externalId,
            ];
        }

        CsvExporter::download($filename, $headers, $rows);
    }
}
