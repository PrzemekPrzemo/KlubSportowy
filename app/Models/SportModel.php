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

    /** Sporty zapięte do klubu (z JOIN club_sports). */
    public function listForClub(int $clubId): array
    {
        $sql = "SELECT cs.id AS club_sport_id, cs.is_active AS cs_active, cs.name AS cs_name,
                       cs.federation_club_id,
                       s.id, s.`key`, s.name, s.icon, s.color, s.team_sport,
                       f.code AS federation_code
                FROM club_sports cs
                JOIN sports s ON s.id = cs.sport_id
                LEFT JOIN federations f ON f.id = s.federation_id
                WHERE cs.club_id = ?
                ORDER BY s.sort_order, s.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
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
