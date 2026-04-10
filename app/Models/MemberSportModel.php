<?php

namespace App\Models;

class MemberSportModel extends BaseModel
{
    protected string $table = 'member_sports';

    public function assign(int $memberId, int $clubSportId, array $extras = []): int
    {
        $data = [
            'member_id'     => $memberId,
            'club_sport_id' => $clubSportId,
            'is_active'     => 1,
            'joined_at'     => $extras['joined_at'] ?? date('Y-m-d'),
        ];
        foreach (['class_id', 'discipline_id', 'age_category_id', 'position', 'jersey_number'] as $k) {
            if (array_key_exists($k, $extras)) {
                $data[$k] = $extras[$k];
            }
        }
        $cols  = implode('`, `', array_keys($data));
        $holds = implode(', ', array_fill(0, count($data), '?'));
        $sql   = "INSERT INTO member_sports (`{$cols}`) VALUES ({$holds})
                  ON DUPLICATE KEY UPDATE is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function unassign(int $memberId, int $clubSportId): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM member_sports WHERE member_id = ? AND club_sport_id = ?"
        );
        $stmt->execute([$memberId, $clubSportId]);
    }

    public function forClubSport(int $clubSportId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ms.*, m.first_name, m.last_name, m.member_number
             FROM member_sports ms
             JOIN members m ON m.id = ms.member_id
             WHERE ms.club_sport_id = ? AND ms.is_active = 1
             ORDER BY m.last_name, m.first_name"
        );
        $stmt->execute([$clubSportId]);
        return $stmt->fetchAll();
    }
}
