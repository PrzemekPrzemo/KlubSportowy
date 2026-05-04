<?php

namespace App\Models;

class MemberBeltModel extends BaseModel
{
    protected string $table = 'member_belts';

    public function listForMember(int $memberId, int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT mb.*, s.name AS sport_name, s.color AS sport_color, s.icon AS sport_icon
             FROM member_belts mb
             JOIN club_sports cs ON cs.id = mb.club_sport_id
             JOIN sports s ON s.id = cs.sport_id
             WHERE mb.member_id = ? AND mb.club_id = ?
             ORDER BY mb.exam_date DESC"
        );
        $stmt->execute([$memberId, $clubId]);
        return $stmt->fetchAll();
    }

    public function latestBelt(int $memberId, int $clubSportId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM member_belts WHERE member_id = ? AND club_sport_id = ? ORDER BY exam_date DESC LIMIT 1"
        );
        $stmt->execute([$memberId, $clubSportId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function add(array $data): int
    {
        return $this->insert($data);
    }
}
