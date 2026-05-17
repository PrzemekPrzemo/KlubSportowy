<?php

namespace App\Sports\WaterPolo\Models;

use App\Sports\Support\TeamMatchStatsModel;

class WaterPoloMatchStatsModel extends TeamMatchStatsModel
{
    protected string $table       = 'sport_water_polo_match_stats';
    protected string $matchTable  = 'water_polo_matches';
    protected array  $statsColumns = [
        'goals', 'exclusions', 'exclusion_seconds',
        'saves', 'steals', 'penalties_for',
    ];
}
