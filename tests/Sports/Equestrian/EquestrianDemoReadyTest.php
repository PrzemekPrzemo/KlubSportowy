<?php

namespace Tests\Sports\Equestrian;

use App\Helpers\DemoSeeders\DemoSeederFactory;
use App\Helpers\DemoSeeders\EquestrianSeeder;
use App\Sports\Equestrian\EquestrianArchetype;
use Tests\Integration\DemoReadinessTestHelper;
use Tests\Integration\TestCase;

/**
 * @group integration
 *
 * Faza Q.8 — equestrian demo-ready end-to-end test.
 *
 * Po wywolaniu EquestrianSeeder klub jezdziecki demo powinien miec:
 *   - 4 czlonkow + 4 wlascicieli + 5 koni + 4 riderow z licencja PZJ
 *   - 2 zawody + 6 klas + 12 startow + 12 wynikow
 *   - 5 szczepien + 8 sesji treningowych
 *
 * Wystarczajace by potencjalny klient (klub jezdziecki) zobaczyl
 * pelna funkcjonalnosc panelu.
 */
class EquestrianDemoReadyTest extends TestCase
{
    use DemoReadinessTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        DemoSeederFactory::reset();
    }

    public function testEquestrianPluginIsDemoReadyAfterSeed(): void
    {
        $clubId    = $this->createTestClub('Equestrian Demo Club');
        $archetype = new EquestrianArchetype();

        DemoSeederFactory::register(new EquestrianSeeder());
        $result = DemoSeederFactory::seedFor($clubId, $archetype);

        $this->assertArrayHasKey('created', $result);
        $created = $result['created'];

        // Core entities — minimum dla demo
        $this->assertGreaterThanOrEqual(3, $created['horse'] ?? 0,    'Min 3 konie');
        $this->assertGreaterThanOrEqual(2, $created['rider'] ?? 0,    'Min 2 riderow');
        $this->assertGreaterThanOrEqual(1, $created['competition'] ?? 0, 'Min 1 zawody');
        $this->assertGreaterThanOrEqual(2, $created['class'] ?? 0,    'Min 2 klasy');
        $this->assertGreaterThanOrEqual(2, $created['start'] ?? 0,    'Min 2 starty');

        // Sprawdz ze archetype tables maja dane
        $coreCounts = $this->getDemoCounts($archetype, $clubId);
        $this->assertGreaterThanOrEqual(1, $coreCounts['equestrian_horses']        ?? -1, 'horses puste');
        $this->assertGreaterThanOrEqual(1, $coreCounts['equestrian_competitions']  ?? -1, 'competitions puste');
    }

    public function testEquestrianArchetypeContract(): void
    {
        $a = new EquestrianArchetype();
        $this->assertSame('equestrian', $a->key());
        $this->assertContains('equestrian_horses',       $a->tables());
        $this->assertContains('equestrian_competitions', $a->tables());
        $this->assertContains('equestrian_results',      $a->tables());
        $this->assertTrue($a->isDemoReady());

        $counts = $a->defaultSeedCounts();
        $this->assertSame(5, $counts['horse']);
        $this->assertSame(4, $counts['rider']);
        $this->assertSame(2, $counts['competition']);
    }
}
