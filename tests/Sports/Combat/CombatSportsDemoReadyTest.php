<?php

namespace Tests\Sports\Combat;

use App\Helpers\DemoSeeders\CombatSportSeeder;
use App\Helpers\DemoSeeders\DemoSeederFactory;
use App\Sports\Aikido\AikidoArchetype;
use App\Sports\Bjj\BjjArchetype;
use App\Sports\Boxing\BoxingArchetype;
use App\Sports\Fencing\FencingArchetype;
use App\Sports\Kickboxing\KickboxingArchetype;
use App\Sports\Mma\MmaArchetype;
use App\Sports\Sambo\SamboArchetype;
use App\Sports\Support\BaseSportArchetype;
use App\Sports\Wrestling\WrestlingArchetype;
use Tests\Integration\DemoReadinessTestHelper;
use Tests\Integration\TestCase;

/**
 * @group integration
 *
 * Faza C.1-C.8 — smoke test demo-ready dla 8 sportow walki:
 * Boxing, Kickboxing, MMA, Wrestling, Sambo, Aikido, Bjj, Fencing.
 *
 * Po seedzie generic CombatSportSeeder kazdy sport ma min 1 wiersz w
 * `<key>_results`. Opcjonalne `<key>_belts` / `<key>_fighters` zostaja
 * zaseedowane gdy istnieja.
 */
class CombatSportsDemoReadyTest extends TestCase
{
    use DemoReadinessTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        DemoSeederFactory::reset();
        DemoSeederFactory::register(new CombatSportSeeder());
    }

    /**
     * @return array<string, array{0: BaseSportArchetype}>
     */
    public static function archetypeProvider(): array
    {
        return [
            'boxing'     => [new BoxingArchetype()],
            'kickboxing' => [new KickboxingArchetype()],
            'mma'        => [new MmaArchetype()],
            'wrestling'  => [new WrestlingArchetype()],
            'sambo'      => [new SamboArchetype()],
            'aikido'     => [new AikidoArchetype()],
            'bjj'        => [new BjjArchetype()],
            'fencing'    => [new FencingArchetype()],
        ];
    }

    /**
     * @dataProvider archetypeProvider
     */
    public function testCombatSportPluginIsDemoReadyAfterSeed(BaseSportArchetype $archetype): void
    {
        $key = $archetype->key();
        $clubId = $this->createTestClub("Demo Combat — {$key}");

        $result = DemoSeederFactory::seedFor($clubId, $archetype);

        $this->assertArrayHasKey('created', $result, "{$key}: brak created counts");
        $this->assertGreaterThanOrEqual(1, $result['created']['athlete'] ?? 0, "{$key}: 0 zawodnikow");

        // Core: results ma min 1 wpis (gdy tabela <key>_results istnieje w CI DB).
        $counts = $this->getDemoCounts($archetype, $clubId);
        $resultsTable = "{$key}_results";
        if (isset($counts[$resultsTable]) && $counts[$resultsTable] !== -1) {
            $this->assertGreaterThanOrEqual(1, $counts[$resultsTable], "{$key}: {$resultsTable} puste");
        }
    }

    /**
     * @dataProvider archetypeProvider
     */
    public function testCombatArchetypeFlagsAndKey(BaseSportArchetype $archetype): void
    {
        $this->assertTrue($archetype->isDemoReady(), get_class($archetype) . ' powinien byc demo-ready');
        $this->assertNotEmpty($archetype->key());
        $this->assertNotEmpty($archetype->tables());
    }
}
