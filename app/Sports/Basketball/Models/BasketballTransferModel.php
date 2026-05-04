<?php

namespace App\Sports\Basketball\Models;

use App\Models\ClubScopedModel;

class BasketballTransferModel extends ClubScopedModel
{
    protected string $table = 'basketball_transfers';

    public function listForClub(int $page = 1, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT bt.*, m.first_name, m.last_name, m.member_number
                FROM basketball_transfers bt
                JOIN members m ON m.id = bt.member_id
                WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND bt.club_id = ?"; $params[] = $clubId; }
        $sql .= " ORDER BY bt.transfer_date DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }
}
