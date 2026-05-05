<?php

namespace App\Models;

/**
 * Subskrypcja opłat — przypisanie polityki opłat (fee_rate) do zawodnika
 * z opcjonalnymi zniżkami (M:N przez member_fee_assignment_discounts).
 *
 * Status:
 *   - active     — aktywna, generuje należności
 *   - suspended  — wstrzymana (np. urlop), bez należności tymczasowo
 *   - ended      — zakończona (po valid_to lub ręcznie)
 *
 * Pełna izolacja per klub.
 */
class MemberFeeAssignmentModel extends ClubScopedModel
{
    protected string $table = 'member_fee_assignments';

    public static array $STATUSES = [
        'active'    => 'Aktywna',
        'suspended' => 'Wstrzymana',
        'ended'     => 'Zakończona',
    ];

    /**
     * Lista wszystkich assignment'ów klubu z join'em do member i fee_rate.
     */
    public function listForClub(?int $memberId = null, ?string $status = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT mfa.*,
                       m.first_name, m.last_name, m.member_number,
                       fr.name AS rate_name, fr.amount AS rate_amount, fr.period AS rate_period,
                       fr.fee_type AS rate_fee_type
                FROM member_fee_assignments mfa
                JOIN members m   ON m.id = mfa.member_id
                JOIN fee_rates fr ON fr.id = mfa.fee_rate_id
                WHERE mfa.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) {
            $sql .= " AND mfa.member_id = ?";
            $params[] = $memberId;
        }
        if ($status !== null) {
            $sql .= " AND mfa.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY m.last_name, m.first_name, mfa.valid_from DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Aktywne assignment'y dla danego członka na dziś.
     */
    public function activeForMember(int $memberId, string $onDate): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT mfa.*, fr.name AS rate_name, fr.amount AS rate_amount,
                    fr.period AS rate_period, fr.fee_type AS rate_fee_type,
                    fr.sport_id, fr.class_id
             FROM member_fee_assignments mfa
             JOIN fee_rates fr ON fr.id = mfa.fee_rate_id
             WHERE mfa.club_id = ?
               AND mfa.member_id = ?
               AND mfa.status = 'active'
               AND mfa.valid_from <= ?
               AND (mfa.valid_to IS NULL OR mfa.valid_to >= ?)"
        );
        $stmt->execute([$clubId, $memberId, $onDate, $onDate]);
        return $stmt->fetchAll();
    }

    /**
     * Pobierz przypisane zniżki dla assignment'u.
     */
    public function discountsForAssignment(int $assignmentId): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT fd.*
             FROM member_fee_assignment_discounts mfad
             JOIN fee_discounts fd ON fd.id = mfad.discount_id
             JOIN member_fee_assignments mfa ON mfa.id = mfad.assignment_id
             WHERE mfa.club_id = ? AND mfad.assignment_id = ?
             ORDER BY fd.name"
        );
        $stmt->execute([$clubId, $assignmentId]);
        return $stmt->fetchAll();
    }

    /**
     * Dołącz zniżkę do assignment'u (M:N).
     * Sprawdza że oba należą do tego samego klubu (defensywnie).
     */
    public function attachDiscount(int $assignmentId, int $discountId, ?int $appliedBy = null): bool
    {
        $clubId = $this->clubId();
        // Defensywne sprawdzenie: oba rekordy w tym samym klubie
        $stmt = $this->db->prepare(
            "SELECT
                (SELECT COUNT(*) FROM member_fee_assignments WHERE id = ? AND club_id = ?) AS a,
                (SELECT COUNT(*) FROM fee_discounts          WHERE id = ? AND club_id = ?) AS d"
        );
        $stmt->execute([$assignmentId, $clubId, $discountId, $clubId]);
        $r = $stmt->fetch();
        if (!$r || (int)$r['a'] === 0 || (int)$r['d'] === 0) return false;

        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO member_fee_assignment_discounts (assignment_id, discount_id, applied_by)
             VALUES (?, ?, ?)"
        );
        return $stmt->execute([$assignmentId, $discountId, $appliedBy]);
    }

    public function detachDiscount(int $assignmentId, int $discountId): bool
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "DELETE mfad FROM member_fee_assignment_discounts mfad
             JOIN member_fee_assignments mfa ON mfa.id = mfad.assignment_id
             WHERE mfa.club_id = ? AND mfad.assignment_id = ? AND mfad.discount_id = ?"
        );
        return $stmt->execute([$clubId, $assignmentId, $discountId]);
    }

    /**
     * Helper: oblicz net amount dla assignment'u (gross - sum stackable discounts).
     */
    public static function calculateNet(float $grossAmount, array $discounts): array
    {
        $totalDiscount     = 0.0;
        $breakdown         = [];
        $hasNonStackable   = false;
        foreach ($discounts as $d) {
            // Jeśli wcześniej zastosowano non-stackable — nie dokładamy nic więcej.
            if ($hasNonStackable) continue;

            $amt = FeeDiscountModel::calculateDiscountAmount($d, $grossAmount - $totalDiscount);
            $totalDiscount += $amt;
            $breakdown[] = [
                'discount_id' => (int)$d['id'],
                'code'        => $d['code'] ?? '',
                'name'        => $d['name'] ?? '',
                'amount'      => $amt,
            ];
            if (!($d['is_stackable'] ?? 1)) {
                $hasNonStackable = true;
            }
        }
        $net = (float)max(0, round($grossAmount - $totalDiscount, 2));
        return [
            'gross_amount'    => round($grossAmount, 2),
            'discount_amount' => round($totalDiscount, 2),
            'net_amount'      => $net,
            'breakdown'       => $breakdown,
        ];
    }
}
