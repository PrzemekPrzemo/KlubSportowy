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

    public function isOverSportLimit(int $clubId): bool
    {
        $sub = $this->findForClub($clubId);
        if ($sub === null || $sub['max_sports'] === null) return false;

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM club_sports WHERE club_id = ? AND is_active = 1"
        );
        $stmt->execute([$clubId]);
        $count = (int)$stmt->fetchColumn();
        return $count >= (int)$sub['max_sports'];
    }

    public function sportLimitInfo(int $clubId): array
    {
        $sub = $this->findForClub($clubId);
        if ($sub === null) {
            return ['limit' => null, 'used' => 0, 'remaining' => null];
        }
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM club_sports WHERE club_id = ? AND is_active = 1"
        );
        $stmt->execute([$clubId]);
        $used  = (int)$stmt->fetchColumn();
        $limit = $sub['max_sports'] !== null ? (int)$sub['max_sports'] : null;
        return [
            'limit'     => $limit,
            'used'      => $used,
            'remaining' => $limit !== null ? max(0, $limit - $used) : null,
        ];
    }

    public function listPlans(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order"
        );
        return $stmt->fetchAll();
    }

    /**
     * Q.2 — Efektywne limity klubu = limity planu + boost-y z aktywnych addonow.
     *
     * Zwraca:
     *   ['max_members' => int|null, 'max_sports' => int|null,
     *    'plan_max_members' => int|null, 'plan_max_sports' => int|null,
     *    'addon_members_boost' => int, 'addon_sports_boost' => int]
     *
     * NULL w max_X = bez limitu (plan Enterprise/Federacja).
     */
    public function effectiveLimits(int $clubId): array
    {
        $sub = $this->findForClub($clubId);
        $planMembers = $sub['max_members'] ?? null;
        $planSports  = $sub['max_sports']  ?? null;

        // Override z admin (max_members_override) ma priorytet nad planem
        if (!empty($sub['max_members_override'])) {
            $planMembers = (int)$sub['max_members_override'];
        }

        $boostMembers = 0;
        $boostSports  = 0;
        try {
            $stmt = $this->db->prepare(
                "SELECT ac.boost_field, SUM(ca.quantity * COALESCE(ac.boost_amount, 0)) AS total_boost
                   FROM club_addons ca
                   JOIN addon_catalog ac ON ac.id = ca.addon_id
                  WHERE ca.club_id = ?
                    AND ca.status = 'active'
                    AND (ca.valid_until IS NULL OR ca.valid_until >= CURDATE())
                    AND ac.boost_field IS NOT NULL
                  GROUP BY ac.boost_field"
            );
            $stmt->execute([$clubId]);
            foreach ($stmt->fetchAll() as $row) {
                if ($row['boost_field'] === 'max_members') $boostMembers = (int)$row['total_boost'];
                if ($row['boost_field'] === 'max_sports')  $boostSports  = (int)$row['total_boost'];
            }
        } catch (\Throwable) {
            // Tabele addon_catalog/club_addons mogą nie istnieć w starszych
            // instalacjach — zwróć tylko plan limits (defensywne)
        }

        return [
            'plan_max_members'    => $planMembers,
            'plan_max_sports'     => $planSports,
            'addon_members_boost' => $boostMembers,
            'addon_sports_boost'  => $boostSports,
            'max_members'         => $planMembers === null ? null : ($planMembers + $boostMembers),
            'max_sports'          => $planSports  === null ? null : ($planSports  + $boostSports),
        ];
    }

    /**
     * Q.2 — Lista aktywnych addonow klubu (dla UI /club/subscription).
     */
    public function activeAddonsForClub(int $clubId): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT ca.*, ac.code AS addon_code, ac.name AS addon_name,
                        ac.description, ac.category, ac.boost_field, ac.boost_amount
                   FROM club_addons ca
                   JOIN addon_catalog ac ON ac.id = ca.addon_id
                  WHERE ca.club_id = ?
                    AND ca.status = 'active'
                    AND (ca.valid_until IS NULL OR ca.valid_until >= CURDATE())
                  ORDER BY ac.sort_order ASC"
            );
            $stmt->execute([$clubId]);
            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
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
