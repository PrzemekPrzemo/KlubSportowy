<?php

namespace App\Sports\Athletics\Models;

use App\Models\ClubScopedModel;

class AthleticsCompetitionModel extends ClubScopedModel
{
    protected string $table = 'athletics_competitions';

    public function listForClub(?string $status = null, int $page = 1, int $perPage = 25): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT ac.* FROM athletics_competitions ac WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND ac.club_id = ?"; $params[] = $clubId; }
        if ($status)          { $sql .= " AND ac.status  = ?"; $params[] = $status; }
        $sql .= " ORDER BY ac.date_from DESC, ac.id DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function withResults(int $id): ?array
    {
        $row = $this->findById($id);
        if (!$row) return null;

        $stmt = $this->db->prepare(
            "SELECT ar.*, m.first_name, m.last_name, m.member_number,
                    d.name AS discipline_name, d.short_code
             FROM athletics_records ar
             JOIN members m ON m.id = ar.member_id
             LEFT JOIN disciplines d ON d.id = ar.discipline_id
             WHERE ar.competition_id = ?
             ORDER BY d.short_code, ar.result_value"
        );
        $stmt->execute([$id]);
        $row['results'] = $stmt->fetchAll();
        return $row;
    }

    public function statusCounts(): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT status, COUNT(*) AS cnt FROM athletics_competitions";
        $params = [];
        if ($clubId !== null) { $sql .= " WHERE club_id = ?"; $params[] = $clubId; }
        $sql .= " GROUP BY status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $out = ['zaplanowane' => 0, 'w_trakcie' => 0, 'zakonczone' => 0, 'odwolane' => 0];
        foreach ($stmt->fetchAll() as $r) $out[$r['status']] = (int)$r['cnt'];
        return $out;
    }
}
