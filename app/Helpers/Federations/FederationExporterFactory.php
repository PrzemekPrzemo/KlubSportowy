<?php

namespace App\Helpers\Federations;

use App\Helpers\ClubContext;
use App\Models\ClubFederationCredentialModel;

/**
 * Factory wybierająca odpowiedni adapter exportera dla danego kodu federacji.
 *
 * Lista wspieranych (poza Generic CSV fallback):
 *   - PZPN   → PznpAdapter   (STUB — wymaga umowy partnerskiej z PZPN)
 *   - PZSS   → PzssAdapter   (SCRAPING publiczny + CSV push)
 *   - PZKosz → PzkoszAdapter (LOGIN — Probasket, login klubu wymagany)
 *   - PZLA   → PzlaAdapter   (SCRAPING domtel-sport.pl + CSV)
 *   - PZHL   → PzhlAdapter   (SCRAPING hokej.net/pzhl.org.pl + CSV)
 *   - PZPS   → PzpsAdapter   (SCRAPING plusliga.pl + CSV)
 *   - PZTS   → PztsAdapter   (LOGIN — stat.pzts.pl, cookie session w osobnym tickecie)
 *   - PZW    → PzwAdapter    (SCRAPING wrotkarstwo + CSV)
 *   - PZJ    → PzjAdapter    (SCRAPING pzj.pl + CSV)
 *   - PZTAEK   → PztaekAdapter   (SCRAPING pztaekwondo.pl + CSV) — taekwondo
 *   - PZKARATE → PzkarateAdapter (SCRAPING pzkarate.pl/pzkt.pl + CSV) — karate
 *   - PZKOL    → PzkolAdapter    (SCRAPING pzkol.pl ranking UCI + CSV) — kolarstwo
 *   - PZSZACH  → PzszachAdapter  (SCRAPING cr-pzszach.pl + ELO + CSV) — szachy
 *   - ZPRP   → ZprpAdapter   (SCRAPING zprp.pl + CSV) — piłka ręczna
 *   - PZP    → PzpAdapter    (SCRAPING polswim.pl + livetiming.pl + CSV) — pływanie
 *   - PZTEN  → PztenAdapter  (SCRAPING pzt.pl + TIE rankings + CSV) — tenis
 *   - PZB    → PzbAdapter    (LOGIN — pzb.com.pl, login klubu wymagany) — boks
 *   - inne   → GenericCsvExporter (fallback, manualny CSV)
 */
class FederationExporterFactory
{
    /**
     * Metadane federacji: nazwa + status adaptera (SCRAPING/LOGIN/STUB/CSV_ONLY).
     * Status wynika z tego co adapter REALNIE potrafi — używany przez UI do
     * honest oznaczenia (zielone/żółte/czerwone/niebieskie badge).
     */
    public const SUPPORTED = [
        'PZPN'   => ['label' => 'Polski Związek Piłki Nożnej',         'status' => FederationExporterInterface::STATUS_STUB],
        'PZSS'   => ['label' => 'Polski Związek Strzelectwa Sportowego', 'status' => FederationExporterInterface::STATUS_SCRAPING],
        'PZKosz' => ['label' => 'Polski Związek Koszykówki',           'status' => FederationExporterInterface::STATUS_LOGIN],
        'PZLA'   => ['label' => 'Polski Związek Lekkiej Atletyki',     'status' => FederationExporterInterface::STATUS_SCRAPING],
        'PZHL'   => ['label' => 'Polski Związek Hokeja na Lodzie',     'status' => FederationExporterInterface::STATUS_SCRAPING],
        'PZPS'   => ['label' => 'Polski Związek Piłki Siatkowej',      'status' => FederationExporterInterface::STATUS_SCRAPING],
        'PZTS'   => ['label' => 'Polski Związek Tenisa Stołowego',     'status' => FederationExporterInterface::STATUS_LOGIN],
        'PZW'    => ['label' => 'Polski Związek Wrotkarstwa',          'status' => FederationExporterInterface::STATUS_SCRAPING],
        'PZJ'    => ['label' => 'Polski Związek Judo',                 'status' => FederationExporterInterface::STATUS_SCRAPING],
        'PZTAEK'   => ['label' => 'Polski Związek Taekwondo Olimpijskiego', 'status' => FederationExporterInterface::STATUS_SCRAPING],
        'PZKARATE' => ['label' => 'Polski Związek Karate',                  'status' => FederationExporterInterface::STATUS_SCRAPING],
        'PZKOL'    => ['label' => 'Polski Związek Kolarski',                'status' => FederationExporterInterface::STATUS_SCRAPING],
        'PZSZACH'  => ['label' => 'Polski Związek Szachowy',                'status' => FederationExporterInterface::STATUS_SCRAPING],
        'ZPRP'   => ['label' => 'Związek Piłki Ręcznej w Polsce',      'status' => FederationExporterInterface::STATUS_SCRAPING],
        'PZP'    => ['label' => 'Polski Związek Pływacki',             'status' => FederationExporterInterface::STATUS_SCRAPING],
        'PZTEN'  => ['label' => 'Polski Związek Tenisowy',             'status' => FederationExporterInterface::STATUS_SCRAPING],
        'PZB'    => ['label' => 'Polski Związek Bokserski',            'status' => FederationExporterInterface::STATUS_LOGIN],
    ];

