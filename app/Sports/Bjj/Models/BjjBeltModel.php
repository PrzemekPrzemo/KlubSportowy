<?php

namespace App\Sports\Bjj\Models;

use App\Models\ClubScopedModel;

class BjjBeltModel extends ClubScopedModel
{
    protected string $table = 'bjj_belts';

    public static array $BELT_LEVELS = [
        'white'  => ['label' => 'Biały (White)',   'color' => '#f8f9fa', 'text' => '#333'],
        'blue'   => ['label' => 'Niebieski (Blue)', 'color' => '#0d6efd', 'text' => '#fff'],
        'purple' => ['label' => 'Fioletowy (Purple)','color' => '#6f42c1', 'text' => '#fff'],
        'brown'  => ['label' => 'Brązowy (Brown)',  'color' => '#8B4513', 'text' => '#fff'],
        'black'  => ['label' => 'Czarny (Black)',   'color' => '#212529', 'text' => '#fff'],
    ];

    public function listForClub(): array
    {
        $sql = "SELECT b.*, m.first_name, m.last_name, m.member_number
                FROM bjj_belts b
                JOIN members m ON m.id = b.member_id
                WHERE b.club_id = ?
                ORDER BY m.last_name, b.exam_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function currentBelt(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM bjj_belts WHERE club_id = ? AND member_id = ?
             ORDER BY exam_date DESC, id DESC LIMIT 1"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetch() ?: null;
    }
}
