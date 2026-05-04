<?php

namespace App\Models;

class SportModel extends BaseModel
{
    protected string $table = 'sports';

    public function listActive(): array
    {
        $sql = "SELECT s.*, f.code AS federation_code, f.name AS federation_name
                FROM sports s
                LEFT JOIN federations f ON f.id = s.federation_id
                WHERE s.is_active = 1
                ORDER BY s.sort_order, s.name";
        return $this->db->query($sql)->fetchAll();
    }

    public function findByKey(string $key): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `sports` WHERE `key` = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Sporty zapięte do klubu (z JOIN club_sports). $onlyActive=true → tylko is_active=1. */
    public function listForClub(int $clubId, bool $onlyActive = false): array
    {
        $sql = "SELECT cs.id AS club_sport_id, cs.is_active AS cs_active, cs.name AS cs_name,
                       cs.federation_club_id,
                       s.id, s.`key`, s.name, s.icon, s.color, s.team_sport,
                       f.code AS federation_code
                FROM club_sports cs
                JOIN sports s ON s.id = cs.sport_id
                LEFT JOIN federations f ON f.id = s.federation_id
                WHERE cs.club_id = ?";
        if ($onlyActive) {
            $sql .= " AND cs.is_active = 1";
        }
        $sql .= " ORDER BY s.sort_order, s.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    /** Skrót — aktywne sporty klubu (keys only) do filtrowania UI/routingu. */
    public function activeKeysForClub(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.`key` FROM club_sports cs
             JOIN sports s ON s.id = cs.sport_id
             WHERE cs.club_id = ? AND cs.is_active = 1"
        );
        $stmt->execute([$clubId]);
        return array_column($stmt->fetchAll(), 'key');
    }

    /** Sporty NIE zapięte do danego klubu (do dodania). */
    public function listAvailableForClub(int $clubId): array
    {
        $sql = "SELECT s.*, f.code AS federation_code
                FROM sports s
                LEFT JOIN federations f ON f.id = s.federation_id
                WHERE s.is_active = 1
                  AND s.id NOT IN (SELECT sport_id FROM club_sports WHERE club_id = ?)
                ORDER BY s.sort_order, s.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
