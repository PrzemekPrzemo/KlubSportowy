<?php

namespace App\Models;

/**
 * Należności (payment_dues) — kto powinien zapłacić ile za jaki okres.
 *
 * Generowane:
 *   - automatycznie z member_fee_assignments (P.4 cron lub button)
 *   - ręcznie przez admin (np. opłata za obóz / wpisowe)
 *
 * Status:
 *   - pending   — oczekująca, due_date nieprzeterminowana
 *   - partial   — częściowo zapłacona (paid_amount < net_amount)
 *   - paid      — opłacona w całości
 *   - overdue   — przeterminowana (CRON aktualizuje)
 *   - waived    — odpuszczona (np. zwolnienie z opłat)
 *   - cancelled — anulowana (np. członek odszedł)
 *
 * Każda należność ma `discount_breakdown` JSON z listą zastosowanych zniżek.
 */
class PaymentDueModel extends ClubScopedModel
{
    protected string $table = 'payment_dues';

    public static array $STATUSES = [
        'pending'   => 'Oczekująca',
        'partial'   => 'Częściowo opłacona',
        'paid'      => 'Opłacona',
        'overdue'   => 'Przeterminowana',
        'waived'    => 'Zwolniona',
        'cancelled' => 'Anulowana',
    ];

    /**
     * Lista należności klubu z opcjonalnymi filtrami.
     */
    public function listForClub(array $filters = []): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT pd.*,
                       m.first_name, m.last_name, m.member_number,
                       fr.name AS rate_name, fr.fee_type AS rate_fee_type
                FROM payment_dues pd
                JOIN members m   ON m.id = pd.member_id
                LEFT JOIN fee_rates fr ON fr.id = pd.fee_rate_id
                WHERE pd.club_id = ?";
        $params = [$clubId];

        if (!empty($filters['status'])) {
            $sql .= " AND pd.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['member_id'])) {
            $sql .= " AND pd.member_id = ?";
            $params[] = (int)$filters['member_id'];
        }
        if (!empty($filters['period_year'])) {
            $sql .= " AND pd.period_year = ?";
            $params[] = (int)$filters['period_year'];
        }
        if (!empty($filters['period_month'])) {
            $sql .= " AND pd.period_month = ?";
            $params[] = (int)$filters['period_month'];
        }
        if (!empty($filters['overdue_only'])) {
            $sql .= " AND pd.status IN ('pending','partial') AND pd.due_date < CURDATE()";
        }

        $sql .= " ORDER BY pd.due_date DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Należności dla konkretnego członka (do portalu zawodnika).
     */
    public function forMember(int $memberId, ?string $status = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT pd.*, fr.name AS rate_name, fr.fee_type AS rate_fee_type
                FROM payment_dues pd
                LEFT JOIN fee_rates fr ON fr.id = pd.fee_rate_id
                WHERE pd.club_id = ? AND pd.member_id = ?";
        $params = [$clubId, $memberId];
        if ($status !== null) {
            $sql .= " AND pd.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY pd.due_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Sumaryczne saldo zaległości klubu (overdue + pending unpaid).
     */
    public function clubBalance(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN status='paid' THEN net_amount ELSE 0 END), 0) AS total_paid,
                COALESCE(SUM(CASE WHEN status IN ('pending','partial') THEN (net_amount - paid_amount) ELSE 0 END), 0) AS total_outstanding,
                COALESCE(SUM(CASE WHEN status='overdue' OR (status IN ('pending','partial') AND due_date < CURDATE())
                                  THEN (net_amount - paid_amount) ELSE 0 END), 0) AS total_overdue,
                COUNT(CASE WHEN status='paid' THEN 1 END) AS count_paid,
                COUNT(CASE WHEN status IN ('pending','partial') THEN 1 END) AS count_outstanding,
                COUNT(CASE WHEN status='overdue' OR (status IN ('pending','partial') AND due_date < CURDATE())
                            THEN 1 END) AS count_overdue
             FROM payment_dues
             WHERE club_id = ?"
        );
        $stmt->execute([$clubId]);
        $row = $stmt->fetch() ?: [];
        // Cast all to numeric
        return array_map(fn($v) => is_numeric($v) ? (float)$v : $v, $row);
    }

    /**
     * Aktualizuj overdue: ustaw status='overdue' dla wszystkich pending/partial
     * po due_date. Wywoływane cronem co noc (lub ręcznie z admin panelu).
     */
    public function refreshOverdue(): int
    {
        $clubId = $this->clubId();
        $sql = "UPDATE payment_dues
                SET status = 'overdue'
                WHERE club_id = ?
                  AND status IN ('pending','partial')
                  AND due_date < CURDATE()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId]);
        return $stmt->rowCount();
    }

    /**
     * Po wpłacie (payment): dolicz paid_amount do due i zaktualizuj status.
     */
    public function applyPayment(int $dueId, float $paidAmount): bool
    {
        $clubId = $this->clubId();
        $due = $this->findById($dueId);
        if (!$due) return false;

        $newPaid = (float)$due['paid_amount'] + $paidAmount;
        $netAmount = (float)$due['net_amount'];

        $newStatus = match (true) {
            $newPaid >= $netAmount => 'paid',
            $newPaid > 0           => 'partial',
            default                => 'pending',
        };

        $stmt = $this->db->prepare(
            "UPDATE payment_dues SET paid_amount = ?, status = ? WHERE id = ? AND club_id = ?"
        );
        return $stmt->execute([$newPaid, $newStatus, $dueId, $clubId]);
    }
}
