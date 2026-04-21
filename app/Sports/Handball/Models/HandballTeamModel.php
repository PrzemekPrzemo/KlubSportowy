<?php

namespace App\Sports\Handball\Models;

use App\Models\ClubScopedModel;

class HandballTeamModel extends ClubScopedModel
{
    protected string $table = 'handball_teams';

    public static array $CATEGORIES = [
        'senior_m'   => 'Seniorzy (M)',
        'senior_k'   => 'Seniorzy (K)',
        'junior_m'   => 'Juniorzy (M)',
        'junior_k'   => 'Juniorki (K)',
        'mlodzik_m'  => 'Młodzicy (M)',
        'mlodzik_k'  => 'Młodziczki (K)',
        'dzieci'     => 'Dzieci',
    ];

    public static array $POSITIONS = [
        'bramkarz'         => 'Bramkarz',
        'rozgrywający'     => 'Rozgrywający',
        'obrotowy'         => 'Obrotowy',
        'skrzydłowy_lewy'  => 'Skrzydłowy lewy',
        'skrzydłowy_prawy' => 'Skrzydłowy prawy',
        'kołowy'           => 'Kołowy',
        'uniwersalny'      => 'Uniwersalny',
    ];

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT t.*,
                    (SELECT COUNT(*) FROM handball_players p WHERE p.team_id = t.id) AS player_count,
                    c.first_name AS coach_first, c.last_name AS coach_last
             FROM handball_teams t
             LEFT JOIN members c ON c.id = t.coach_id
             WHERE t.club_id = ?
             ORDER BY t.category, t.name"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function roster(int $teamId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, m.first_name, m.last_name, m.member_number
             FROM handball_players p
             JOIN members m ON m.id = p.member_id
             WHERE p.team_id = ? AND p.club_id = ?
             ORDER BY p.jersey_number, m.last_name"
        );
        $stmt->execute([$teamId, $this->clubId()]);
        return $stmt->fetchAll();
    }

    public function playerTeam(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT t.*, p.position, p.jersey_number, p.is_captain
             FROM handball_teams t
             JOIN handball_players p ON p.team_id = t.id
             WHERE p.member_id = ? AND t.club_id = ?
             LIMIT 1"
        );
        $stmt->execute([$memberId, $this->clubId()]);
        return $stmt->fetch() ?: null;
    }
}
