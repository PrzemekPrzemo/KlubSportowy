<?php

namespace App\Sports\Football\Models;

use App\Models\ClubScopedModel;

class FootballTransferModel extends ClubScopedModel
{
    protected string $table = 'football_transfers';

    public function listForClub(int $page = 1, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT ft.*, m.first_name, m.last_name, m.member_number
                FROM football_transfers ft
                JOIN members m ON m.id = ft.member_id
                WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND ft.club_id = ?"; $params[] = $clubId; }
        $sql .= " ORDER BY ft.transfer_date DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }
}