    /**
     * Zbuduj exporter dla podanego kodu federacji i konfiguracji.
     */
    public static function forCode(string $code, array $config = []): ?FederationExporterInterface
    {
        $normalized = self::normalizeCode($code);

        return match ($normalized) {
            'PZPN'   => new PznpAdapter($config),
            'PZSS'   => new PzssAdapter($config),
            'PZKOSZ' => new PzkoszAdapter($config),
            'PZLA'   => new PzlaAdapter($config),
            'PZHL'   => new PzhlAdapter($config),
            'PZPS'   => new PzpsAdapter($config),
            'PZTS'   => new PztsAdapter($config),
            'PZW'    => new PzwAdapter($config),
            'PZJ'    => new PzjAdapter($config),
            'PZTAEK'   => new PztaekAdapter($config),
            'PZKARATE' => new PzkarateAdapter($config),
            'PZKOL'    => new PzkolAdapter($config),
            'PZSZACH'  => new PzszachAdapter($config),
            'ZPRP'   => new ZprpAdapter($config),
            'PZP'    => new PzpAdapter($config),
            'PZTEN'  => new PztenAdapter($config),
            'PZB'    => new PzbAdapter($config),
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
            // Brak konfiguracji per-klub — fallback CSV (działa bez creds) dla
            // federacji spoza SUPPORTED. Dla SUPPORTED bez configu zwracamy null.
            if (!self::isSupported($code)) {
                return new GenericCsvExporter([], $code);
            }
            return null;
        }

        return self::forCode($code, $config);
    }

    /**
     * Lista wszystkich federacji wspieranych — UI używa tego do generowania
     * kafelków. Zwraca [code => label] (back-compat z poprzednim API).
     */
    public static function supportedCodes(): array
    {
        $out = [];
        foreach (self::SUPPORTED as $code => $meta) {
            $out[$code] = $meta['label'];
        }
        return $out;
    }

    /**
     * Pełne metadane federacji (label + status). UI używa do badge'ów statusu.
     *
     * @return array<string, array{label:string,status:string}>
     */
    public static function supportedWithMetadata(): array
    {
        return self::SUPPORTED;
    }

    /** Czy podany kod federacji ma dedykowany adapter? */
    public static function isSupported(string $code): bool
    {
        return in_array(
            self::normalizeCode($code),
            array_map(fn($k) => self::normalizeCode($k), array_keys(self::SUPPORTED)),
            true
        );
    }

    private static function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }
}
