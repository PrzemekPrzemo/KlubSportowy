<?php

namespace App\Sports\TableTennis;

use App\Sports\Support\RacketSport;

/**
 * TableTennis — RacketSport archetype.
 *
 * Schema:
 *   table_tennis_results (member_id, competition_name/_date, placement)
 */
class TableTennisArchetype extends RacketSport
{
    public function key(): string
    {
        return 'table_tennis';
    }

    public function tables(): array
    {
        return ['table_tennis_results'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
