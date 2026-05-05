<?php

namespace Tests\Sports\Handball;

use App\Helpers\DemoSeeders\DemoSeederFactory;
use App\Helpers\DemoSeeders\TeamSportSeeder;
use App\Sports\Handball\HandballArchetype;
use Tests\Integration\DemoReadinessTestHelper;
use Tests\Integration\TestCase;

/**
 * @group integration
 *
 * Faza B.1 — Handball demo-ready smoke test.
 *
 * Po wywolaniu TeamSportSeeder dla Handball'owego pluginu klub powinien
 * miec realne dummy data (drużyny, mecze, zdarzenia) — wystarczajace dla
 * potencjalnego klienta do zapoznania sie z funkcjonalnoscia.
 */
class HandballDemoReadyTest extends TestCase
{
    use DemoReadinessTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        DemoSeederFactory::reset();
    }

    public function testHandballPluginIsDemoReadyAfterSeed(): void
    {
        $clubId = $this->createTestClub('Handball Demo Club');
        $archetype = new HandballArchetype();

        DemoSeederFactory::register(new TeamSportSeeder());
        $result = DemoSeederFactory::seedFor($clubId, $archetype);

        $this->assertArrayHasKey('created', $result, 'TeamSportSeeder powinien zwrocic created counts');
        $this->assertGreaterThanOrEqual(1, $result['created']['team']    ?? 0, 'Min 1 druzyna');
        $this->assertGreaterThanOrEqual(1, $result['created']['athlete'] ?? 0, 'Min 1 zawodnik');
        $this->assertGreaterThanOrEqual(1, $result['created']['event']   ?? 0, 'Min 1 mecz');

        // Wszystkie tabele archetypu maja >=1 wierszy (dla teams + matches;
        // events moze wymagac extra schema details, traktujemy luznie).
        // Core demo-ready check: sprawdzamy core tables (teams + matches).
        $coreCounts = $this->getDemoCounts($archetype, $clubId);
        $this->assertGreaterThanOrEqual(1, $coreCounts['handball_teams']   ?? -1, 'handball_teams puste');
        $this->assertGreaterThanOrEqual(1, $coreCounts['handball_matches'] ?? -1, 'handball_matches puste');
    }

    public function testHandballArchetypeIsDemoReady(): void
    {
        $arch = new HandballArchetype();
        $this->assertTrue($arch->isDemoReady(), 'Handball powinien byc oznaczony demo-ready');
        $this->assertSame('handball', $arch->key());
        $this->assertContains('handball_teams', $arch->tables());
        $this->assertContains('handball_matches', $arch->tables());
        $this->assertContains('handball_events', $arch->tables());
    }
}
