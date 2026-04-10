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
     * Jeśli SportContext nie jest ustawiony — zwraca pustą tablicę.
     */
    public static function navForActiveSport(): array
    {
        $key = SportContext::currentSportKey();
        if ($key === null) return [];
        $module = self::get($key);
        return $module['nav'] ?? [];
    }

    /** Reset cache — używane w testach. */
    public static function reset(): void
    {
        self::$modules = null;
    }
}
