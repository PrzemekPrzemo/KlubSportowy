<?php

namespace App\Sports\FieldHockey\Models;

use App\Sports\Support\TeamMatchStatsModel;

class FieldHockeyMatchStatsModel extends TeamMatchStatsModel
{
    protected string $table       = 'sport_field_hockey_match_stats';
    protected string $matchTable  = 'field_hockey_matches';
    protected array  $statsColumns = [
        'goals', 'penalty_corners', 'penalty_strokes',
        'shots_total', 'saves',
        'cards_green', 'cards_yellow', 'cards_red',
    ];
}
