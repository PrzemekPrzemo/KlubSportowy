<?php

namespace App\Sports\Rugby\Models;

use App\Sports\Support\TeamMatchStatsModel;

class RugbyMatchStatsModel extends TeamMatchStatsModel
{
    protected string $table       = 'sport_rugby_match_scoring';
    protected string $matchTable  = 'rugby_matches';
    protected array  $statsColumns = [
        'tries', 'conversions', 'penalties', 'drop_goals',
        'cards_yellow', 'cards_red',
    ];

    /** Wylicz total points na podstawie statystyk (try=5, conv=2, pen=3, drop=3). */
    public static function totalPoints(array $row): int
    {
        return 5 * (int)($row['tries']       ?? 0)
             + 2 * (int)($row['conversions'] ?? 0)
             + 3 * (int)($row['penalties']   ?? 0)
             + 3 * (int)($row['drop_goals']  ?? 0);
    }
}
