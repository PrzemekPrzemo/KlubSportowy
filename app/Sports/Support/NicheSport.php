<?php

namespace App\Sports\Support;

/**
 * Archetyp niche / custom: sporty ktore nie pasuja do kanonicznych
 * 6 archetypow i wymagaja unikalnej domeny.
 *
 * Pasuje do: CrossFit (WODs/leaderboard), Climbing (drogi z grade'm),
 *            Sailing (regatty + class system), Chess (ELO + opening),
 *            Bridge (pary + MP/IMP).
 *
 * Konwencja tabel:
 *   <key>_athletes  / <key>_climbers / <key>_sailors  — zawodnicy
 *   <key>_<event>   — sport-specific event (workout / route / regatta / tournament)
 *   <key>_<result>  — sport-specific result (score / sent / finish position / score)
 */
abstract class NicheSport extends BaseSportArchetype
{
    public function entityTypes(): array
    {
        return ['athlete' => 'athletes', 'event' => 'events', 'result' => 'results'];
    }

    public function defaultSeedCounts(): array
    {
        return [
            'athlete' => 6,
            'event'   => 3,
            'result'  => 12,
        ];
    }

    /**
     * Niche sport plugins MUSZA nadpisac tables() bo nie ma kanonicznej
     * konwencji nazw.
     */
    abstract public function tables(): array;
}
