<?php

namespace App\Sports\Canoeing\Models;

use App\Models\ClubScopedModel;

/**
 * Profil kajakarza — klasa lodzi, waga, ranking krajowy.
 * PRIMARY KEY = member_id (1 rekord na zawodnika).
 */
class CanoeingMemberModel extends ClubScopedModel
{
    protected string $table = 'sport_canoeing_member';

    public const BOAT_CLASSES = [
        'K1' => 'K1 — kajak 1-osobowy',
        'K2' => 'K2 — kajak 2-osobowy',
        'K4' => 'K4 — kajak 4-osobowy',
        'C1' => 'C1 — kanadyjka 1-osobowa',
        'C2' => 'C2 — kanadyjka 2-osobowa',
        'C4' => 'C4 — kanadyjka 4-osobowa',
        'slalom' => 'Slalom',
    ];

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT cm.*, m.first_name, m.last_name, m.member_number
             FROM `{$this->table}` cm
             JOIN members m ON m.id = cm.member_id
             WHERE cm.club_id = ?
             ORDER BY cm.national_rank ASC, m.last_name ASC"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function findForMember(int $memberId): ?array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE member_id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([$memberId, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsert(int $memberId, array $data): void
    {
        $boat = array_key_exists($data['boat_class'] ?? '', self::BOAT_CLASSES) ? $data['boat_class'] : 'K1';
        $weight = isset($data['weight_class']) && $data['weight_class'] !== '' ? trim((string)$data['weight_class']) : null;
        $rank   = isset($data['national_rank']) && $data['national_rank'] !== '' ? (int)$data['national_rank'] : null;

        $existing = $this->findForMember($memberId);
        if ($existing !== null) {
            $clubId = $this->clubId();
            $stmt = $this->db->prepare(
                "UPDATE `{$this->table}`
                 SET boat_class = ?, weight_class = ?, national_rank = ?
                 WHERE member_id = ? AND club_id = ?"
            );
            $stmt->execute([$boat, $weight, $rank, $memberId, $clubId]);
            return;
        }
        $this->insert([
            'member_id'     => $memberId,
            'boat_class'    => $boat,
            'weight_class'  => $weight,
            'national_rank' => $rank,
        ]);
    }
}
