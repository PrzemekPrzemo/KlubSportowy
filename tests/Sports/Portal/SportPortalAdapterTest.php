<?php

namespace Tests\Sports\Portal;

use App\Helpers\Database;
use App\Helpers\SportModuleLoader;
use App\Helpers\SportPortalAdapter;
use App\Sports\Support\BaseSportArchetype;
use Tests\Integration\TestCase;

/**
 * @group integration
 *
 * Faza I — test ogolnego adaptera portalu.
 *
 * Dla kazdego z 49 sportow:
 *   - manifest.archetype istnieje + jest istanowalny
 *   - SportPortalAdapter::loadForMember() zwraca strukture
 *     {title, sections} bez throw
 *   - kazda section ma wymagane klucze: table, label, columns, rows
 */
class SportPortalAdapterTest extends SportPortalTestCase
{
    public function testAllSportsHaveArchetypeOrAreLegacy(): void
    {
        $modules = SportModuleLoader::load();
        $missingArchetype = [];
        foreach ($modules as $key => $manifest) {
            if (empty($manifest['archetype']) || !class_exists($manifest['archetype'])) {
                $missingArchetype[] = $key;
            }
        }

        // Po Fazie H wszystkie 49 sportow ma archetype.
        $this->assertEmpty(
            $missingArchetype,
            "Sporty bez archetype'u: " . implode(', ', $missingArchetype)
        );
    }

    /**
     * @dataProvider archetypeProvider
     */
    public function testAdapterReturnsStructuredDataForArchetype(string $sportKey, BaseSportArchetype $archetype): void
    {
        $this->requireDatabase();
        $clubId = $this->createTestClub("Adapter Test — {$sportKey}");

        $adapter = new SportPortalAdapter(Database::pdo());
        // member_id=999999 — nie istnieje, ale adapter ma zwrocic strukture
        // (puste sections).
        $payload = $adapter->loadForMember($archetype, 999999, $clubId);

        $this->assertArrayHasKey('title', $payload);
        $this->assertArrayHasKey('sections', $payload);
        $this->assertIsArray($payload['sections']);

        foreach ($payload['sections'] as $section) {
            $this->assertArrayHasKey('table', $section);
            $this->assertArrayHasKey('label', $section);
            $this->assertArrayHasKey('columns', $section);
            $this->assertArrayHasKey('rows', $section);
            $this->assertIsArray($section['columns']);
            $this->assertIsArray($section['rows']);
        }
    }

    /**
     * @return array<string, array{0: string, 1: BaseSportArchetype}>
     */
    public static function archetypeProvider(): array
    {
        $out = [];
        foreach (SportModuleLoader::load() as $key => $manifest) {
            $fqcn = $manifest['archetype'] ?? null;
            if (!$fqcn || !class_exists($fqcn)) continue;
            $out[$key] = [$key, new $fqcn()];
        }
        return $out;
    }
}
