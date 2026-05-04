<?php

namespace App\Sports\IceHockey\Models;

use App\Models\ClubScopedModel;

class IceHockeyTeamModel extends ClubScopedModel
{
    protected string $table = 'icehockey_teams';

    public static array $POSITIONS = [
        'bramkarz'  => ['label' => 'Bramkarz (G)', 'class' => 'warning'],
        'obrońca'   => ['label' => 'Obrońca (D)',  'class' => 'primary'],
        'napastnik' => ['label' => 'Napastnik (F)','class' => 'danger'],
    ];

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT t.*,
                    (SELECT COUNT(*) FROM icehockey_players p WHERE p.team_id = t.id) AS player_count,
                    c.first_name AS coach_first, c.last_name AS coach_last
             FROM icehockey_teams t
             LEFT JOIN members c ON c.id = t.coach_id
             WHERE t.club_id = ?
             ORDER BY t.name"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function roster(int $teamId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, m.first_name, m.last_name, m.member_number
             FROM icehockey_players p
             JOIN members m ON m.id = p.member_id
             WHERE p.team_id = ? AND p.club_id = ?
             ORDER BY
                CASE p.position WHEN 'bramkarz' THEN 1 WHEN 'obrońca' THEN 2 ELSE 3 END,
                p.jersey_number, m.last_name"
        );
        $stmt->execute([$teamId, $this->clubId()]);
        return $stmt->fetchAll();
    }

    public function playerTeam(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT t.*, p.position, p.jersey_number, p.shoots, p.is_captain, p.is_assistant
             FROM icehockey_teams t
             JOIN icehockey_players p ON p.team_id = t.id
             WHERE p.member_id = ? AND t.club_id = ?
             LIMIT 1"
        );
        $stmt->execute([$memberId, $this->clubId()]);
        return $stmt->fetch() ?: null;
    }
}
