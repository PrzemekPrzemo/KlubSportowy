<?php

namespace App\Sports\Shooting\Models;

use App\Models\ClubScopedModel;

class AmmoStockModel extends ClubScopedModel
{
    protected string $table = 'ammo_stock';

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT a.*, (a.min_stock IS NOT NULL AND a.quantity <= a.min_stock) AS low_stock
                FROM ammo_stock a";
        $params = [];
        if ($clubId !== null) {
            $sql .= " WHERE a.club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY a.caliber";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function adjust(int $ammoId, int $quantity, string $direction, ?int $memberId = null, ?string $notes = null, ?int $userId = null): void
    {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $delta = match ($direction) {
                'przyjecie', 'korekta' => $quantity,
                'wydanie' => -$quantity,
                default => 0,
            };
            if ($direction === 'korekta') {
                // set absolute value
                $stmt = $db->prepare("UPDATE ammo_stock SET quantity = ? WHERE id = ?");
                $stmt->execute([$quantity, $ammoId]);
            } else {
                $stmt = $db->prepare("UPDATE ammo_stock SET quantity = GREATEST(0, quantity + ?) WHERE id = ?");
                $stmt->execute([$delta, $ammoId]);
            }

            $stmt = $db->prepare(
                "INSERT INTO ammo_transactions (ammo_id, member_id, direction, quantity, notes, created_by)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$ammoId, $memberId, $direction, $quantity, $notes, $userId]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function transactionsForAmmo(int $ammoId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT at.*, m.first_name, m.last_name, u.full_name AS created_by_name
             FROM ammo_transactions at
             LEFT JOIN members m ON m.id = at.member_id
             LEFT JOIN users u  ON u.id = at.created_by
             WHERE at.ammo_id = ?
             ORDER BY at.created_at DESC
             LIMIT " . (int)$limit
        );
        $stmt->execute([$ammoId]);
        return $stmt->fetchAll();
    }
}
