<?php

namespace App\Models;

class ShopProductModel extends ClubScopedModel
{
    protected string $table = 'shop_products';

    /**
     * List active products for the current club.
     */
    public function findActive(string $orderBy = 'name', string $dir = 'ASC'): array
    {
        $clubId = $this->clubId();
        $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $orderBy = preg_replace('/[^a-zA-Z0-9_]/', '', $orderBy);

        $sql = "SELECT * FROM `{$this->table}` WHERE is_active = 1";
        $params = [];
        if ($clubId !== null) {
            $sql .= " AND club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY `{$orderBy}` {$dir}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * List active products for a specific club (public catalog).
     */
    public function findActiveForClub(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE club_id = ? AND is_active = 1 ORDER BY category, name"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    /**
     * Find a product by ID for a specific club (public context).
     */
    public function findByIdForClub(int $id, int $clubId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE id = ? AND club_id = ? AND is_active = 1"
        );
        $stmt->execute([$id, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Decrease stock after order.
     */
    public function decreaseStock(int $id, int $quantity): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET stock = GREATEST(0, stock - ?) WHERE id = ?"
        );
        return $stmt->execute([$quantity, $id]);
    }
}
