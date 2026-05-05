<?php

namespace Tests\Unit;

use App\Sports\Support\BaseSportArchetype;
use App\Sports\Support\TeamSport;
use App\Sports\Support\CombatSport;
use App\Sports\Support\TimingSport;
use App\Sports\Support\ScoringSport;
use App\Sports\Support\RacketSport;
use App\Sports\Support\StrengthSport;
use App\Sports\Support\NicheSport;
use PHPUnit\Framework\TestCase;

/**
 * Sanity test dla A.1 — BaseSportArchetype + 7 abstract klas.
 * Weryfikuje ze archetypy istnieja, sa abstract'ami, dziedziczą po Base
 * i deklaruja wymagane metody. Bez DB / HTTP.
 */
class SportArchetypesTest extends TestCase
{
    /**
     * Konkretny test stub dla kazdego archetypu — minimal class implementing
     * abstract methods.
     */
    public function testBaseClassIsAbstract(): void
    {
        $ref = new \ReflectionClass(BaseSportArchetype::class);
        $this->assertTrue($ref->isAbstract(), 'BaseSportArchetype musi byc abstract');
    }

    public function testAllSevenArchetypesExist(): void
    {
        $expected = [
            TeamSport::class, CombatSport::class, TimingSport::class,
            ScoringSport::class, RacketSport::class, StrengthSport::class,
            NicheSport::class,
        ];
        foreach ($expected as $cls) {
            $this->assertTrue(class_exists($cls), "Archetyp {$cls} nie istnieje");
            $ref = new \ReflectionClass($cls);
            $this->assertTrue($ref->isAbstract(), "{$cls} musi byc abstract");
            $this->assertTrue(
                $ref->isSubclassOf(BaseSportArchetype::class),
                "{$cls} musi dziedziczyc po BaseSportArchetype"
            );
        }
    }

    public function testTeamSportHasExpectedSeedCounts(): void
    {
        $stub = new class extends TeamSport {
            public function key(): string { return 'demo'; }
        };
        $counts = $stub->defaultSeedCounts();
        $this->assertGreaterThanOrEqual(2, $counts['team'] ?? 0);
        $this->assertGreaterThanOrEqual(12, $counts['athlete'] ?? 0); // mini drużyna
        $this->assertArrayHasKey('event', $counts);
        $this->assertArrayHasKey('result', $counts);
    }

    public function testCombatSportTablesUseFightersAndFights(): void
    {
        $stub = new class extends CombatSport {
            public function key(): string { return 'demo'; }
        };
        $tables = $stub->tables();
        $this->assertContains('demo_fighters', $tables);
        $this->assertContains('demo_fights', $tables);
    }

    public function testTimingSportEntitiesAreTimes(): void
    {
        $stub = new class extends TimingSport {
            public function key(): string { return 'demo'; }
        };
        $this->assertSame('times', $stub->entityTypes()['result']);
    }

    public function testNicheSportRequiresExplicitTables(): void
    {
        // NicheSport::tables() jest abstract — nie da sie utworzyc bez nadpisania
        $stub = new class extends NicheSport {
            public function key(): string { return 'demo'; }
            public function tables(): array { return ['demo_x', 'demo_y']; }
        };
        $this->assertSame(['demo_x', 'demo_y'], $stub->tables());
    }

    public function testIsDemoReadyDefaultsToFalse(): void
    {
        $stub = new class extends TeamSport {
            public function key(): string { return 'demo'; }
        };
        $this->assertFalse($stub->isDemoReady(),
            'Plugin musi explicit zadeklarowac demo-ready przez nadpisanie isDemoReady');
    }
}
