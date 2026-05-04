<?php

namespace App\Sports\Mma\Models;

use App\Models\ClubScopedModel;

class MmaFighterModel extends ClubScopedModel
{
    protected string $table = 'mma_fighters';

    public static array $STANCES = [
        'ortodox'  => 'Ortodox',
        'southpaw' => 'Southpaw',
        'switch'   => 'Switch',
    ];

    public static array $STYLES = [
        'boxing'      => 'Boks',
        'wrestling'   => 'Zapasy',
        'bjj'         => 'BJJ',
        'muay_thai'   => 'Muay Thai',
        'karate'      => 'Karate',
        'sambo'       => 'Sambo',
        'judo'        => 'Judo',
        'kickboxing'  => 'Kickboxing',
        'mixed'       => 'Mixed / uniwersalny',
    ];

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT f.*, m.first_name, m.last_name, m.member_number
             FROM mma_fighters f
             JOIN members m ON m.id = f.member_id
             WHERE f.club_id = ?
             ORDER BY m.last_name"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function forMember(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM mma_fighters WHERE club_id = ? AND member_id = ? LIMIT 1"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetch() ?: null;
    }
}
