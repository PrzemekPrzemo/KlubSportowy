<?php

namespace App\Models;

class ClubSportModel extends ClubScopedModel
{
    protected string $table = 'club_sports';

    public function findWithSport(int $id): ?array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT cs.*, s.`key` AS sport_key, s.name AS sport_name,
                          s.icon, s.color, s.team_sport
                   FROM club_sports cs
                   JOIN sports s ON s.id = cs.sport_id
                   WHERE cs.id = ?";
        $params = [$id];
        if ($clubId !== null) {
            $sql     .= " AND cs.club_id = ?";
            $params[] = $clubId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function addSportToClub(int $clubId, int $sportId, ?string $customName = null): int
    {
        $sql = "INSERT INTO club_sports (club_id, sport_id, name, is_active, started_at)
                VALUES (?, ?, ?, 1, CURDATE())
                ON DUPLICATE KEY UPDATE is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId, $sportId, $customName]);
        return (int)$this->db->lastInsertId();
    }

    public function deactivate(int $id): bool
    {
        return $this->update($id, ['is_active' => 0]);
    }
}
