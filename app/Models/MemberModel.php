<?php

namespace App\Models;

class MemberModel extends ClubScopedModel
{
    protected string $table = 'members';

    public function search(string $q = '', ?string $status = null, ?int $clubSportId = null, int $page = 1, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT m.* FROM members m";
        $params = [];

        if ($clubSportId !== null) {
            $sql .= " JOIN member_sports ms ON ms.member_id = m.id AND ms.club_sport_id = ?";
            $params[] = $clubSportId;
        }

        $sql .= " WHERE 1=1";

        if ($clubId !== null) {
            $sql     .= " AND m.club_id = ?";
            $params[] = $clubId;
        }

        if ($q !== '') {
            $sql     .= " AND (m.first_name LIKE ? OR m.last_name LIKE ?
                               OR m.member_number LIKE ? OR m.email LIKE ?)";
            $like     = '%' . $q . '%';
            $params   = [...$params, $like, $like, $like, $like];
        }

        if ($status !== null && $status !== '') {
            $sql     .= " AND m.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY m.last_name, m.first_name";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function withSports(int $memberId): array
    {
        $row = $this->findById($memberId);
        if ($row === null) return [];

        $sql = "SELECT ms.id AS ms_id, ms.position, ms.jersey_number, ms.is_active,
                       ms.joined_at, ms.class_id, ms.discipline_id,
                       cs.id AS club_sport_id, s.`key` AS sport_key, s.name AS sport_name,
                       s.icon, s.color, s.team_sport,
                       mc.name AS class_name, mc.short_code AS class_code,
                       d.name AS discipline_name, d.short_code AS discipline_code
                FROM member_sports ms
                JOIN club_sports cs    ON cs.id = ms.club_sport_id
                JOIN sports s          ON s.id = cs.sport_id
                LEFT JOIN member_classes mc ON mc.id = ms.class_id
                LEFT JOIN disciplines d     ON d.id = ms.discipline_id
                WHERE ms.member_id = ?
                ORDER BY s.sort_order";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$memberId]);
        $row['sports'] = $stmt->fetchAll();
        return $row;
    }

    /** Kolejny wolny numer członkowski w klubie (format: {rok}-{seq}). */
    public function nextMemberNumber(int $clubId): string
    {
        $year = (int)date('Y');
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM members WHERE club_id = ? AND YEAR(join_date) = ?"
        );
        $stmt->execute([$clubId, $year]);
        $count = (int)$stmt->fetchColumn() + 1;
        return $year . '-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
    }
}
