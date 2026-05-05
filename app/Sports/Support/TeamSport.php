<?php

namespace App\Sports\Support;

/**
 * Archetyp drużynowy: 2+ druzyn graja przeciwko sobie, scoring per match,
 * statystyki per zawodnik per match, transfery zawodnikow.
 *
 * Pasuje do: Football, Basketball, Volleyball, Handball, IceHockey,
 *            FieldHockey, Rugby, Floorball, Padel.
 *
 * Konwencja tabel:
 *   <key>_teams      — drużyny w klubie
 *   <key>_matches    — mecze (home_team_id, away_team, home_score, away_score)
 *   <key>_match_events   — gole/asysty/kartki/punkty per zawodnik
 *   <key>_lineups        — sklady per mecz
 *   <key>_transfers      — przyjscia/odejscia zawodnikow
 */
abstract class TeamSport extends BaseSportArchetype
{
    public function entityTypes(): array
    {
        return ['athlete' => 'players', 'event' => 'matches', 'result' => 'match_events'];
    }

    public function defaultSeedCounts(): array
    {
        return [
            'team'    => 2,    // min 2 do meczu
            'athlete' => 12,   // 12 graczy / drużyna
            'event'   => 5,    // 5 meczy
            'result'  => 8,    // ~8 zdarzen per mecz
        ];
    }

    public function tables(): array
    {
        $k = $this->key();
        return [
            "{$k}_teams",
            "{$k}_matches",
            "{$k}_match_events",
        ];
    }
}
