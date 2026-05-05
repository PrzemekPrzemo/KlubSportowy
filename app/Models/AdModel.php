<?php

namespace App\Models;

class AdModel extends BaseModel
{
    protected string $table = 'ads';

    /**
     * Return active ads for a given target (club_panel, member_portal, public),
     * filtered by audience targeting added in migration 032.
     *
     * Audience semantics:
     *   audience='all'    → always matches (subject to global filters)
     *   audience='club'   → matches when ads.club_id = ctx.clubId
     *   audience='sport'  → matches when ads.sport_id = ctx.sportId
     *   audience='member' → matches when ads.member_id = ctx.memberId
     *   audience='plan'   → matches when plan_min IS NULL OR plan_min = planCode
     *                        (caller passes current plan code; plan ordering
     *                        is caller's responsibility)
     *
     * Backward compat: existing callers passing only $target+$clubId still
     * see all 'all'-audience rows scoped per club just like before.
     */
    public function activeForTarget(
        string  $target,
        ?int    $clubId   = null,
        ?int    $memberId = null,
        ?int    $sportId  = null,
        ?string $planCode = null
    ): array {
        $sql = "SELECT * FROM ads
                WHERE is_active = 1
                  AND target = ?
                  AND (start_date IS NULL OR start_date <= CURDATE())
                  AND (end_date   IS NULL OR end_date   >= CURDATE())
                  AND (
                          audience_type = 'all'
                          OR (audience_type = 'club'   AND club_id   = ?)
                          OR (audience_type = 'sport'  AND sport_id  = ?)
                          OR (audience_type = 'member' AND member_id = ?)
                          OR (audience_type = 'plan'   AND (plan_min IS NULL OR plan_min = ?))
                      )";
        $params = [$target, $clubId, $sportId, $memberId, $planCode];

        if ($clubId !== null) {
            $sql .= " AND (audience_type != 'all' OR club_id IS NULL OR club_id = ?)";
            $params[] = $clubId;
        } else {
            $sql .= " AND (audience_type != 'all' OR club_id IS NULL)";
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
