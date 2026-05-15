<?php

namespace App\Models;

/**
 * Audit log poszczególnych chargeów subskrypcji (Stripe invoice / P24 recurring charge).
 * Pozwala wyświetlić timeline w admin UI i policzyć failed_charges_count.
 */
class SubscriptionChargeModel extends ClubScopedModel
{
    protected string $table = 'subscription_charges';

    /**
     * Timeline chargeów dla danej subskrypcji.
     */
    public function forSubscription(int $subscriptionId): array
    {
        $clubId = $this->clubId();
        if ($clubId === null) return [];

        $stmt = $this->db->prepare(
            "SELECT * FROM subscription_charges
             WHERE club_id = ? AND subscription_id = ?
             ORDER BY created_at DESC"
        );
        $stmt->execute([$clubId, $subscriptionId]);
        return $stmt->fetchAll();
    }

    /**
     * INSERT charge — bypassuje scope (webhook może nie mieć ClubContext setted).
     * club_id musi być explicit w $data.
     */
    public function insertUnscoped(array $data): int
    {
        if (empty($data['club_id'])) {
            throw new \RuntimeException('club_id is required for SubscriptionChargeModel.insertUnscoped');
        }
        $cols = array_keys($data);
        $placeholders = array_fill(0, count($cols), '?');
        $sql = "INSERT INTO subscription_charges (`" . implode('`,`', $cols) . "`) VALUES ("
             . implode(',', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    /**
     * Znajdź charge po external_payment_id (Stripe invoice ID / pi_id).
     */
    public function findByExternalId(string $externalId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM subscription_charges WHERE external_payment_id = ? OR external_invoice_id = ? LIMIT 1"
        );
        $stmt->execute([$externalId, $externalId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
