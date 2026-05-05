<?php

namespace Tests\Sports\Racket;

use App\Helpers\DemoSeeders\DemoSeederFactory;
use App\Helpers\DemoSeeders\RacketSportSeeder;
use App\Sports\Archery\ArcheryArchetype;
use App\Sports\Badminton\BadmintonArchetype;
use App\Sports\Golf\GolfArchetype;
use App\Sports\Padel\PadelArchetype;
use App\Sports\Squash\SquashArchetype;
use App\Sports\Support\BaseSportArchetype;
use App\Sports\TableTennis\TableTennisArchetype;
use App\Sports\Tennis\TennisArchetype;
use Tests\Integration\DemoReadinessTestHelper;
use Tests\Integration\TestCase;

/**
 * @group integration
 *
 * Faza F.1-F.7 — smoke test demo-ready dla 7 sportow racket/cel:
 * Tennis, TableTennis, Badminton, Squash, Archery, Golf, Padel.
 *
 * Tabele i konwencje sa rozne — niektore uzywaja player1/player2
 * (Tennis matches, Padel pairs), inne member_id (TableTennis, Badminton,
 * Squash, Archery, Golf). Seeder iteruje archetype.tables() i obsluguje
 * defensywnie obie konwencje.
 */
class RacketSportsDemoReadyTest extends TestCase
{
    use DemoReadinessTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        DemoSeederFactory::reset();
        DemoSeederFactory::register(new RacketSportSeeder());
    }

    /**
     * @return array<string, array{0: BaseSportArchetype, 1: string}>
     */
    public static function archetypeProvider(): array
    {
        return [
            'tennis'      => [new TennisArchetype(),      'tennis_matches'],
            'tabletennis' => [new TableTennisArchetype(), 'table_tennis_results'],
            'badminton'   => [new BadmintonArchetype(),   'badminton_results'],
            'squash'      => [new SquashArchetype(),      'squash_results'],
            'archery'     => [new ArcheryArchetype(),     'archery_scores'],
            'golf'        => [new GolfArchetype(),        'golf_rounds'],
            'padel'       => [new PadelArchetype(),       'padel_pairs'],
        ];
    }

    /**
     * @dataProvider archetypeProvider
     */
    public function testRacketSportPluginIsDemoReadyAfterSeed(BaseSportArchetype $archetype, string $primaryTable): void
    {
        $key = $archetype->key();
        $clubId = $this->createTestClub("Demo Racket — {$key}");

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
    public function testRacketArchetypeFlagsAndKey(BaseSportArchetype $archetype, string $primaryTable): void
    {
        $this->assertTrue($archetype->isDemoReady(), get_class($archetype) . ' powinien byc demo-ready');
        $this->assertNotEmpty($archetype->key());
        $this->assertContains($primaryTable, $archetype->tables(), "{$archetype->key()}: primary table musi byc w tables()");
    }
}
