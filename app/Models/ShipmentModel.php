<?php

namespace App\Models;

/**
 * Lista przesyłek klubu (wszyscy providerzy — obecnie tylko InPost).
 *
 * Multi-tenant przez ClubScopedModel. Brak wrażliwych pól — tracking_number
 * i label_url to dane publiczne (klient i tak je dostaje mailowo od InPost).
 */
class ShipmentModel extends ClubScopedModel
{
    protected string $table = 'shipments';

    /**
     * Lista ostatnich przesyłek z opcjonalnym limitem (na index page).
     */
    public function recentForClub(int $limit = 20): array
    {
        $clubId = $this->clubId();
        if ($clubId === null) return [];
        $limit = max(1, min(500, $limit));

        $stmt = $this->db->prepare(
            "SELECT * FROM shipments WHERE club_id = ?
             ORDER BY created_at DESC LIMIT {$limit}"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function findByExternalId(string $externalId): ?array
    {
        $clubId = $this->clubId();
        if ($clubId === null) return null;
        $stmt = $this->db->prepare(
            "SELECT * FROM shipments WHERE club_id = ? AND external_id = ? LIMIT 1"
        );
        $stmt->execute([$clubId, $externalId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
