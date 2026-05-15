<?php

namespace App\Models;

/**
 * Migracja 081: club_referrals.
 *
 * Pojedyncza para (referrer, referred). UNIQUE uniq_referred zapewnia
 * ze klub moze byc polecony tylko raz w calej platformie.
 */
class ReferralModel extends BaseModel
{
    protected string $table = 'club_referrals';

    public function findByReferred(int $referredClubId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE referred_club_id = ? LIMIT 1"
        );
        $stmt->execute([$referredClubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int,array<string,mixed>> */
    public function listForReferrer(int $referrerClubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, c.name AS referred_club_name, c.city AS referred_club_city
               FROM `{$this->table}` r
               LEFT JOIN clubs c ON c.id = r.referred_club_id
              WHERE r.referrer_club_id = ?
              ORDER BY r.referred_at DESC"
        );
        $stmt->execute([$referrerClubId]);
        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public function listPending(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM `{$this->table}` WHERE status = 'pending' ORDER BY referred_at ASC"
        );
        return $stmt->fetchAll();
    }

    /** @return array<string,int> */
    public function statsForReferrer(int $referrerClubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT status, COUNT(*) AS cnt
               FROM `{$this->table}`
              WHERE referrer_club_id = ?
              GROUP BY status"
        );
        $stmt->execute([$referrerClubId]);
        $out = [
            'total'     => 0,
            'pending'   => 0,
            'qualified' => 0,
            'paid'      => 0,
            'expired'   => 0,
            'cancelled' => 0,
        ];
        foreach ($stmt->fetchAll() as $row) {
            $s = (string)$row['status'];
            $cnt = (int)$row['cnt'];
            if (isset($out[$s])) {
                $out[$s] = $cnt;
            }
            $out['total'] += $cnt;
        }
        return $out;
    }

    /** Sumuje wartosc rewardow ktore klub-referrer dostal (status paid/qualified). */
    public function totalRewardForReferrer(int $referrerClubId): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(reward_value), 0)
               FROM `{$this->table}`
              WHERE referrer_club_id = ?
                AND status IN ('qualified','paid')
                AND reward_applied = 1"
        );
        $stmt->execute([$referrerClubId]);
        return (float)$stmt->fetchColumn();
    }

    /** Admin: lista wszystkich z filtrem statusu. */
    public function listForAdmin(?string $status = null, int $limit = 200): array
    {
        $sql = "SELECT r.*,
                       rc.name AS referrer_name,
                       rd.name AS referred_name
                  FROM `{$this->table}` r
                  LEFT JOIN clubs rc ON rc.id = r.referrer_club_id
                  LEFT JOIN clubs rd ON rd.id = r.referred_club_id";
        $params = [];
        if ($status !== null && $status !== '') {
            $sql .= ' WHERE r.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY r.referred_at DESC LIMIT ' . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function markQualified(int $id, float $rewardValue, string $rewardType): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}`
                SET status = 'qualified',
                    qualified_at = NOW(),
                    reward_value = ?,
                    reward_type  = ?,
                    reward_applied = 1
              WHERE id = ?"
        );
        $stmt->execute([$rewardValue, $rewardType, $id]);
    }

    public function markPaid(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}`
                SET status = 'paid', paid_at = NOW()
              WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
}
