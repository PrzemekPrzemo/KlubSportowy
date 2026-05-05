<?php

namespace App\Sports\Archery;

use App\Sports\Support\RacketSport;

/**
 * Archery — RacketSport archetype (cel/precision).
 *
 * Schema:
 *   archery_scores (member_id, score_date, discipline ENUM REQUIRED, total_score)
 *   archery_bows (member_id NULL — pominiete jesli nie ma fk wymaganego)
 */
class ArcheryArchetype extends RacketSport
{
    public function key(): string
    {
        return 'archery';
    }

    public function tables(): array
    {
        return ['archery_scores'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
