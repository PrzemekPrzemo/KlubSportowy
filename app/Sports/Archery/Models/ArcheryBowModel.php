<?php

namespace App\Sports\Archery\Models;

use App\Models\ClubScopedModel;

class ArcheryBowModel extends ClubScopedModel
{
    protected string $table = 'archery_bows';

    public static array $BOW_TYPES = [
        'recurve'     => 'Łuk klasyczny (recurve)',
        'compound'    => 'Łuk bloczkowy (compound)',
        'barebow'     => 'Łuk prosty (barebow)',
        'longbow'     => 'Longbow',
        'traditional' => 'Tradycyjny',
    ];

    public static array $LIMB_LENGTHS = ['XS', 'S', 'M', 'L', 'XL'];

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT ab.*, m.first_name, m.last_name
             FROM archery_bows ab
             LEFT JOIN members m ON m.id = ab.member_id
             WHERE ab.club_id = ?
             ORDER BY ab.bow_type, ab.brand, ab.model"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
