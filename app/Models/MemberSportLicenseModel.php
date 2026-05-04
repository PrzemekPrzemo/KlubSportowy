<?php

namespace App\Models;

class MemberSportLicenseModel extends ClubScopedModel
{
    protected string $table = 'member_sport_licenses';

    public function listForClub(?string $sportKey = null, bool $activeOnly = false): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT msl.*, m.first_name, m.last_name, m.member_number
                FROM member_sport_licenses msl
                JOIN members m ON m.id = msl.member_id
                WHERE msl.club_id = ?";
        $params = [$clubId];
        if ($sportKey !== null) {
            $sql .= " AND msl.sport_key = ?";
            $params[] = $sportKey;
        }
        if ($activeOnly) {
            $sql .= " AND (msl.valid_to IS NULL OR msl.valid_to >= CURDATE()) AND msl.status = 'active'";
        }
        $sql .= " ORDER BY msl.valid_to ASC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listForMember(int $memberId, ?string $sportKey = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT msl.* FROM member_sport_licenses msl
                WHERE msl.club_id = ? AND msl.member_id = ?";
        $params = [$clubId, $memberId];
        if ($sportKey !== null) {
            $sql .= " AND msl.sport_key = ?";
            $params[] = $sportKey;
        }
        $sql .= " ORDER BY msl.valid_to DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function expiringSoon(int $days = 30): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT msl.*, m.first_name, m.last_name, m.member_number
             FROM member_sport_licenses msl
             JOIN members m ON m.id = msl.member_id
             WHERE msl.club_id = ? AND msl.status = 'active'
               AND msl.valid_to BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY msl.valid_to ASC"
        );
        $stmt->execute([$clubId, $days]);
        return $stmt->fetchAll();
    }
}
