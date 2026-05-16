<?php

namespace App\Models;

class PaymentModel extends ClubScopedModel
{
    protected string $table = 'payments';

    public function listForClub(?int $memberId = null, ?int $year = null, int $page = 1, int $perPage = 30): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT p.*, m.first_name, m.last_name, m.member_number,
                          fr.name AS fee_name, s.name AS sport_name
                   FROM payments p
                   JOIN members m ON m.id = p.member_id
                   LEFT JOIN fee_rates fr ON fr.id = p.fee_rate_id
                   LEFT JOIN sports s     ON s.id = p.sport_id
                   WHERE 1=1";
        $params = [];

        if ($clubId !== null) {
            $sql     .= " AND p.club_id = ?";
            $params[] = $clubId;
        }
        if ($memberId !== null) {
            $sql     .= " AND p.member_id = ?";
            $params[] = $memberId;
        }
        if ($year !== null) {
            $sql     .= " AND p.period_year = ?";
            $params[] = $year;
        }

        $sql .= " ORDER BY p.payment_date DESC, p.id DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function totalForClubThisYear(): float
    {
        $clubId = $this->clubId();
        $sql    = "SELECT COALESCE(SUM(amount), 0) FROM payments
                   WHERE period_year = YEAR(CURDATE())";
        $params = [];
        if ($clubId !== null) {
            $sql .= " AND club_id = ?";
            $params[] = $clubId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }

    public function totalForMemberThisYear(int $memberId): float
    {
        $sql  = "SELECT COALESCE(SUM(amount), 0) FROM payments
                 WHERE member_id = ? AND period_year = YEAR(CURDATE())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$memberId]);
        return (float)$stmt->fetchColumn();
    }
}
