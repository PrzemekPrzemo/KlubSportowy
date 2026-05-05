<?php

namespace Tests\Sports\Scoring;

use App\Helpers\DemoSeeders\DemoSeederFactory;
use App\Helpers\DemoSeeders\ScoringSportSeeder;
use App\Sports\DanceSport\DanceSportArchetype;
use App\Sports\FigureSkating\FigureSkatingArchetype;
use App\Sports\Gymnastics\GymnasticsArchetype;
use App\Sports\Support\BaseSportArchetype;
use Tests\Integration\DemoReadinessTestHelper;
use Tests\Integration\TestCase;

/**
 * @group integration
 *
 * Faza E.1-E.3 — smoke test demo-ready dla 3 sportow scoring/sedziowanie:
 * FigureSkating, Gymnastics, DanceSport.
 *
 * Po seedzie generic ScoringSportSeeder kazdy sport ma min 1 zawodnika
 * i (gdy schema istnieje w CI DB) min 1 wpis w `<results_table>`.
 */
class ScoringSportsDemoReadyTest extends TestCase
{
    use DemoReadinessTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        DemoSeederFactory::reset();
        DemoSeederFactory::register(new ScoringSportSeeder());
    }

    /**
     * @return array<string, array{0: BaseSportArchetype, 1: string}>
     */
    public static function archetypeProvider(): array
    {
        return [
            'figureskating' => [new FigureSkatingArchetype(), 'figure_skating_results'],
            'gymnastics'    => [new GymnasticsArchetype(),    'gymnastics_results'],
            'dancesport'    => [new DanceSportArchetype(),    'dance_results'],
        ];
    }

    /**
     * @dataProvider archetypeProvider
     */
    public function testScoringSportPluginIsDemoReadyAfterSeed(BaseSportArchetype $archetype, string $resultsTable): void
    {
        $key = $archetype->key();
        $clubId = $this->createTestClub("Demo Scoring — {$key}");

        $result = DemoSeederFactory::seedFor($clubId, $archetype);

        $this->assertArrayHasKey('created', $result, "{$key}: brak created counts");
        $this->assertGreaterThanOrEqual(1, $result['created']['athlete'] ?? 0, "{$key}: 0 zawodnikow");

        $counts = $this->getDemoCounts($archetype, $clubId);
        if (isset($counts[$resultsTable]) && $counts[$resultsTable] !== -1) {
            $this->assertGreaterThanOrEqual(1, $counts[$resultsTable], "{$key}: {$resultsTable} puste");
        }
    }

    /**
     * @dataProvider archetypeProvider
     */
    public function testScoringArchetypeFlagsAndKey(BaseSportArchetype $archetype, string $resultsTable): void
    {
        $this->assertTrue($archetype->isDemoReady(), get_class($archetype) . ' powinien byc demo-ready');
        $this->assertNotEmpty($archetype->key());
        $this->assertContains($resultsTable, $archetype->tables(), "{$archetype->key()}: results table musi byc w tables()");
    }
}
