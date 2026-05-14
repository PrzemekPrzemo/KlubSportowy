<?php

namespace App\Helpers\Federations;

use App\Helpers\ClubContext;
use App\Models\ClubFederationCredentialModel;

/**
 * Factory wybierająca odpowiedni adapter exportera dla danego kodu federacji.
 *
 * Wzorowane na App\Helpers\Gateway\GatewayFactory:
 *   1. forCode($code, $config)           — eksplicytny adapter (testy / single-shot)
 *   2. forClubFederation($code, $clubId) — pobiera credentials z DB, decrypted
 *
 * Lista wspieranych:
 *   - PZPN   → PznpAdapter
 *   - PZSS   → PzssAdapter
 *   - PZKosz → PzkoszAdapter
 *   - PZLA   → PzlaAdapter
 *   - inne   → GenericCsvExporter (fallback, manualny eksport CSV)
 */
class FederationExporterFactory
{
    public const SUPPORTED = [
        'PZPN'   => 'Polski Związek Piłki Nożnej',
        'PZSS'   => 'Polski Związek Strzelectwa Sportowego',
        'PZKosz' => 'Polski Związek Koszykówki',
        'PZLA'   => 'Polski Związek Lekkiej Atletyki',
        // Inne federacje (PZPS, PZHL, PZPR, PZT, PZP, PZW, PZJ, PZKarate, ...)
        // automatycznie dostają GenericCsvExporter.
    ];

    /**
     * Zbuduj exporter dla podanego kodu federacji i konfiguracji
     * (zazwyczaj z ClubFederationCredentialModel::findByFederation).
     */
    public static function forCode(string $code, array $config = []): ?FederationExporterInterface
    {
        $normalized = self::normalizeCode($code);

        return match ($normalized) {
            'PZPN'   => new PznpAdapter($config),
            'PZSS'   => new PzssAdapter($config),
            'PZKOSZ' => new PzkoszAdapter($config),
            'PZLA'   => new PzlaAdapter($config),
            default  => new GenericCsvExporter($config, $code),
        };
    }

    /**
     * Załaduj credentials dla aktualnego klubu (lub explicit clubId) i zbuduj
     * adapter. Zwraca null gdy brak konfiguracji.
     */
    public static function forClubFederation(string $code, ?int $clubId = null): ?FederationExporterInterface
    {
        $clubId = $clubId ?? ClubContext::current();
        if ($clubId === null) {
            return null;
        }

        $model  = new ClubFederationCredentialModel();
        $config = $model->findByFederation($code);
        if (!$config) {
            // Brak konfiguracji per-klub — fallback CSV (działa bez creds).
            if (!in_array(self::normalizeCode($code), array_map(self::class . '::normalizeCode', array_keys(self::SUPPORTED)), true)) {
                return new GenericCsvExporter([], $code);
            }
            return null;
        }

        return self::forCode($code, $config);
    }

    /** Lista wszystkich federacji wspieranych (z aktywnym adapterem lub fallback CSV). */
    public static function supportedCodes(): array
    {
        return self::SUPPORTED;
    }

    private static function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }
}
