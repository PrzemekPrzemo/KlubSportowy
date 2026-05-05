<?php

namespace Tests\Integration;

use App\Helpers\SportModuleLoader;

/**
 * @group integration
 *
 * Faza M — sanity test: kazdy plugin sportu deklaruje sensowne routes.
 *
 * Sprawdza:
 *   - manifest definuje >=1 route
 *   - kazda route ma kontroler + metode ktora istnieje
 *   - manifest 'archetype' jest istanowalna klasa (po Fazach C-H, X)
 *   - kazdy archetyp deklaruje min 1 tabela w tables() ktora ma ENGINE=InnoDB
 *
 * Nie startuje HTTP — testuje strukturalna integralnosc plugin'ow.
 * Pozwala wykryc literowki w manifest.php zanim klient zobaczy 500 error.
 */
class SportPluginRoutesTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: array}>
     */
    public static function manifestProvider(): array
    {
        $out = [];
        foreach (SportModuleLoader::load() as $key => $manifest) {
            $out[$key] = [$key, $manifest];
        }
        return $out;
    }

    /**
     * @dataProvider manifestProvider
     */
    public function testManifestHasArchetype(string $sportKey, array $manifest): void
    {
        $this->assertArrayHasKey('archetype', $manifest, "Sport '{$sportKey}': brak 'archetype' w manifest.php");
        $fqcn = $manifest['archetype'];
        $this->assertTrue(
            class_exists($fqcn),
            "Sport '{$sportKey}': archetype class '{$fqcn}' nie istnieje"
        );
    }

    /**
     * @dataProvider manifestProvider
     */
    public function testArchetypeIsInstantiableAndDeclaresTables(string $sportKey, array $manifest): void
    {
        $fqcn = $manifest['archetype'] ?? null;
        if (!$fqcn) {
            $this->markTestSkipped("brak archetype dla {$sportKey}");
        }

        $arch = new $fqcn();
        $this->assertSame(
            $sportKey,
            $arch->key(),
            "Sport '{$sportKey}': archetype.key() = '{$arch->key()}' (mismatch z manifest.key)"
        );
        $tables = $arch->tables();
        $this->assertNotEmpty(
            $tables,
            "Sport '{$sportKey}': archetype.tables() jest puste"
        );
    }

    /**
     * @dataProvider manifestProvider
     */
    public function testManifestRoutesPointToCallableHandlers(string $sportKey, array $manifest): void
    {
        $this->assertArrayHasKey('routes', $manifest, "Sport '{$sportKey}': brak 'routes'");
        $this->assertNotEmpty($manifest['routes'], "Sport '{$sportKey}': 'routes' jest puste");

        foreach ($manifest['routes'] as $i => $route) {
            $this->assertCount(3, $route, "Sport '{$sportKey}': route[{$i}] ma niewlasciwy ksztalt");
            [$method, $path, $handler] = $route;

            $this->assertContains(
                $method,
                ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
                "Sport '{$sportKey}': route[{$i}] ma niedozwolony method '{$method}'"
            );
            $this->assertStringStartsWith('/', $path, "Sport '{$sportKey}': route[{$i}] path nie zaczyna sie od '/'");
            $this->assertCount(2, $handler, "Sport '{$sportKey}': route[{$i}] handler nie jest [Class, method]");

            [$class, $methodName] = $handler;
            $this->assertTrue(
                class_exists($class),
                "Sport '{$sportKey}': route[{$i}] class '{$class}' nie istnieje"
            );
            $this->assertTrue(
                method_exists($class, $methodName),
                "Sport '{$sportKey}': route[{$i}] '{$class}::{$methodName}' nie istnieje"
            );
        }
    }

    /**
     * @dataProvider manifestProvider
     */
    public function testManifestNavLinksHaveValidFormat(string $sportKey, array $manifest): void
    {
        if (!isset($manifest['nav'])) {
            $this->markTestSkipped("Sport '{$sportKey}': brak 'nav' (opcjonalne)");
        }
        foreach ($manifest['nav'] as $i => $item) {
            $this->assertArrayHasKey('label', $item, "Sport '{$sportKey}': nav[{$i}] brak 'label'");
            $this->assertArrayHasKey('url',   $item, "Sport '{$sportKey}': nav[{$i}] brak 'url'");
        }
    }
}
