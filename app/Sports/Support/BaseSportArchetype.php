<?php

namespace App\Sports\Support;

/**
 * Bazowy kontrakt dla archetypu sportu. Archetypy grupuja sporty o
 * podobnej domenie (drużynowe / walki / czas / scoring / racket / sila /
 * niche) zeby dzielic logike CRUD-u, seedowania i widoków.
 *
 * Pluginy sportów rejestruja sie przez SportModuleLoader (manifest.php),
 * a archetyp jest dostarczany przez `archetype()` metode w manifescie:
 *
 *   return [
 *       'key'        => 'handball',
 *       'archetype'  => \App\Sports\Support\TeamSport::class,
 *       ...
 *   ];
 *
 * Klasy konkretne (TeamSport, CombatSport itd.) deklarują:
 *   - kanoniczne nazwy encji (athleteEntity / eventEntity / resultEntity)
 *   - default minimalny zestaw seed
 *   - hook do walidacji "demo-ready" (sport ma min 1 athlete / 1 event / 1 result)
 */
abstract class BaseSportArchetype
{
    /** Klucz sportu (football, handball, ...) */
    abstract public function key(): string;

    /**
     * Trzy kanoniczne typy encji w archetypie. Konkretny plugin sport
     * mapuje je na swoje tabele/modele:
     *   - 'athlete' — np. handball_players, mma_fighters, swimming_swimmers
     *   - 'event'   — np. handball_matches, fencing_bouts, cycling_races
     *   - 'result'  — np. handball_match_events, swimming_times, golf_scores
     */
    abstract public function entityTypes(): array;

    /**
     * Zalecana minimalna liczba rekordów per encja w demo seedzie.
     * DemoSeederFactory uzywa tego jako defaultu — konkretny sport moze
     * zwiekszyc (np. drużynowe potrzebuja >=12 zawodnikow w drużynie).
     */
    public function defaultSeedCounts(): array
    {
        return [
            'athlete' => 5,
            'event'   => 3,
            'result'  => 5,
        ];
    }

    /**
     * Lista nazw tabel ktore plugin sportu definuje (bez prefixu schema).
     * Demo seeder uzyje ich do count-check'u "demo-ready".
     * Konkretny plugin nadpisuje (np. ['handball_teams', 'handball_matches', ...]).
     */
    abstract public function tables(): array;

    /**
     * Czy plugin jest production-ready (full CRUD + portal + seed).
     * Domyslnie false — kazdy plugin podnosi swoja flage gdy jest gotowy.
     */
    public function isDemoReady(): bool
    {
        return false;
    }
}
