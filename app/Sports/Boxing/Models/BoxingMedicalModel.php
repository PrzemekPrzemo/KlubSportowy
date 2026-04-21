<?php

namespace App\Sports\Boxing\Models;

use App\Models\ClubScopedModel;

class BoxingMedicalModel extends ClubScopedModel
{
    protected string $table = 'boxing_medicals';

    public static array $CLEARANCE_TYPES = [
        'amateur'       => ['label' => 'Amatorski', 'class' => 'info'],
        'pro'           => ['label' => 'Zawodowy',  'class' => 'primary'],
        'sparring_only' => ['label' => 'Sparingi',  'class' => 'secondary'],
    ];

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT bm.*, m.first_name, m.last_name, m.member_number,
                    DATEDIFF(bm.valid_until, CURDATE()) AS days_remaining
             FROM boxing_medicals bm
             JOIN members m ON m.id = bm.member_id
             WHERE bm.club_id = ?
             ORDER BY bm.valid_until ASC"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function currentForMember(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT *, DATEDIFF(valid_until, CURDATE()) AS days_remaining
             FROM boxing_medicals
             WHERE club_id = ? AND member_id = ?
             ORDER BY valid_until DESC LIMIT 1"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetch() ?: null;
    }
}
