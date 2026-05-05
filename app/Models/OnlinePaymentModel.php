<?php

namespace App\Models;

class OnlinePaymentModel extends ClubScopedModel
{
    protected string $table = 'online_payments';

    public function createPayment(array $data): int
    {
        return $this->insert($data);
    }

    public function findByProviderId(string $providerId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM online_payments WHERE provider_id = ? LIMIT 1");
        $stmt->execute([$providerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markPaid(int $id, string $providerId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE online_payments SET status = 'paid', paid_at = NOW(), provider_id = ? WHERE id = ?"
        );
        $stmt->execute([$providerId, $id]);
    }

    public function markFailed(int $id, string $reason = ''): void
    {
        $stmt = $this->db->prepare("UPDATE online_payments SET status = 'failed' WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function forMember(int $memberId, int $page = 1, int $perPage = 20): array
    {
        $sql = "SELECT op.*, fr.name AS fee_name
                FROM online_payments op
                LEFT JOIN fee_rates fr ON fr.id = op.fee_rate_id
                WHERE op.member_id = ?
                ORDER BY op.created_at DESC";
        return $this->paginate($sql, [$memberId], $page, $perPage);
    }

    public function pendingForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT op.*, fr.name AS fee_name
             FROM online_payments op
             LEFT JOIN fee_rates fr ON fr.id = op.fee_rate_id
             WHERE op.member_id = ? AND op.status = 'pending'
             ORDER BY op.created_at DESC"
        );
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    /**
     * Po opłaceniu — automatyczne zaksięgowanie w tabeli payments.
     */
    public function bookToPayments(int $onlinePaymentId): ?int
    {
        $op = $this->findById($onlinePaymentId);
        if (!$op || $op['status'] !== 'paid') return null;

        $stmt = $this->db->prepare(
            "INSERT INTO payments (club_id, member_id, fee_rate_id, amount, payment_date,
                                   period_year, period_month, method, reference, notes)
             VALUES (?, ?, ?, ?, CURDATE(), ?, ?, 'karta', ?, ?)"
        );
        $stmt->execute([
            $op['club_id'], $op['member_id'], $op['fee_rate_id'],
            $op['amount'], $op['period_year'], $op['period_month'],
            'online#' . ($op['provider_id'] ?? $op['id']),
            'Płatność online: ' . $op['description'],
        ]);
        $paymentId = (int)$this->db->lastInsertId();

        // U.1 — auto-naliczanie prowizji trenerów
        try {
            \App\Helpers\CommissionCalculator::accrueForPayment([
                'id'           => $paymentId,
                'club_id'      => $op['club_id'],
                'member_id'    => $op['member_id'],
                'sport_id'     => null,
                'amount'       => $op['amount'],
                'payment_date' => date('Y-m-d'),
                'period_year'  => $op['period_year'],
                'period_month' => $op['period_month'],
                'fee_rate_id'  => $op['fee_rate_id'],
            ]);
        } catch (\Throwable $e) {
            error_log('CommissionCalculator (online) failed: ' . $e->getMessage());
        }

        return $paymentId;
    }
}
