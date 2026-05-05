<?php

namespace App\Models;

/**
 * Audit log naliczonych prowizji trenerów per wpłata zawodnika.
 *
 * Wpis powstaje przy zaksięgowaniu wpłaty (CommissionCalculator::accrue).
 * Status:
 *   - accrued    — naliczona, ale jeszcze nie wypłacona trenerowi
 *   - paid_out   — wypłacona (klub zaznacza po przelewie)
 *   - cancelled  — anulowana (np. zwrot wpłaty / zniżka post-factum)
 *
 * UNIQUE (payment_id, trainer_user_id) — idempotencja: ponowny przebieg
 * cron-a / re-import wpłat nie zduplikuje wpisów.
 */
class TrainerCommissionLogModel extends ClubScopedModel
{
    protected string $table = 'trainer_commissions_log';

    public const STATUS_ACCRUED   = 'accrued';
    public const STATUS_PAID_OUT  = 'paid_out';
    public const STATUS_CANCELLED = 'cancelled';

    public static array $STATUSES = [
        self::STATUS_ACCRUED   => 'Naliczona',
        self::STATUS_PAID_OUT  => 'Wypłacona',
        self::STATUS_CANCELLED => 'Anulowana',
    ];

    /**
     * Wpis już istnieje dla pary (payment_id, trainer_user_id)?
     * Używane przez CommissionCalculator do idempotencji.
     */
    public function existsForPaymentAndTrainer(int $paymentId, int $trainerId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM trainer_commissions_log
              WHERE payment_id = ? AND trainer_user_id = ? LIMIT 1"
        );
        $stmt->execute([$paymentId, $trainerId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Lista prowizji klubu z filtrami (period, trener, status).
     */
    public function listForClub(array $filters = []): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT tcl.*,
                       u.username  AS trainer_username,
                       u.full_name AS trainer_name,
                       m.first_name, m.last_name, m.member_number,
                       p.payment_date, p.amount AS payment_total,
                       fr.fee_type AS fee_type
                FROM trainer_commissions_log tcl
                JOIN users     u ON u.id = tcl.trainer_user_id
                JOIN members   m ON m.id = tcl.member_id
                JOIN payments  p ON p.id = tcl.payment_id
                LEFT JOIN fee_rates fr ON fr.id = p.fee_rate_id
                WHERE tcl.club_id = ?";
        $params = [$clubId];

        if (!empty($filters['trainer_user_id'])) {
            $sql .= " AND tcl.trainer_user_id = ?";
            $params[] = (int)$filters['trainer_user_id'];
        }
        if (!empty($filters['period_year'])) {
            $sql .= " AND tcl.period_year = ?";
            $params[] = (int)$filters['period_year'];
        }
        if (!empty($filters['period_month'])) {
            $sql .= " AND tcl.period_month = ?";
            $params[] = (int)$filters['period_month'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND tcl.status = ?";
            $params[] = $filters['status'];
        }
        $sql .= " ORDER BY tcl.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Agregacja per trener za dany okres (rok lub rok+miesiąc).
     * Używane do generowania raportów / list wypłat.
     */
    public function aggregateByTrainer(int $year, ?int $month = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT tcl.trainer_user_id,
                       u.username, u.full_name,
                       COUNT(*)                                                          AS items,
                       SUM(tcl.commission_amount)                                        AS total,
                       SUM(CASE WHEN tcl.status='accrued'  THEN tcl.commission_amount ELSE 0 END) AS accrued,
                       SUM(CASE WHEN tcl.status='paid_out' THEN tcl.commission_amount ELSE 0 END) AS paid_out
                FROM trainer_commissions_log tcl
                JOIN users u ON u.id = tcl.trainer_user_id
                WHERE tcl.club_id = ? AND tcl.period_year = ?
                  AND tcl.status <> 'cancelled'";
        $params = [$clubId, $year];
        if ($month !== null) {
            $sql .= " AND tcl.period_month = ?";
            $params[] = $month;
        }
        $sql .= " GROUP BY tcl.trainer_user_id, u.username, u.full_name
                  ORDER BY total DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Oznacz pakiet prowizji jako wypłacone (po przelewie do trenera).
     * Zwraca liczbę zaktualizowanych wierszy.
     */
    public function markPaidOut(array $ids): int
    {
        $clubId = $this->clubId();
        $ids = array_filter(array_map('intval', $ids));
        if (empty($ids)) return 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE trainer_commissions_log
                   SET status = 'paid_out', paid_out_at = NOW()
                 WHERE club_id = ? AND status = 'accrued'
                   AND id IN ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId, ...$ids]);
        return $stmt->rowCount();
    }

    /**
     * Cofnij prowizję (np. po zwrocie wpłaty / cancel payment).
     */
    public function cancelByPayment(int $paymentId): int
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "UPDATE trainer_commissions_log
                SET status = 'cancelled'
              WHERE club_id = ? AND payment_id = ? AND status = 'accrued'"
        );
        $stmt->execute([$clubId, $paymentId]);
        return $stmt->rowCount();
    }
}
