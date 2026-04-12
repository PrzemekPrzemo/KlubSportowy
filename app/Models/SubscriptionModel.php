<?php

namespace App\Models;

class SubscriptionModel extends BaseModel
{
    protected string $table = 'club_subscriptions';

    public function findForClub(int $clubId): ?array
    {
        $sql = "SELECT cs.*, p.code AS plan_code, p.name AS plan_name,
                       p.max_members, p.max_sports, p.features
                FROM club_subscriptions cs
                JOIN subscription_plans p ON p.id = cs.plan_id
                WHERE cs.club_id = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function isExpired(int $clubId): bool
    {
        $sub = $this->findForClub($clubId);
        if ($sub === null) return false;
        if ($sub['status'] === 'expired' || $sub['status'] === 'cancelled') return true;
        return strtotime($sub['valid_until']) < strtotime(date('Y-m-d'));
    }

    public function isOverMemberLimit(int $clubId): bool
    {
        $sub = $this->findForClub($clubId);
        if ($sub === null || $sub['max_members'] === null) return false;

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM members WHERE club_id = ? AND status = 'aktywny'"
        );
        $stmt->execute([$clubId]);
        $count = (int)$stmt->fetchColumn();
        return $count >= (int)$sub['max_members'];
    }

    public function listPlans(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order"
        );
        return $stmt->fetchAll();
    }

    /**
     * Check if club is over member limit, respecting admin overrides.
     * max_members_override takes precedence over plan.max_members.
     */
    public function isOverMemberLimitWithOverride(int $clubId): bool
    {
        $sub = $this->findForClub($clubId);
        if ($sub === null) {
            return false;
        }

        // Override takes precedence
        $limit = $sub['max_members_override'] ?? null;
        if ($limit === null) {
            $limit = $sub['max_members'] ?? null;
        }

        if ($limit === null) {
            return false; // unlimited
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM members WHERE club_id = ? AND status = 'aktywny'"
        );
        $stmt->execute([$clubId]);
        $count = (int)$stmt->fetchColumn();

        return $count >= (int)$limit;
    }
}
