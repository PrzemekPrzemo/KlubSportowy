<?php

namespace App\Sports\Curling\Models;

use App\Models\ClubScopedModel;

class CurlingTeamModel extends ClubScopedModel
{
    protected string $table = 'curling_teams';

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT t.*, COUNT(p.id) AS player_count
             FROM curling_teams t
             LEFT JOIN curling_players p ON p.team_id = t.id
             WHERE t.club_id = ?
             GROUP BY t.id
             ORDER BY t.name"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function roster(int $teamId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, m.first_name, m.last_name, m.member_number
             FROM curling_players p
             JOIN members m ON m.id = p.member_id
             WHERE p.team_id = ? AND p.club_id = ?
             ORDER BY FIELD(p.position,'skip','third','second','lead','alternate'), m.last_name"
        );
        $stmt->execute([$teamId, $this->clubId()]);
        return $stmt->fetchAll();
    }

    public function addPlayer(int $teamId, int $memberId, array $data = []): void
    {
        $this->db->prepare(
            "INSERT IGNORE INTO curling_players (club_id, team_id, member_id, position, is_captain)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([
            $this->clubId(), $teamId, $memberId,
            $data['position'] ?? 'lead',
            !empty($data['is_captain']) ? 1 : 0,
        ]);
    }

    public function removePlayer(int $playerId): void
    {
        $this->db->prepare(
            "DELETE FROM curling_players WHERE id = ? AND club_id = ?"
        )->execute([$playerId, $this->clubId()]);
    }

    public function playerTeam(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, t.name AS team_name
             FROM curling_players p
             JOIN curling_teams t ON t.id = p.team_id
             WHERE p.member_id = ? AND p.club_id = ?
             LIMIT 1"
        );
        $stmt->execute([$memberId, $this->clubId()]);
        return $stmt->fetch() ?: null;
    }
}
