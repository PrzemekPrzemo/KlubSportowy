<?php

namespace App\Sports\Basketball;

use App\Sports\Support\TeamSport;

/**
 * Basketball (PZKosz) — TeamSport archetype.
 *
 * Schema: basketball_teams, basketball_matches
 */
class BasketballArchetype extends TeamSport
{
    public function key(): string
    {
        return 'basketball';
    }

    public function tables(): array
    {
        return ['basketball_teams', 'basketball_matches'];
    }

    public function isDemoReady(): bool
    {
        return true;
    }
}
