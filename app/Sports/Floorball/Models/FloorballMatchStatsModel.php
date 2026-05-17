<?php

namespace App\Sports\Floorball\Models;

use App\Sports\Support\TeamMatchStatsModel;

class FloorballMatchStatsModel extends TeamMatchStatsModel
{
    protected string $table       = 'sport_floorball_match_stats';
    protected string $matchTable  = 'floorball_matches';
    protected array  $statsColumns = [
        'goals', 'shots_total', 'saves',
        'penalties_2min', 'penalties_10min',
        'power_play_goals', 'short_handed_goals',
    ];
}
