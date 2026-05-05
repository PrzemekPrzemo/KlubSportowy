<?php

namespace Tests\Sports\Strength;

use App\Helpers\DemoSeeders\DemoSeederFactory;
use App\Helpers\DemoSeeders\StrengthSportSeeder;
use App\Sports\Powerlifting\PowerliftingArchetype;
use App\Sports\Support\BaseSportArchetype;
use App\Sports\Weightlifting\WeightliftingArchetype;
use Tests\Integration\DemoReadinessTestHelper;
use Tests\Integration\TestCase;

/**
 * @group integration
 *
 * Faza G.1-G.2 — smoke test demo-ready dla 2 sportow silowych:
 * Weightlifting (PKC), Powerlifting (PZTSS).
 */
class StrengthSportsDemoReadyTest extends TestCase
{
    use DemoReadinessTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        DemoSeederFactory::reset();
        DemoSeederFactory::register(new StrengthSportSeeder());
    }

    /**
     * @return array<string, array{0: BaseSportArchetype, 1: string}>
     */
    public static function archetypeProvider(): array
    {
        return [
            'weightlifting' => [new WeightliftingArchetype(), 'weightlifting_results'],
            'powerlifting'  => [new PowerliftingArchetype(),  'powerlifting_results'],
        ];
    }

    /**
     * @dataProvider archetypeProvider
     */
    public function testStrengthSportPluginIsDemoReadyAfterSeed(BaseSportArchetype $archetype, string $primaryTable): void
    {
        $key = $archetype->key();
        $clubId = $this->createTestClub("Demo Strength — {$key}");

        $result = DemoSeederFactory::seedFor($clubId, $archetype);

        $this->assertArrayHasKey('created', $result, "{$key}: brak created counts");
        $this->assertGreaterThanOrEqual(1, $result['created']['athlete'] ?? 0, "{$key}: 0 zawodnikow");

        $counts = $this->getDemoCounts($archetype, $clubId);
        if (isset($counts[$primaryTable]) && $counts[$primaryTable] !== -1) {
            $this->assertGreaterThanOrEqual(1, $counts[$primaryTable], "{$key}: {$primaryTable} puste");
        }
    }

    /**
     * @dataProvider archetypeProvider
     */
    public function testStrengthArchetypeFlagsAndKey(BaseSportArchetype $archetype, string $primaryTable): void
    {
        $this->assertTrue($archetype->isDemoReady(), get_class($archetype) . ' powinien byc demo-ready');
        $this->assertNotEmpty($archetype->key());
        $this->assertContains($primaryTable, $archetype->tables());
    }
}
