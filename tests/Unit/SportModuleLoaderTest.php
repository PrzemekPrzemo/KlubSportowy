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

    public function testShootingPluginRemoved(): void
    {
        // Strzelectwo jest obsługiwane przez zewnętrzny system shootero.pl,
        // wewnętrzny plugin został usunięty.
        $modules = SportModuleLoader::load();
        $this->assertArrayNotHasKey('shooting', $modules);
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
        $m = SportModuleLoader::get('football');
        $this->assertNotNull($m);
        $this->assertEquals('football', $m['key']);
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

    public function testCacheWorksCorrectly(): void
    {
        $first  = SportModuleLoader::load();
        $second = SportModuleLoader::load();
        $this->assertSame($first, $second);
    }
}
