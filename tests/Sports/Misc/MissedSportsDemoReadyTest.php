<?php

namespace Tests\Sports\Misc;

use App\Helpers\DemoSeeders\CombatSportSeeder;
use App\Helpers\DemoSeeders\DemoSeederFactory;
use App\Helpers\DemoSeeders\TeamSportSeeder;
use App\Helpers\DemoSeeders\TimingSportSeeder;
use App\Sports\Athletics\AthleticsArchetype;
use App\Sports\Basketball\BasketballArchetype;
use App\Sports\Football\FootballArchetype;
use App\Sports\Judo\JudoArchetype;
use App\Sports\Karate\KarateArchetype;
use App\Sports\Rollerskating\RollerskatingArchetype;
use App\Sports\Support\BaseSportArchetype;
use App\Sports\Taekwondo\TaekwondoArchetype;
use App\Sports\Volleyball\VolleyballArchetype;
use Tests\Integration\DemoReadinessTestHelper;
use Tests\Integration\TestCase;

/**
 * @group integration
 *
 * Batch X — wire-up dla 8 sportow ktore mialy juz odpowiednie seedery
 * (TeamSportSeeder, CombatSportSeeder, TimingSportSeeder), ale brakowalo
 * archetype + manifest update:
 *
 * TeamSport     : Football, Basketball, Volleyball
 * CombatSport   : Judo, Karate, Taekwondo
 * TimingSport   : Athletics, Rollerskating
 */
class MissedSportsDemoReadyTest extends TestCase
{
    use DemoReadinessTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        DemoSeederFactory::reset();
        DemoSeederFactory::register(new TeamSportSeeder());
        DemoSeederFactory::register(new CombatSportSeeder());
        DemoSeederFactory::register(new TimingSportSeeder());
    }

    /**
     * @return array<string, array{0: BaseSportArchetype}>
     */
    public static function archetypeProvider(): array
    {
        return [
            'football'     => [new FootballArchetype()],
            'basketball'   => [new BasketballArchetype()],
            'volleyball'   => [new VolleyballArchetype()],
            'judo'         => [new JudoArchetype()],
            'karate'       => [new KarateArchetype()],
            'taekwondo'    => [new TaekwondoArchetype()],
            'athletics'    => [new AthleticsArchetype()],
            'rollerskating'=> [new RollerskatingArchetype()],
        ];
    }

    /**
     * @dataProvider archetypeProvider
     */
    public function testMissedSportArchetypeContract(BaseSportArchetype $archetype): void
    {
        $this->assertTrue($archetype->isDemoReady(), get_class($archetype) . ' powinien byc demo-ready');
        $this->assertNotEmpty($archetype->key());
        $this->assertNotEmpty($archetype->tables());
    }

    /**
     * @dataProvider archetypeProvider
     */
    public function testMissedSportSeederDispatches(BaseSportArchetype $archetype): void
    {
        $clubId = $this->createTestClub("Demo Misc — {$archetype->key()}");
        $result = DemoSeederFactory::seedFor($clubId, $archetype);

        $this->assertArrayHasKey('created', $result, "{$archetype->key()}: brak created counts (factory dispatch)");
        $this->assertGreaterThanOrEqual(1, $result['created']['athlete'] ?? 0, "{$archetype->key()}: 0 zawodnikow");
    }
}
