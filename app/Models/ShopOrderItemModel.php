<?php

namespace App\Models;

class ShopOrderItemModel extends BaseModel
{
    protected string $table = 'shop_order_items';

    /**
     * Get items for a given order.
     */
    public function findByOrder(int $orderId): array
    {
        $stmt = $this->db->prepare(
            "SELECT oi.*, sp.name AS product_name
             FROM `{$this->table}` oi
             JOIN shop_products sp ON sp.id = oi.product_id
             WHERE oi.order_id = ?"
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
}
