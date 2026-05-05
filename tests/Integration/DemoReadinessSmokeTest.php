<?php

namespace Tests\Integration;

use App\Helpers\DemoSeeders\DemoSeederFactory;
use App\Sports\Support\TeamSport;
use App\Sports\Support\BaseSportArchetype;

/**
 * @group integration
 *
 * A.3 — Smoke test demo-readiness framework.
 *
 * Sam test nie wykonuje zadnego seed-u; weryfikuje ze:
 *   1. DemoSeederFactory dispatchuje archetyp na zarejestrowanego seedera
 *   2. assertSportDemoReady() poprawnie zlicza i flaguje brak demo data
 *
 * Faza B/C/D/... rejestruje konkretne seedery i pisze testy dziedziczace
 * po tym helperze, wolajace assertSportDemoReady() po seed'zie.
 */
class DemoReadinessSmokeTest extends TestCase
{
    use DemoReadinessTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        DemoSeederFactory::reset();
    }

    public function testHelperFailsWhenSportTableMissing(): void
    {
        // Tabela 'nonexistent_demo_*' nie istnieje — assertSportDemoReady musi
        // failowac z czytelnym komunikatem ('nie istnieje').
        $clubId = $this->createTestClub('Demo Smoke');

        $stub = new class extends TeamSport {
            public function key(): string { return 'nonexistent_demo'; }
        };

        try {
            $this->assertSportDemoReady($stub, $clubId);
            $this->fail('Should have failed for missing tables');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            $this->assertStringContainsString('nie istnieje', $e->getMessage());
        }
    }

    public function testHelperFailsWhenSportTableEmpty(): void
    {
        // Football tabele istnieja (po A.4 plugin migrations w CI), ale
        // dla nowo utworzonego klubu sa puste — helper musi to wykryc.
        $clubId = $this->createTestClub('Demo Smoke Empty');

        $stub = new class extends TeamSport {
            public function key(): string { return 'football'; }
        };

        try {
            $this->assertSportDemoReady($stub, $clubId);
            $this->fail('Should have failed for empty tables');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            $this->assertStringContainsString('Demo nie jest gotowe', $e->getMessage());
        }
    }

    public function testGetDemoCountsReturnsArrayPerTable(): void
    {
        $clubId = $this->createTestClub('Demo Counts Fresh');

        $stub = new class extends TeamSport {
            public function key(): string { return 'football'; }
        };

        $counts = $this->getDemoCounts($stub, $clubId);
        $this->assertNotEmpty($counts);
        // Helper zwraca count >= 0 dla istniejacych tabel albo -1 dla missing.
        // Wymagamy, ze przynajmniej JEDNA tabela istnieje (count >= 0) — po
        // A.4 plugin migrations w CI tabele Football powinny byc obecne.
        $existing = array_filter($counts, fn($c) => $c >= 0);
        $this->assertNotEmpty(
            $existing,
            'Zadna z tabel Football nie istnieje w CI DB — A.4 plugin migrations '
            . 'mogly nie zaaplikowac sie. Counts: ' . json_encode($counts)
        );
        // Dla istniejacych tabel — fresh club ma 0 wierszy (jeszcze nie seedujemy)
        foreach ($existing as $table => $count) {
            $this->assertSame(0, $count, "Fresh club: tabela {$table} powinna byc pusta");
        }
    }

    public function testFactoryReturnsSkippedWhenSeederNotRegistered(): void
    {
        $stub = new class extends TeamSport {
            public function key(): string { return 'football'; }
        };
        $result = DemoSeederFactory::seedFor(1, $stub);
        $this->assertArrayHasKey('skipped', $result);
    }
}
