<?php

namespace Tests\Unit;

use App\Helpers\SportModuleLoader;
use PHPUnit\Framework\TestCase;

class SportModuleLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        SportModuleLoader::reset();
    }

    public function testLoadReturnsArray(): void
    {
        $modules = SportModuleLoader::load();
        $this->assertIsArray($modules);
    }

    public function testShootingManifestLoaded(): void
    {
        $modules = SportModuleLoader::load();
        $this->assertArrayHasKey('shooting', $modules);
        $this->assertEquals('Strzelectwo', $modules['shooting']['name']);
        $this->assertEquals('PZSS', $modules['shooting']['federation']);
    }

    public function testFootballManifestLoaded(): void
    {
        $modules = SportModuleLoader::load();
        $this->assertArrayHasKey('football', $modules);
        $this->assertEquals('PZPN', $modules['football']['federation']);
    }

    public function testBasketballManifestLoaded(): void
    {
        $modules = SportModuleLoader::load();
        $this->assertArrayHasKey('basketball', $modules);
    }

    public function testVolleyballManifestLoaded(): void
    {
        $modules = SportModuleLoader::load();
        $this->assertArrayHasKey('volleyball', $modules);
    }

    public function testRollerskatingManifestLoaded(): void
    {
        $modules = SportModuleLoader::load();
        $this->assertArrayHasKey('rollerskating', $modules);
    }

    public function testAthleticsManifestLoaded(): void
    {
        $modules = SportModuleLoader::load();
        $this->assertArrayHasKey('athletics', $modules);
    }

    public function testGetReturnsManifest(): void
    {
        $m = SportModuleLoader::get('shooting');
        $this->assertNotNull($m);
        $this->assertEquals('shooting', $m['key']);
        $this->assertArrayHasKey('routes', $m);
        $this->assertArrayHasKey('nav', $m);
        $this->assertArrayHasKey('features', $m);
    }

    public function testGetUnknownReturnsNull(): void
    {
        $this->assertNull(SportModuleLoader::get('nonexistent'));
    }

    public function testAllManifestsHaveRequiredKeys(): void
    {
        $required = ['key', 'name', 'federation', 'features', 'routes', 'nav'];
        foreach (SportModuleLoader::load() as $key => $manifest) {
            foreach ($required as $k) {
                $this->assertArrayHasKey($k, $manifest, "Manifest '{$key}' brakuje klucza '{$k}'");
            }
        }
    }

    public function testRoutesAreArraysOfThree(): void
    {
        foreach (SportModuleLoader::load() as $key => $manifest) {
            foreach ($manifest['routes'] as $i => $route) {
                $this->assertCount(3, $route, "Manifest '{$key}' route {$i} musi mieć 3 elementy [method, path, handler]");
                $this->assertContains($route[0], ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']);
                $this->assertIsArray($route[2]);
            }
        }
    }

    public function testNavItemsHaveLabelAndUrl(): void
    {
        foreach (SportModuleLoader::load() as $key => $manifest) {
            foreach ($manifest['nav'] as $i => $item) {
                $this->assertArrayHasKey('label', $item, "Manifest '{$key}' nav[{$i}] brakuje 'label'");
                $this->assertArrayHasKey('url', $item, "Manifest '{$key}' nav[{$i}] brakuje 'url'");
            }
        }
    }

    /**
     * Catalogue audit: gdy ktos doda nowy plugin sport, prosze dodac
     * jego manifest z poprawnymi kluczami i podlinkowac migracje.
     * Test obejmuje wszystkie pluginy w app/Sports/ — nowe sporty
     * nie wymagaja zmian w testach.
     */
    public function testEachManifestHasMigrationsPath(): void
    {
        foreach (SportModuleLoader::load() as $key => $manifest) {
            $this->assertArrayHasKey('migrations', $manifest,
                "Manifest '{$key}' brakuje klucza 'migrations'");
            $path = $manifest['migrations'];
            $this->assertIsString($path, "Manifest '{$key}' migrations musi byc stringiem");
            $this->assertDirectoryExists($path,
                "Manifest '{$key}' wskazuje na nieistniejacy katalog migracji: {$path}");
        }
    }

    public function testEachManifestHasAtLeastOneMigrationFile(): void
    {
        foreach (SportModuleLoader::load() as $key => $manifest) {
            $path  = $manifest['migrations'] ?? null;
            if (!$path || !is_dir($path)) continue; // pokryte przez powyzszy test

            $files = glob($path . '/*.sql') ?: [];
            $this->assertNotEmpty($files,
                "Manifest '{$key}' ma katalog migracji {$path} ale 0 plikow .sql");

            // Wszystkie migracje powinny byc poprawnie ponumerowane (NNN_name.sql)
            foreach ($files as $f) {
                $this->assertMatchesRegularExpression(
                    '/\\/[0-9]{3}_[a-z0-9_]+\\.sql$/',
                    $f,
                    "Migracja '{$f}' nie jest w formacie NNN_name.sql"
                );
            }
        }
    }

    public function testFederationFieldNotEmpty(): void
    {
        // Wymog tylko: kazdy plugin musi miec niepuste 'federation'
        // (konkretne formatowanie zostawiamy autorom pluginow).
        foreach (SportModuleLoader::load() as $key => $manifest) {
            $this->assertNotEmpty(
                $manifest['federation'] ?? null,
                "Manifest '{$key}' ma puste pole 'federation'"
            );
        }
    }

    public function testCacheWorksCorrectly(): void
    {
        $first  = SportModuleLoader::load();
        $second = SportModuleLoader::load();
        $this->assertSame($first, $second);
    }
}
