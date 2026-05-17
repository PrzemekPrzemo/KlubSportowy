<?php

namespace App\Sports\Esport;

use App\Sports\Support\NicheSport;

/**
 * E-SPORT — NicheSport (multi-game katalog + profile per gra + szczegoly meczu).
 *
 * Schema:
 *   sport_esport_games            — katalog gier (globalne + klubowe)
 *   sport_esport_member_profiles  — profile graczy per gra
 *   sport_esport_match_details    — rozszerzenie tournament_matches o pola gry
 */
class EsportArchetype extends NicheSport
{
    public function key(): string
    {
        return 'esport';
    }

    public function tables(): array
    {
        return [
            'sport_esport_games',
            'sport_esport_member_profiles',
            'sport_esport_match_details',
        ];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
