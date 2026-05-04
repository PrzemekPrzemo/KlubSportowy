<?php

namespace App\Sports\Golf\Models;

use App\Models\ClubScopedModel;

class GolfHandicapModel extends ClubScopedModel
{
    protected string $table = 'golf_handicaps';

    public static array $SOURCES = [
        'pzga'         => 'PZGA (oficjalny)',
        'klubowy'      => 'Klubowy',
        'wsh_official' => 'WHS oficjalny',
        'manual'       => 'Wpis ręczny',
    ];

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT h.*, m.first_name, m.last_name, m.member_number
             FROM golf_handicaps h
             JOIN members m ON m.id = h.member_id
             WHERE h.club_id = ?
             ORDER BY h.updated_at DESC"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function currentForMember(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM golf_handicaps
             WHERE club_id = ? AND member_id = ?
             ORDER BY updated_at DESC, id DESC LIMIT 1"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetch() ?: null;
    }

    public function historyForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM golf_handicaps
             WHERE club_id = ? AND member_id = ?
             ORDER BY updated_at DESC"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetchAll();
    }
}
