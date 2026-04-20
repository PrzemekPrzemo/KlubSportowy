<?php

namespace App\Sports\Football\Models;

use App\Models\ClubScopedModel;

class FootballLeagueModel extends ClubScopedModel
{
    protected string $table = 'football_leagues';

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT * FROM football_leagues";
        $params = [];
        if ($clubId !== null) {
            $sql .= " WHERE club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY season DESC, name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function standingsForLeague(int $leagueId): array
    {
        $sql = "SELECT flt.*, ft.name AS team_name
                FROM football_league_teams flt
                JOIN football_teams ft ON ft.id = flt.team_id
                WHERE flt.league_id = ?
                ORDER BY flt.points DESC, flt.goal_diff DESC, flt.goals_for DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$leagueId]);
        return $stmt->fetchAll();
    }

    public function recalculateStandings(int $leagueId): void
    {
        // Get all team IDs in this league
        $stmt = $this->db->prepare(
            "SELECT team_id FROM football_league_teams WHERE league_id = ?"
        );
        $stmt->execute([$leagueId]);
        $existingTeams = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        // Get all team IDs that appear in matches linked to teams in this league
        $stmt = $this->db->prepare(
            "SELECT team_id FROM football_league_teams WHERE league_id = ?"
        );
        $stmt->execute([$leagueId]);
        $leagueTeamIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($leagueTeamIds)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($leagueTeamIds), '?'));

        // Fetch all relevant matches
        $sql = "SELECT home_team_id, away_team_id, home_score, away_score
                FROM football_matches
                WHERE status = 'zakonczony'
                  AND home_score IS NOT NULL
                  AND away_score IS NOT NULL
                  AND (home_team_id IN ({$placeholders}) OR away_team_id IN ({$placeholders}))";
        $params = array_merge($leagueTeamIds, $leagueTeamIds);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $matches = $stmt->fetchAll();

        // Aggregate stats per team
        $stats = [];
        foreach ($leagueTeamIds as $tid) {
            $stats[(int)$tid] = [
                'games_played'  => 0,
                'wins'          => 0,
                'draws'         => 0,
                'losses'        => 0,
                'goals_for'     => 0,
                'goals_against' => 0,
            ];
        }

        foreach ($matches as $m) {
            $homeId  = (int)$m['home_team_id'];
            $awayId  = (int)($m['away_team_id'] ?? 0);
            $homeScore = (int)$m['home_score'];
            $awayScore = (int)$m['away_score'];

            if (isset($stats[$homeId])) {
                $stats[$homeId]['games_played']++;
                $stats[$homeId]['goals_for']     += $homeScore;
                $stats[$homeId]['goals_against'] += $awayScore;
                if ($homeScore > $awayScore)      { $stats[$homeId]['wins']++;   }
                elseif ($homeScore === $awayScore) { $stats[$homeId]['draws']++;  }
                else                               { $stats[$homeId]['losses']++; }
            }

            if ($awayId > 0 && isset($stats[$awayId])) {
                $stats[$awayId]['games_played']++;
                $stats[$awayId]['goals_for']     += $awayScore;
                $stats[$awayId]['goals_against'] += $homeScore;
                if ($awayScore > $homeScore)      { $stats[$awayId]['wins']++;   }
                elseif ($awayScore === $homeScore) { $stats[$awayId]['draws']++;  }
                else                               { $stats[$awayId]['losses']++; }
            }
        }

        // Delete existing rows for this league
        $this->db->prepare("DELETE FROM football_league_teams WHERE league_id = ?")->execute([$leagueId]);

        // Re-insert updated stats
        $insertSql = "INSERT INTO football_league_teams
                        (league_id, team_id, points, games_played, wins, draws, losses, goals_for, goals_against)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $this->db->prepare($insertSql);

        foreach ($stats as $teamId => $s) {
            $points = $s['wins'] * 3 + $s['draws'];
            $insertStmt->execute([
                $leagueId,
                $teamId,
                $points,
                $s['games_played'],
                $s['wins'],
                $s['draws'],
                $s['losses'],
                $s['goals_for'],
                $s['goals_against'],
            ]);
        }
    }

    public function addTeamToLeague(int $leagueId, int $teamId): void
    {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO football_league_teams (league_id, team_id) VALUES (?, ?)"
        );
        $stmt->execute([$leagueId, $teamId]);
    }
}
