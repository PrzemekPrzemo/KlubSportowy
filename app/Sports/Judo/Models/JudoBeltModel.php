<?php

namespace App\Sports\Judo\Models;

use App\Models\ClubScopedModel;

class JudoBeltModel extends ClubScopedModel
{
    protected string $table = 'judo_belts';

    public static array $BELTS = [
        '6kyu' => ['label' => '6 kyu (biały)',       'color' => '#fff',    'dan' => false],
        '5kyu' => ['label' => '5 kyu (żółty)',        'color' => '#ffd700', 'dan' => false],
        '4kyu' => ['label' => '4 kyu (pomarańczowy)', 'color' => '#ff8c00', 'dan' => false],
        '3kyu' => ['label' => '3 kyu (zielony)',      'color' => '#28a745', 'dan' => false],
        '2kyu' => ['label' => '2 kyu (niebieski)',    'color' => '#007bff', 'dan' => false],
        '1kyu' => ['label' => '1 kyu (brązowy)',      'color' => '#8B4513', 'dan' => false],
        '1dan' => ['label' => '1 dan (czarny)',        'color' => '#000',    'dan' => true],
        '2dan' => ['label' => '2 dan',                 'color' => '#000',    'dan' => true],
        '3dan' => ['label' => '3 dan',                 'color' => '#000',    'dan' => true],
        '4dan' => ['label' => '4 dan',                 'color' => '#000',    'dan' => true],
        '5dan' => ['label' => '5 dan',                 'color' => '#000',    'dan' => true],
        '6dan' => ['label' => '6 dan (czerwono-biały)','color' => '#dc3545', 'dan' => true],
        '7dan' => ['label' => '7 dan',                 'color' => '#dc3545', 'dan' => true],
        '8dan' => ['label' => '8 dan (czerwony)',      'color' => '#dc3545', 'dan' => true],
    ];

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT jb.*, m.first_name, m.last_name, m.member_number
                FROM judo_belts jb
                JOIN members m ON m.id = jb.member_id
                WHERE jb.club_id = ?
                ORDER BY m.last_name, jb.granted_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function currentBelt(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM judo_belts WHERE club_id = ? AND member_id = ?
             ORDER BY granted_date DESC, id DESC LIMIT 1"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetch() ?: null;
    }
}
