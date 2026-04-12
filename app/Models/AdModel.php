<?php

namespace App\Models;

class AdModel extends BaseModel
{
    protected string $table = 'ads';

    /**
     * Return active ads for a given target (club_panel, member_portal, public).
     * Filters by date range, is_active flag, and optionally club_id.
     */
    public function activeForTarget(string $target, ?int $clubId = null): array
    {
        $sql = "SELECT * FROM ads
                WHERE is_active = 1
                  AND target = ?
                  AND (start_date IS NULL OR start_date <= CURDATE())
                  AND (end_date   IS NULL OR end_date   >= CURDATE())";
        $params = [$target];

        if ($clubId !== null) {
            $sql .= " AND (club_id IS NULL OR club_id = ?)";
            $params[] = $clubId;
        } else {
            $sql .= " AND club_id IS NULL";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Increment impression counter for an ad.
     */
    public function recordImpression(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE ads SET impressions = impressions + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    /**
     * Increment click counter for an ad.
     */
    public function recordClick(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE ads SET clicks = clicks + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    /**
     * List all ads (for admin panel).
     */
    public function listAll(): array
    {
        $stmt = $this->db->query(
            "SELECT a.*, c.name AS club_name
             FROM ads a
             LEFT JOIN clubs c ON c.id = a.club_id
             ORDER BY a.created_at DESC"
        );
        return $stmt->fetchAll();
    }
}
