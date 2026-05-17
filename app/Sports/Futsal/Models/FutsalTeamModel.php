<?php

namespace App\Sports\Futsal\Models;

use App\Models\ClubScopedModel;

class FutsalTeamModel extends ClubScopedModel
{
    protected string $table = 'futsal_teams';

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT t.*, COUNT(p.id) AS player_count
             FROM futsal_teams t
             LEFT JOIN futsal_players p ON p.team_id = t.id
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
             FROM futsal_players p
             JOIN members m ON m.id = p.member_id
             WHERE p.team_id = ? AND p.club_id = ?
             ORDER BY p.jersey_number, m.last_name"
        );
        $stmt->execute([$teamId, $this->clubId()]);
        return $stmt->fetchAll();
    }

    public function addPlayer(int $teamId, int $memberId, array $data = []): void
    {
        $this->db->prepare(
            "INSERT IGNORE INTO futsal_players (club_id, team_id, member_id, jersey_number, position, is_captain)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([
            $this->clubId(), $teamId, $memberId,
            $data['jersey_number'] ?? null,
            $data['position'] ?? 'uniwersalny',
            !empty($data['is_captain']) ? 1 : 0,
        ]);
    }

    public function removePlayer(int $playerId): void
    {
        $this->db->prepare(
            "DELETE FROM futsal_players WHERE id = ? AND club_id = ?"
        )->execute([$playerId, $this->clubId()]);
    }

    public function playerTeam(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, t.name AS team_name
             FROM futsal_players p
             JOIN futsal_teams t ON t.id = p.team_id
             WHERE p.member_id = ? AND p.club_id = ?
             LIMIT 1"
        );
        $stmt->execute([$memberId, $this->clubId()]);
        return $stmt->fetch() ?: null;
    }
}
