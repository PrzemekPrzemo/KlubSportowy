<?php

namespace App\Helpers;

/**
 * Loader modułów sportowych.
 *
 * Skanuje katalog app/Sports/ w poszukiwaniu manifestów (manifest.php),
 * udostępnia listę modułów (dla routingu i nawigacji) oraz mapowanie
 * sport_key → manifest.
 *
 * Każdy manifest zwraca tablicę:
 *   [
 *     'key'        => 'shooting',              // zgodny z sports.key
 *     'name'       => 'Strzelectwo',
 *     'federation' => 'PZSS',
 *     'features'   => ['weapons','ammo',...],
 *     'routes'     => [ ['GET','/weapons', [Class, 'method']], ... ],
 *     'nav'        => [ ['label'=>'Broń','icon'=>'...','url'=>'weapons'], ... ],
 *   ]
 */
class SportModuleLoader
{
    private static ?array $modules = null;

    /** Załaduj wszystkie manifesty sportów z katalogu app/Sports */
    public static function load(): array
    {
        if (self::$modules !== null) {
            return self::$modules;
        }

        $modules = [];
        $base    = ROOT_PATH . '/app/Sports';
        if (!is_dir($base)) {
            return self::$modules = [];
        }

        foreach (glob($base . '/*/manifest.php') as $manifestPath) {
            $manifest = require $manifestPath;
            if (!is_array($manifest) || empty($manifest['key'])) {
                continue;
            }
            $modules[$manifest['key']] = $manifest;
        }

        return self::$modules = $modules;
    }

    /** Zwraca manifest dla danego sport.key lub null. */
    public static function get(string $sportKey): ?array
    {
        $all = self::load();
        return $all[$sportKey] ?? null;
    }

    /** Zarejestruj wszystkie trasy z manifestów w routerze. */
    public static function registerRoutes(Router $router): void
    {
        foreach (self::load() as $module) {
            foreach (($module['routes'] ?? []) as $route) {
                [$method, $path, $handler] = $route;
                $router->addRoute($method, $path, $handler);
            }
        }
    }

    /**
     * Zwraca listę pozycji nawigacyjnych dla aktualnie aktywnej sekcji sportowej.
     *
     * Łączy:
     *   1) Statyczny `nav` z manifest.php sportu (dedykowane controllery)
     *   2) Dynamiczne wpisy z tabeli `sport_module_resources` (generic CRUD)
     *      pod URL-em `sport/<key>/<resource>` — generic SportModuleController.
     *
     * Jeśli SportContext nie jest ustawiony — zwraca pustą tablicę.
     */
    public static function navForActiveSport(): array
    {
        $key = SportContext::currentSportKey();
        if ($key === null) return [];
        $module = self::get($key);
        $manifestNav = $module['nav'] ?? [];

        // Mapa już-zarejestrowanych URL-i (z manifestu) aby unikac duplikatow
        $existingUrls = [];
        foreach ($manifestNav as $item) {
            if (!empty($item['url'])) $existingUrls[trim((string)$item['url'], '/')] = true;
        }

        // Doloz generic resources (jesli tabela istnieje)
        $generic = [];
        try {
            $pdo = Database::pdo();
            $check = $pdo->query("SHOW TABLES LIKE 'sport_module_resources'");
            if ($check && $check->fetchColumn()) {
                $stmt = $pdo->prepare(
                    'SELECT resource_key, resource_label, icon
                     FROM sport_module_resources
                     WHERE sport_key = ? AND is_active = 1
                     ORDER BY sort_order ASC, resource_label ASC'
                );
                $stmt->execute([$key]);
                foreach ($stmt->fetchAll() as $r) {
                    $url = 'sport/' . $key . '/' . (string)$r['resource_key'];
                    if (isset($existingUrls[$url])) continue;
                    $generic[] = [
                        'label' => (string)$r['resource_label'],
                        'icon'  => (string)($r['icon'] ?: 'bi-table'),
                        'url'   => $url,
                    ];
                }
            }
        } catch (\Throwable) {
            // DB nie dostępna / migracja 073 nie uruchomiona — zwracamy tylko manifest
        }

        return array_merge($manifestNav, $generic);
    }

    /** Reset cache — używane w testach. */
    public static function reset(): void
    {
        self::$modules = null;
    }
}
