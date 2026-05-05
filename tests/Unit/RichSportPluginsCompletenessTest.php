<?php

namespace Tests\Unit;

use App\Helpers\SportModuleLoader;
use PHPUnit\Framework\TestCase;

/**
 * Phase 3 / E2 lite — per-sport completeness audit dla 7 "bogatych"
 * sportow (Football, Basketball, Volleyball, Athletics, Judo, Karate,
 * Taekwondo). Sprawdza ze kazdy plugin ma:
 *   - manifest z listing route do glownej encji
 *   - controllery wymienione w manifest existuja jako klasy
 *   - kazda metoda handler'a wskazana w manifest istnieje na klasie
 *
 * Pelne integration testy (HTTP GET → 200) wymagaja running serwera —
 * niedostepne w obecnym CI. Ten test daje 80% wartosci za 5% kosztu:
 * lapie regression typu "skopiowali manifest z innego sportu i nie
 * zaimplementowali kontrolera" lub "po refactorze zostawili dangling
 * route ktora wskazuje na nieistniejaca metode".
 *
 * Bez DB, bez HTTP.
 */
class RichSportPluginsCompletenessTest extends TestCase
{
    /** @var array<string,array<int,string>> sport_key => required base entity nav urls */
    private array $expectedNav = [
        'football'   => ['football/teams',     'football/matches'],
        'basketball' => ['basketball/teams',   'basketball/matches'],
        'volleyball' => ['volleyball/teams',   'volleyball/matches'],
        'athletics'  => ['athletics/records'],
        'judo'       => ['judo/results'],
        'karate'     => ['karate/results'],
        'taekwondo'  => ['taekwondo/results'],
    ];

    public function testRichSportsHaveManifest(): void
    {
        $modules = SportModuleLoader::load();
        foreach (array_keys($this->expectedNav) as $key) {
            $this->assertArrayHasKey($key, $modules, "Brak manifestu dla {$key}");
        }
    }

    public function testRichSportsHaveExpectedNavEntries(): void
    {
        $modules = SportModuleLoader::load();
        foreach ($this->expectedNav as $key => $expectedUrls) {
            $manifest = $modules[$key] ?? null;
            $this->assertNotNull($manifest, "Brak manifestu '{$key}'");

            $navUrls = array_map(fn($n) => $n['url'] ?? '', $manifest['nav'] ?? []);
            foreach ($expectedUrls as $url) {
                $this->assertContains(
                    $url,
                    $navUrls,
                    "Manifest '{$key}' nie ma w nav pozycji '{$url}'"
                );
            }
        }
    }

    public function testRichSportsControllersAndHandlersResolve(): void
    {
        $modules = SportModuleLoader::load();
        $unresolved = [];
        foreach (array_keys($this->expectedNav) as $key) {
            $manifest = $modules[$key] ?? null;
            if (!$manifest) {
                $unresolved[] = "{$key}: manifest missing";
                continue;
            }

            foreach (($manifest['routes'] ?? []) as $route) {
                if (!is_array($route) || count($route) !== 3) continue;
                [, , $handler] = $route;
                if (!is_array($handler) || count($handler) !== 2) continue;
                [$class, $method] = $handler;

                if (!class_exists($class)) {
                    $unresolved[] = "{$key}: class {$class} not found";
                    continue;
                }
                if (!method_exists($class, $method)) {
                    $unresolved[] = "{$key}: {$class}::{$method}() missing";
                }
            }
        }
        $this->assertEmpty(
            $unresolved,
            "Plugin completeness — brakujace klasy/metody:\n  - "
            . implode("\n  - ", $unresolved)
        );
    }

    public function testRichSportsHaveAtLeastOneGetRoute(): void
    {
        $modules = SportModuleLoader::load();
        foreach (array_keys($this->expectedNav) as $key) {
            $manifest = $modules[$key] ?? null;
            $this->assertNotNull($manifest, "Brak manifestu '{$key}'");

            $hasGet = false;
            foreach (($manifest['routes'] ?? []) as $route) {
                if (is_array($route) && ($route[0] ?? '') === 'GET') {
                    $hasGet = true; break;
                }
            }
            $this->assertTrue(
                $hasGet,
                "Plugin '{$key}' nie ma zadnej GET route — uzytkownik nie moze otworzyc strony"
            );
        }
    }

    public function testRichSportsControllersExtendBaseController(): void
    {
        $modules = SportModuleLoader::load();
        $offenders = [];
        $checked   = [];
        foreach (array_keys($this->expectedNav) as $key) {
            $manifest = $modules[$key] ?? null;
            if (!$manifest) continue;

            foreach (($manifest['routes'] ?? []) as $route) {
                if (!is_array($route) || count($route) !== 3) continue;
                [, , $handler] = $route;
                if (!is_array($handler) || empty($handler[0])) continue;
                $class = $handler[0];
                if (in_array($class, $checked, true)) continue;
                $checked[] = $class;

                if (!class_exists($class)) continue; // covered by other test
                if (!is_subclass_of($class, \App\Controllers\BaseController::class)) {
                    $offenders[] = "{$key}: {$class} nie dziedziczy po BaseController";
                }
            }
        }
        $this->assertEmpty(
            $offenders,
            "Plugin controllers nie dziedziczacy BaseController:\n  - "
            . implode("\n  - ", $offenders)
        );
    }
}
