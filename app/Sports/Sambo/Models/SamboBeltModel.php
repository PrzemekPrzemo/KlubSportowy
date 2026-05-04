<?php

namespace App\Sports\Sambo\Models;

use App\Models\ClubScopedModel;

class SamboBeltModel extends ClubScopedModel
{
    protected string $table = 'sambo_belts';

    public static array $BELTS = [
        'yellow'  => ['label' => 'Żółty pas',      'color' => '#ffd700'],
        'orange'  => ['label' => 'Pomarańczowy',    'color' => '#ff8c00'],
        'green'   => ['label' => 'Zielony',         'color' => '#28a745'],
        'blue'    => ['label' => 'Niebieski',        'color' => '#007bff'],
        'brown'   => ['label' => 'Brązowy',         'color' => '#8B4513'],
        'black_1' => ['label' => 'Czarny I dan',    'color' => '#000000'],
        'black_2' => ['label' => 'Czarny II dan',   'color' => '#000000'],
        'black_3' => ['label' => 'Czarny III dan',  'color' => '#000000'],
    ];

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT sb.*, m.first_name, m.last_name, m.member_number
                FROM sambo_belts sb
                JOIN members m ON m.id = sb.member_id
                WHERE sb.club_id = ?
                ORDER BY m.last_name, sb.granted_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function currentBelt(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sambo_belts WHERE club_id = ? AND member_id = ?
             ORDER BY granted_date DESC, id DESC LIMIT 1"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetch() ?: null;
    }
}
