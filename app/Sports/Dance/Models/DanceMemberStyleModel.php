<?php

namespace App\Sports\Dance\Models;

use App\Models\ClubScopedModel;

/**
 * Style tanca przypisane do zawodnikow (member_id, style_code) + level + opcjonalny partner.
 */
class DanceMemberStyleModel extends ClubScopedModel
{
    protected string $table = 'sport_dance_member_styles';

    public function listForClub(?string $styleCode = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT ms.*,
                       m.first_name, m.last_name, m.member_number,
                       p.first_name AS partner_first, p.last_name AS partner_last,
                       s.display_name AS style_name, s.category AS style_category
                FROM `{$this->table}` ms
                JOIN members m              ON m.id = ms.member_id
                LEFT JOIN members p         ON p.id = ms.partner_member_id
                LEFT JOIN sport_dance_styles s
                       ON s.style_code = ms.style_code
                      AND (s.club_id IS NULL OR s.club_id = ms.club_id)
                WHERE ms.club_id = ?";
        $params = [$clubId];
        if ($styleCode !== null && $styleCode !== '') {
            $sql .= " AND ms.style_code = ?";
            $params[] = $styleCode;
        }
        $sql .= " ORDER BY m.last_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listForMember(int $memberId): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT ms.*, s.display_name AS style_name, s.category AS style_category,
                    p.first_name AS partner_first, p.last_name AS partner_last
             FROM `{$this->table}` ms
             LEFT JOIN sport_dance_styles s
                    ON s.style_code = ms.style_code
                   AND (s.club_id IS NULL OR s.club_id = ms.club_id)
             LEFT JOIN members p ON p.id = ms.partner_member_id
             WHERE ms.member_id = ? AND ms.club_id = ?
             ORDER BY ms.style_code ASC"
        );
        $stmt->execute([$memberId, $clubId]);
        return $stmt->fetchAll();
    }

    public function upsert(int $memberId, string $styleCode, array $data): void
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            return;
        }
        $level   = array_key_exists($data['level'] ?? '', DanceStyleModel::LEVELS) ? $data['level'] : 'beginner';
        $partner = isset($data['partner_member_id']) && (int)$data['partner_member_id'] > 0
            ? (int)$data['partner_member_id']
            : null;

        // PRIMARY KEY (member_id, style_code) — uzywamy REPLACE-like upsert.
        $existing = $this->find($memberId, $styleCode);
        if ($existing !== null) {
            $stmt = $this->db->prepare(
                "UPDATE `{$this->table}`
                 SET level = ?, partner_member_id = ?
                 WHERE member_id = ? AND style_code = ? AND club_id = ?"
            );
            $stmt->execute([$level, $partner, $memberId, $styleCode, $clubId]);
            return;
        }
        $this->insert([
            'member_id'         => $memberId,
            'style_code'        => $styleCode,
            'level'             => $level,
            'partner_member_id' => $partner,
        ]);
    }

    public function find(int $memberId, string $styleCode): ?array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE member_id = ? AND style_code = ? AND club_id = ?
             LIMIT 1"
        );
        $stmt->execute([$memberId, $styleCode, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function remove(int $memberId, string $styleCode): bool
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "DELETE FROM `{$this->table}`
             WHERE member_id = ? AND style_code = ? AND club_id = ?"
        );
        return $stmt->execute([$memberId, $styleCode, $clubId]);
    }
}
