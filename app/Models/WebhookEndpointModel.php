<?php

namespace App\Models;

class WebhookEndpointModel extends ClubScopedModel
{
    protected string $table = 'webhook_endpoints';

    /**
     * Aktywne endpointy dla klubu, które nasłuchują na dany event.
     */
    public function activeForEvent(int $clubId, string $event): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE club_id = ? AND is_active = 1
               AND JSON_CONTAINS(events, ?, '$')
             ORDER BY id ASC"
        );
        $stmt->execute([$clubId, json_encode($event)]);
        return $stmt->fetchAll();
    }

    /**
     * Ostatnie logi dla endpointów danego klubu.
     */
    public function recentLogs(int $clubId, int $limit = 30): array
    {
        $stmt = $this->db->prepare(
            "SELECT wl.*, we.url AS endpoint_url
             FROM webhook_log wl
             JOIN webhook_endpoints we ON we.id = wl.endpoint_id
             WHERE we.club_id = ?
             ORDER BY wl.sent_at DESC
             LIMIT " . (int)$limit
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
