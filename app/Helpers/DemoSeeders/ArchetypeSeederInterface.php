<?php

namespace App\Helpers\DemoSeeders;

use App\Sports\Support\BaseSportArchetype;

/**
 * Kontrakt dla seederow per archetyp sportu.
 *
 * Kazdy archetyp (TeamSport, CombatSport, ...) ma swojego seedera ktory
 * potrafi wstrzyknac realistic dummy data dla pluginu sportu o tym
 * archetypie. DemoSeederFactory dispatchuje na bazie typu archetypu.
 */
interface ArchetypeSeederInterface
{
    /**
     * Seed pojedynczego sportu o danym archetypie do podanego klubu.
     *
     * @param int                  $clubId    klub do ktorego seedujemy
     * @param BaseSportArchetype   $archetype manifest+metadata sportu
     * @param array<string,int>    $counts    overrides dla defaultSeedCounts
     *                                        (np. ['athlete'=>20, 'event'=>10])
     * @return array{created: array<string,int>}  liczba utworzonych encji
     *                                            per typ ('athlete', 'event', 'result', ...)
     */
    public function seed(int $clubId, BaseSportArchetype $archetype, array $counts = []): array;

    /** Zwraca FQCN archetypu obslugiwanego przez tego seedera. */
    public function archetypeClass(): string;
}
