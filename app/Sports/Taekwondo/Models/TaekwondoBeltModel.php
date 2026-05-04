<?php

namespace App\Sports\Taekwondo\Models;

use App\Models\ClubScopedModel;

class TaekwondoBeltModel extends ClubScopedModel
{
    protected string $table = 'taekwondo_belts';

    public static array $BELTS = [
        '10gup' => ['label' => '10 gup (biały)',              'color' => '#ffffff'],
        '9gup'  => ['label' => '9 gup (biały-żółty)',         'color' => '#fffacd'],
        '8gup'  => ['label' => '8 gup (żółty)',               'color' => '#ffd700'],
        '7gup'  => ['label' => '7 gup (żółty-zielony)',       'color' => '#adff2f'],
        '6gup'  => ['label' => '6 gup (zielony)',             'color' => '#28a745'],
        '5gup'  => ['label' => '5 gup (zielony-niebieski)',   'color' => '#17a2b8'],
        '4gup'  => ['label' => '4 gup (niebieski)',           'color' => '#007bff'],
        '3gup'  => ['label' => '3 gup (niebieski-czerwony)',  'color' => '#9b59b6'],
        '2gup'  => ['label' => '2 gup (czerwony)',            'color' => '#dc3545'],
        '1gup'  => ['label' => '1 gup (czerwony-czarny)',     'color' => '#c0392b'],
        '1dan'  => ['label' => '1 dan (czarny)',              'color' => '#000000'],
        '2dan'  => ['label' => '2 dan (czarny)',              'color' => '#000000'],
        '3dan'  => ['label' => '3 dan (czarny)',              'color' => '#000000'],
        '4dan'  => ['label' => '4 dan (czarny)',              'color' => '#000000'],
        '5dan'  => ['label' => '5 dan (czarny)',              'color' => '#000000'],
        '6dan'  => ['label' => '6 dan (czarny)',              'color' => '#000000'],
        '7dan'  => ['label' => '7 dan (czarny)',              'color' => '#000000'],
        '8dan'  => ['label' => '8 dan (czarny)',              'color' => '#000000'],
        '9dan'  => ['label' => '9 dan (czarny)',              'color' => '#000000'],
    ];

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT tb.*, m.first_name, m.last_name, m.member_number
             FROM taekwondo_belts tb
             JOIN members m ON m.id = tb.member_id
             WHERE tb.club_id = ?
             ORDER BY m.last_name, m.first_name, tb.granted_date DESC"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function currentBelt(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM taekwondo_belts WHERE club_id = ? AND member_id = ?
             ORDER BY granted_date DESC LIMIT 1"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetch() ?: null;
    }
}
