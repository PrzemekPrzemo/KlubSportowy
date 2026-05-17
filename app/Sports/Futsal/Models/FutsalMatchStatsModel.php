<?php

namespace App\Sports\Futsal\Models;

use App\Sports\Support\TeamMatchStatsModel;

class FutsalMatchStatsModel extends TeamMatchStatsModel
{
    protected string $table       = 'sport_futsal_match_stats';
    protected string $matchTable  = 'futsal_matches';
    protected array  $statsColumns = [
        'goals', 'shots_total', 'shots_on_target', 'fouls',
        'yellow_cards', 'red_cards', 'blue_cards', 'team_fouls', 'saves_gk',
    ];

    /** Czy druzyna jest aktualnie w "5-foul team penalty rule" (kumulatywne fauly). */
    public static function isInTeamFoulPenalty(int $teamFouls): bool
    {
        return $teamFouls >= 5;
    }
}
