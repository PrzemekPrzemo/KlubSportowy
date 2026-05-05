<?php

namespace Tests\Unit;

use App\Helpers\DemoSeeders\ArchetypeSeederInterface;
use App\Helpers\DemoSeeders\DemoSeederFactory;
use App\Sports\Support\BaseSportArchetype;
use App\Sports\Support\TeamSport;
use App\Sports\Support\CombatSport;
use PHPUnit\Framework\TestCase;

/**
 * A.2 unit testy — DemoSeederFactory rejestracja + dispatch.
 */
class DemoSeederFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        DemoSeederFactory::reset();
    }

    public function testFactoryRegistryStartsEmpty(): void
    {
        $this->assertSame([], DemoSeederFactory::registered());
    }

    public function testRegisterAndRetrieveSeeder(): void
    {
        $seeder = $this->makeStubSeeder(TeamSport::class);
        DemoSeederFactory::register($seeder);

        $this->assertSame([TeamSport::class], DemoSeederFactory::registered());

        $stub = new class extends TeamSport {
            public function key(): string { return 'demo'; }
        };
        $this->assertSame($seeder, DemoSeederFactory::for($stub));
    }

    public function testForReturnsNullWhenNoSeeder(): void
    {
        $stub = new class extends TeamSport {
            public function key(): string { return 'demo'; }
        };
        $this->assertNull(DemoSeederFactory::for($stub));
    }

    public function testForDispatchesByParentClass(): void
    {
        // Rejestrujemy seeder dla TeamSport. Konkretny plugin moze rozszerzyc
        // TeamSport — Factory powinno dispatchowac do TeamSport seedera.
        $seeder = $this->makeStubSeeder(TeamSport::class);
        DemoSeederFactory::register($seeder);

        // Nowa klasa rozszerzajaca TeamSport (np. konkretny Football archetype)
        $extendedArchetype = new class extends TeamSport {
            public function key(): string { return 'football'; }
        };

        $this->assertSame($seeder, DemoSeederFactory::for($extendedArchetype));
    }

    public function testSeedForReturnsSkippedWhenNoSeeder(): void
    {
        $stub = new class extends CombatSport {
            public function key(): string { return 'demo'; }
        };
        $result = DemoSeederFactory::seedFor(1, $stub);
        $this->assertArrayHasKey('skipped', $result);
        $this->assertSame('no_seeder_registered', $result['skipped']);
    }

    public function testSeedForCallsSeederWhenRegistered(): void
    {
        $captured = [];
        $seeder = new class($captured) implements ArchetypeSeederInterface {
            public function __construct(private array &$captured) {}
            public function archetypeClass(): string { return TeamSport::class; }
            public function seed(int $clubId, BaseSportArchetype $archetype, array $counts = []): array
            {
                $this->captured[] = ['club' => $clubId, 'arch' => get_class($archetype), 'counts' => $counts];
                return ['created' => ['athlete' => 12, 'event' => 5]];
            }
        };
        DemoSeederFactory::register($seeder);

        $stub = new class extends TeamSport {
            public function key(): string { return 'football'; }
        };
        $result = DemoSeederFactory::seedFor(42, $stub, ['athlete' => 20]);

        $this->assertCount(1, $captured);
        $this->assertSame(42, $captured[0]['club']);
        $this->assertSame(['athlete' => 20], $captured[0]['counts']);
        $this->assertSame(12, $result['created']['athlete']);
    }

    public function testResetClearsRegistry(): void
    {
        DemoSeederFactory::register($this->makeStubSeeder(TeamSport::class));
        $this->assertNotEmpty(DemoSeederFactory::registered());
        DemoSeederFactory::reset();
        $this->assertSame([], DemoSeederFactory::registered());
    }

    /** @return ArchetypeSeederInterface */
    private function makeStubSeeder(string $archetypeFqcn): ArchetypeSeederInterface
    {
        return new class($archetypeFqcn) implements ArchetypeSeederInterface {
            public function __construct(private string $fqcn) {}
            public function archetypeClass(): string { return $this->fqcn; }
            public function seed(int $clubId, BaseSportArchetype $archetype, array $counts = []): array
            {
                return ['created' => []];
            }
        };
    }
}
