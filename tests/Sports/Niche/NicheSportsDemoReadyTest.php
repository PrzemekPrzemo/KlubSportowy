<?php

namespace Tests\Sports\Niche;

use App\Helpers\DemoSeeders\DemoSeederFactory;
use App\Helpers\DemoSeeders\NicheSportSeeder;
use App\Sports\Bridge\BridgeArchetype;
use App\Sports\Chess\ChessArchetype;
use App\Sports\Climbing\ClimbingArchetype;
use App\Sports\CrossFit\CrossFitArchetype;
use App\Sports\Sailing\SailingArchetype;
use App\Sports\Support\BaseSportArchetype;
use Tests\Integration\DemoReadinessTestHelper;
use Tests\Integration\TestCase;

/**
 * @group integration
 *
 * Faza H.1-H.5 — smoke test demo-ready dla 5 niche sportow:
 * Bridge, Chess, Climbing, CrossFit, Sailing.
 *
 * Niche sporty maja unikalne schemy z parent-child dependencies:
 *   - Climbing: routes → sends (FK)
 *   - CrossFit: wods → scores (FK)
 *   - Sailing: boats → crew (FK)
 *
 * NicheSportSeeder iteruje archetype.tables() i przekazuje insertedIds
 * miedzy tabelami przez lookup map — child tables uzywaja parent ids
 * jako FK.
 */
class NicheSportsDemoReadyTest extends TestCase
{
    use DemoReadinessTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        DemoSeederFactory::reset();
        DemoSeederFactory::register(new NicheSportSeeder());
    }

    /**
     * @return array<string, array{0: BaseSportArchetype, 1: string}>
     */
    public static function archetypeProvider(): array
    {
        return [
            'bridge'   => [new BridgeArchetype(),   'bridge_partnerships'],
            'chess'    => [new ChessArchetype(),    'chess_results'],
            'climbing' => [new ClimbingArchetype(), 'climbing_routes'],
            'crossfit' => [new CrossFitArchetype(), 'crossfit_wods'],
            'sailing'  => [new SailingArchetype(),  'sailing_boats'],
        ];
    }

    /**
     * @dataProvider archetypeProvider
     */
    public function testNicheSportPluginIsDemoReadyAfterSeed(BaseSportArchetype $archetype, string $primaryTable): void
    {
        $key = $archetype->key();
        $clubId = $this->createTestClub("Demo Niche — {$key}");

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
    public function testNicheArchetypeContract(BaseSportArchetype $archetype, string $primaryTable): void
    {
        $this->assertTrue($archetype->isDemoReady(), get_class($archetype) . ' powinien byc demo-ready');
        $this->assertNotEmpty($archetype->key());
        $this->assertContains($primaryTable, $archetype->tables());
    }
}
