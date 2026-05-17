<?php

namespace App\Models;

/**
 * Typy karnetow studio (sprzedaz / katalog).
 */
class StudioPassTypeModel extends ClubScopedModel
{
    protected string $table = 'studio_pass_types';

    public const TYPES = ['single','multi_class','unlimited_period'];

    public function listActive(?string $sportKey = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT * FROM studio_pass_types WHERE active = 1";
        $params = [];
        if ($clubId !== null) {
            $sql .= " AND club_id = ?";
            $params[] = $clubId;
        }
        if ($sportKey !== null) {
            $sql .= " AND (sport_key IS NULL OR sport_key = ?)";
            $params[] = $sportKey;
        }
        $sql .= " ORDER BY sort_order ASC, price_cents ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listAll(?string $sportKey = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT * FROM studio_pass_types WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND club_id = ?"; $params[] = $clubId; }
        if ($sportKey !== null) {
            $sql .= " AND (sport_key IS NULL OR sport_key = ?)";
            $params[] = $sportKey;
        }
        $sql .= " ORDER BY active DESC, sort_order ASC, price_cents ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
