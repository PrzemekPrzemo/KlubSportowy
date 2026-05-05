<?php

namespace App\Helpers\DemoSeeders;

use App\Sports\Support\BaseSportArchetype;

/**
 * Dispatcher seederow demo per archetyp.
 *
 * Faza B/C/D/... planu domkniecia 50 sportow rejestruje konkretne seedery
 * (TeamSportSeeder, CombatSportSeeder, ...) ktore implementuja
 * ArchetypeSeederInterface i znaja konwencje tabel + sport-specific pola.
 *
 * DemoSeeder.seedEnhanced() wola Factory::seedFor($clubId, $archetype) dla
 * kazdego aktywnego sportu w demo klubie.
 */
class DemoSeederFactory
{
    /** @var array<string, ArchetypeSeederInterface> archetypeFqcn => seeder */
    private static array $registry = [];

    /**
     * Rejestruje seedera dla danego archetypu. Zwykle wolane raz przy
     * bootstrapie (np. w DemoSeeder lub testach).
     */
    public static function register(ArchetypeSeederInterface $seeder): void
    {
        self::$registry[$seeder->archetypeClass()] = $seeder;
    }

    /**
     * Czysci registry — uzywane w testach.
     */
    public static function reset(): void
    {
        self::$registry = [];
    }

    /**
     * Zwraca seeder dla danego archetypu lub null jesli nie zarejestrowany.
     */
    public static function for(BaseSportArchetype $archetype): ?ArchetypeSeederInterface
    {
        $cls = get_class($archetype);
        // Bezposredni hit
        if (isset(self::$registry[$cls])) {
            return self::$registry[$cls];
        }
        // Sprawdz parent classes — np. zarejestrowany TeamSport, archetyp to
        // konkretny FootballArchetype extends TeamSport
        foreach (self::$registry as $registeredFqcn => $seeder) {
            if (is_subclass_of($archetype, $registeredFqcn)) {
                return $seeder;
            }
        }
        return null;
    }

    /**
     * Wywoluje seedera dla archetypu. Zwraca tablice utworzonych liczb
     * lub ['skipped' => 'reason'] gdy brak seedera.
     */
    public static function seedFor(
        int $clubId,
        BaseSportArchetype $archetype,
        array $counts = []
    ): array {
        $seeder = self::for($archetype);
        if ($seeder === null) {
            return [
                'skipped' => 'no_seeder_registered',
                'archetype' => get_class($archetype),
            ];
        }
        return $seeder->seed($clubId, $archetype, $counts);
    }

    /** Lista wszystkich zarejestrowanych archetype FQCN. */
    public static function registered(): array
    {
        return array_keys(self::$registry);
    }
}
