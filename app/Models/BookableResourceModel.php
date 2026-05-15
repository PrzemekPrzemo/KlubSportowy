<?php

namespace App\Models;

/**
 * Zasoby klubu mozliwe do zarezerwowania (sala, kort, sprzet, boisko, tor basenu).
 * Scoped per klub przez ClubScopedModel.
 */
class BookableResourceModel extends ClubScopedModel
{
    protected string $table = 'bookable_resources';

    /**
     * Aktywne zasoby klubu (is_active=1), posortowane po nazwie.
     */
    public function listActive(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT * FROM bookable_resources WHERE is_active = 1";
        $params = [];
        if ($clubId !== null) {
            $sql .= " AND club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Wszystkie zasoby klubu (also nieaktywne) dla widoku admina.
     */
    public function listAll(): array
    {
        return $this->findAll('name', 'ASC');
    }

    /**
     * Czy klub ma zdefiniowane aktywne zasoby (do warunkowego nav w portalu).
     */
    public function hasActiveForClub(int $clubId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM bookable_resources WHERE club_id = ? AND is_active = 1 LIMIT 1"
        );
        $stmt->execute([$clubId]);
        return (bool)$stmt->fetchColumn();
    }
}
