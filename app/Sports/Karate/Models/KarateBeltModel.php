<?php

namespace App\Sports\Karate\Models;

use App\Models\ClubScopedModel;

class KarateBeltModel extends ClubScopedModel
{
    protected string $table = 'karate_belts';

    public static array $BELTS = [
        '10kyu' => ['label' => '10 kyu (biały)',        'color' => '#ffffff', 'dan' => false],
        '9kyu'  => ['label' => '9 kyu (biały-żółty)',   'color' => '#fffacd', 'dan' => false],
        '8kyu'  => ['label' => '8 kyu (żółty)',         'color' => '#ffd700', 'dan' => false],
        '7kyu'  => ['label' => '7 kyu (żółty-pomarańczowy)', 'color' => '#ffb347', 'dan' => false],
        '6kyu'  => ['label' => '6 kyu (pomarańczowy)',  'color' => '#ff8c00', 'dan' => false],
        '5kyu'  => ['label' => '5 kyu (zielony)',       'color' => '#28a745', 'dan' => false],
        '4kyu'  => ['label' => '4 kyu (niebieski)',     'color' => '#007bff', 'dan' => false],
        '3kyu'  => ['label' => '3 kyu (brązowy)',       'color' => '#8B4513', 'dan' => false],
        '2kyu'  => ['label' => '2 kyu (brązowy II)',    'color' => '#6b3410', 'dan' => false],
        '1kyu'  => ['label' => '1 kyu (brązowy III)',   'color' => '#4a2409', 'dan' => false],
        '1dan'  => ['label' => '1 dan (czarny)',        'color' => '#000000', 'dan' => true],
        '2dan'  => ['label' => '2 dan (czarny)',        'color' => '#000000', 'dan' => true],
        '3dan'  => ['label' => '3 dan (czarny)',        'color' => '#000000', 'dan' => true],
        '4dan'  => ['label' => '4 dan (czarny)',        'color' => '#000000', 'dan' => true],
        '5dan'  => ['label' => '5 dan (czarny)',        'color' => '#000000', 'dan' => true],
        '6dan'  => ['label' => '6 dan (czerwono-biały)','color' => '#cc0000', 'dan' => true],
        '7dan'  => ['label' => '7 dan (czerwono-biały)','color' => '#cc0000', 'dan' => true],
        '8dan'  => ['label' => '8 dan (czerwono-biały)','color' => '#cc0000', 'dan' => true],
        '9dan'  => ['label' => '9 dan (czerwony)',      'color' => '#dc3545', 'dan' => true],
        '10dan' => ['label' => '10 dan (czerwony)',     'color' => '#dc3545', 'dan' => true],
    ];

    public static array $STYLES = [
        'shotokan'  => 'Shotokan',
        'wado_ryu'  => 'Wado-ryu',
        'goju_ryu'  => 'Goju-ryu',
        'shito_ryu' => 'Shito-ryu',
        'kyokushin' => 'Kyokushin',
        'other'     => 'Inne',
    ];

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT kb.*, m.first_name, m.last_name, m.member_number
             FROM karate_belts kb
             JOIN members m ON m.id = kb.member_id
             WHERE kb.club_id = ?
             ORDER BY m.last_name, m.first_name, kb.granted_date DESC"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function currentBelt(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM karate_belts WHERE club_id = ? AND member_id = ?
             ORDER BY granted_date DESC LIMIT 1"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetch() ?: null;
    }
}
