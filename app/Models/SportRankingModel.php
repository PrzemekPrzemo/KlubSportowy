<?php

namespace App\Models;

class SportRankingModel extends ClubScopedModel
{
    protected string $table = 'sport_rankings';

    public function listForSport(string $sportKey, string $season): array
    {
        $stmt = $this->db->prepare(
            "SELECT sr.*, m.first_name, m.last_name, m.member_number
             FROM sport_rankings sr
             JOIN members m ON m.id = sr.member_id
             WHERE sr.club_id = ? AND sr.sport_key = ? AND sr.season = ?
             ORDER BY sr.ranking_points DESC"
        );
        $stmt->execute([$this->clubId(), $sportKey, $season]);
        return $stmt->fetchAll();
    }

    public function listForMember(int $memberId, ?string $sportKey = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT * FROM sport_rankings
                WHERE club_id = ? AND member_id = ?";
        $params = [$clubId, $memberId];
        if ($sportKey !== null) {
            $sql .= " AND sport_key = ?";
            $params[] = $sportKey;
        }
        $sql .= " ORDER BY season DESC, ranking_points DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function addPoints(int $memberId, string $sportKey, string $season, int $points, bool $isWin = false): void
    {
        $clubId = $this->clubId();
        $this->db->prepare(
            "INSERT INTO sport_rankings (club_id, member_id, sport_key, season, ranking_points, competitions_count, wins)
             VALUES (?, ?, ?, ?, ?, 1, ?)
             ON DUPLICATE KEY UPDATE
               ranking_points     = ranking_points + VALUES(ranking_points),
               competitions_count = competitions_count + 1,
               wins               = wins + VALUES(wins)"
        )->execute([$clubId, $memberId, $sportKey, $season, $points, $isWin ? 1 : 0]);
        $this->recalculatePositions($sportKey, $season);
    }

    private function recalculatePositions(string $sportKey, string $season): void
    {
        $clubId = $this->clubId();
        $rows = $this->db->prepare(
            "SELECT id FROM sport_rankings WHERE club_id = ? AND sport_key = ? AND season = ?
             ORDER BY ranking_points DESC"
        );
        $rows->execute([$clubId, $sportKey, $season]);
        $pos = 1;
        foreach ($rows->fetchAll() as $row) {
            $this->db->prepare("UPDATE sport_rankings SET ranking_position = ? WHERE id = ?")
                     ->execute([$pos++, $row['id']]);
        }
    }

    public function seasons(string $sportKey): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT season FROM sport_rankings
             WHERE club_id = ? AND sport_key = ?
             ORDER BY season DESC"
        );
        $stmt->execute([$this->clubId(), $sportKey]);
        return array_column($stmt->fetchAll(), 'season');
    }

    public function seasonsForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT season FROM sport_rankings
             WHERE club_id = ? AND member_id = ?
             ORDER BY season DESC"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return array_column($stmt->fetchAll(), 'season');
    }
}
