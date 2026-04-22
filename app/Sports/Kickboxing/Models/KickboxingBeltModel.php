<?php

namespace App\Sports\Kickboxing\Models;

use App\Models\ClubScopedModel;

class KickboxingBeltModel extends ClubScopedModel
{
    protected string $table = 'kickboxing_belts';

    public static array $BELTS = [
        'biały'        => ['label' => 'Biały',         'color' => '#ffffff'],
        'żółty'        => ['label' => 'Żółty',          'color' => '#ffd700'],
        'pomarańczowy' => ['label' => 'Pomarańczowy',   'color' => '#ff8c00'],
        'zielony'      => ['label' => 'Zielony',        'color' => '#28a745'],
        'niebieski'    => ['label' => 'Niebieski',      'color' => '#007bff'],
        'fioletowy'    => ['label' => 'Fioletowy',      'color' => '#6f42c1'],
        'brązowy'      => ['label' => 'Brązowy',        'color' => '#8B4513'],
        'czerwony'     => ['label' => 'Czerwony',       'color' => '#dc3545'],
        'czarny'       => ['label' => 'Czarny',         'color' => '#000000'],
    ];

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, m.first_name, m.last_name, m.member_number
             FROM kickboxing_belts b
             JOIN members m ON m.id = b.member_id
             WHERE b.club_id = ?
             ORDER BY m.last_name, b.exam_date DESC"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function currentBelt(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM kickboxing_belts WHERE club_id = ? AND member_id = ?
             ORDER BY exam_date DESC LIMIT 1"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetch() ?: null;
    }
}
