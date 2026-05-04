<?php

namespace App\Sports\Volleyball\Models;

use App\Models\ClubScopedModel;

class VolleyballTransferModel extends ClubScopedModel
{
    protected string $table = 'volleyball_transfers';

    public function listForClub(int $page = 1, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT vt.*, m.first_name, m.last_name, m.member_number
                FROM volleyball_transfers vt
                JOIN members m ON m.id = vt.member_id
                WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND vt.club_id = ?"; $params[] = $clubId; }
        $sql .= " ORDER BY vt.transfer_date DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }
}
