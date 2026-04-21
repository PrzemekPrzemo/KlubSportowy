<?php

namespace App\Sports\Fencing\Models;

use App\Models\ClubScopedModel;

class FencingFencerModel extends ClubScopedModel
{
    protected string $table = 'fencing_fencers';

    public static array $WEAPONS = [
        'foil'  => ['label' => 'Floret',  'color' => '#0d6efd'],
        'epee'  => ['label' => 'Szpada',  'color' => '#198754'],
        'sabre' => ['label' => 'Szabla',  'color' => '#dc3545'],
    ];

    public static array $LATERALITIES = [
        'praworęczny'  => 'Praworęczny',
        'leworęczny'   => 'Leworęczny',
        'oburęczny'    => 'Oburęczny',
    ];

    public function listForClub(?string $weapon = null): array
    {
        $sql = "SELECT ff.*, m.first_name, m.last_name, m.member_number,
                       (SELECT COUNT(*) FROM fencing_results fr WHERE fr.member_id = ff.member_id AND fr.club_id = ff.club_id) AS total_starts
                FROM fencing_fencers ff
                JOIN members m ON m.id = ff.member_id
                WHERE ff.club_id = ?";
        $params = [$this->clubId()];
        if ($weapon !== null && array_key_exists($weapon, self::$WEAPONS)) {
            $sql .= " AND ff.primary_weapon = ?";
            $params[] = $weapon;
        }
        $sql .= " ORDER BY ff.ranking_points DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $pos = 0;
        foreach ($rows as &$r) {
            $pos++;
            $r['position'] = $pos;
        }
        return $rows;
    }

    public function forMember(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM fencing_fencers WHERE club_id = ? AND member_id = ? LIMIT 1"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetch() ?: null;
    }
}
