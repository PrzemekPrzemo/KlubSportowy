<?php

namespace App\Sports\Squash\Models;

use App\Models\ClubScopedModel;

class SquashRankingModel extends ClubScopedModel
{
    protected string $table = 'squash_rankings';

    public function listForClub(string $season = ''): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT sr.*, m.first_name, m.last_name, m.member_number
                FROM squash_rankings sr
                JOIN members m ON m.id = sr.member_id
                WHERE sr.club_id = ?";
        $params = [$clubId];
        if ($season !== '') {
            $sql .= " AND sr.season = ?";
            $params[] = $season;
        }
        $sql .= " ORDER BY sr.psa_rating DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updateRating(int $memberId, int $rating, string $season): void
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "INSERT INTO squash_rankings (club_id, member_id, season, psa_rating)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE psa_rating = VALUES(psa_rating), updated_at = CURRENT_TIMESTAMP"
        );
        $stmt->execute([$clubId, $memberId, $season, $rating]);
    }

    public function seasons(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT DISTINCT season FROM squash_rankings WHERE club_id = ? ORDER BY season DESC"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}
