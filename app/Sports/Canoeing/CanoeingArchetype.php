<?php

namespace App\Sports\Canoeing;

use App\Sports\Support\TimingSport;

/**
 * CANOEING (Kajakarstwo) — TimingSport (timing-based: sprint + slalom).
 *
 * Schema:
 *   sport_canoeing_member         — profil zawodnika (klasa lodzi, ranking)
 *   sport_canoeing_race_results   — wyniki wyscigow (czas + kary + ranking)
 */
class CanoeingArchetype extends TimingSport
{
    public function key(): string
    {
        return 'canoeing';
    }

    public function tables(): array
    {
        return [
            'sport_canoeing_member',
            'sport_canoeing_race_results',
        ];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
