<?php

namespace App\Models;

class ShopOrderModel extends ClubScopedModel
{
    protected string $table = 'shop_orders';

    /**
     * List orders with pagination.
     */
    public function listOrders(int $page = 1, int $perPage = 20): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE 1=1";
        $params = [];

        $clubId = $this->clubId();
        if ($clubId !== null) {
            $sql .= " AND club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY created_at DESC";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Get order with items.
     */
    public function findWithItems(int $id): ?array
    {
        $order = $this->findById($id);
        if (!$order) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT oi.*, sp.name AS product_name, sp.image_path AS product_image
             FROM shop_order_items oi
             JOIN shop_products sp ON sp.id = oi.product_id
             WHERE oi.order_id = ?"
        );
        $stmt->execute([$id]);
        $order['items'] = $stmt->fetchAll();

        return $order;
    }

    /**
     * Update order status.
     */
    public function updateStatus(int $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }
}
