<?php

namespace Tests\Sports\Timing;

use App\Helpers\DemoSeeders\DemoSeederFactory;
use App\Helpers\DemoSeeders\TimingSportSeeder;
use App\Sports\AlpineSki\AlpineSkiArchetype;
use App\Sports\Biathlon\BiathlonArchetype;
use App\Sports\Cycling\CyclingArchetype;
use App\Sports\Kayaking\KayakingArchetype;
use App\Sports\Rowing\RowingArchetype;
use App\Sports\SkiJump\SkiJumpArchetype;
use App\Sports\Snowboard\SnowboardArchetype;
use App\Sports\Support\BaseSportArchetype;
use App\Sports\Swimming\SwimmingArchetype;
use App\Sports\Triathlon\TriathlonArchetype;
use App\Sports\XcSki\XcSkiArchetype;
use Tests\Integration\DemoReadinessTestHelper;
use Tests\Integration\TestCase;

/**
 * @group integration
 *
 * Faza D.1-D.10 — smoke test demo-ready dla 10 sportow wytrzymalosciowych:
 * Swimming, Cycling, Triathlon, Biathlon, Kayaking, Rowing, AlpineSki,
 * XcSki, SkiJump, Snowboard.
 *
 * Po seedzie generic TimingSportSeeder kazdy sport ma min 1 zawodnika
 * i (gdy schema istnieje w CI DB) min 1 wpis w `<results_table>`.
 */
class TimingSportsDemoReadyTest extends TestCase
{
    use DemoReadinessTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        DemoSeederFactory::reset();
        DemoSeederFactory::register(new TimingSportSeeder());
    }

    /**
     * @return array<string, array{0: BaseSportArchetype, 1: string}>
     */
    public static function archetypeProvider(): array
    {
        return [
            'swimming'  => [new SwimmingArchetype(),  'swimming_results'],
            'cycling'   => [new CyclingArchetype(),   'cycling_results'],
            'triathlon' => [new TriathlonArchetype(), 'triathlon_results'],
            'biathlon'  => [new BiathlonArchetype(),  'biathlon_results'],
            'kayaking'  => [new KayakingArchetype(),  'kayak_results'],
            'rowing'    => [new RowingArchetype(),    'rowing_results'],
            'alpineski' => [new AlpineSkiArchetype(), 'alpine_ski_results'],
            'xcski'     => [new XcSkiArchetype(),     'xc_ski_results'],
            'skijump'   => [new SkiJumpArchetype(),   'ski_jump_results'],
            'snowboard' => [new SnowboardArchetype(), 'snowboard_results'],
        ];
    }

    /**
     * @dataProvider archetypeProvider
     */
    public function testTimingSportPluginIsDemoReadyAfterSeed(BaseSportArchetype $archetype, string $resultsTable): void
    {
        $key = $archetype->key();
        $clubId = $this->createTestClub("Demo Timing — {$key}");

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
    public function testTimingArchetypeFlagsAndKey(BaseSportArchetype $archetype, string $resultsTable): void
    {
        $this->assertTrue($archetype->isDemoReady(), get_class($archetype) . ' powinien byc demo-ready');
        $this->assertNotEmpty($archetype->key());
        $this->assertContains($resultsTable, $archetype->tables(), "{$archetype->key()}: results table musi byc w tables()");
    }
}
