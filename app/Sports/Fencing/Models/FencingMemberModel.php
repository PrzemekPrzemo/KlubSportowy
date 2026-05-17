<?php

namespace App\Sports\Fencing\Models;

use App\Models\ClubScopedModel;

/**
 * Profil szermierza — multi-select bron + FIE rank + hand.
 * Tabela `sport_fencing_member`.
 */
class FencingMemberModel extends ClubScopedModel
{
    protected string $table = 'sport_fencing_member';

    public static array $WEAPONS = [
        'epee'  => ['label' => 'Szpada', 'color' => '#198754'],
        'foil'  => ['label' => 'Floret', 'color' => '#0d6efd'],
        'sabre' => ['label' => 'Szabla', 'color' => '#dc3545'],
    ];

    public static array $HANDS = [
        'right' => 'Praworeczny',
        'left'  => 'Leworeczny',
    ];

    public function forMember(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sport_fencing_member
             WHERE club_id = ? AND member_id = ? LIMIT 1"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        $row = $stmt->fetch() ?: null;
        if ($row && isset($row['weapons']) && is_string($row['weapons'])) {
            $row['weapons_list'] = $row['weapons'] !== '' ? explode(',', $row['weapons']) : [];
        }
        return $row;
    }

    public function upsert(int $memberId, array $data): void
    {
        $clubId = (int)$this->clubId();

        if (isset($data['weapons']) && is_array($data['weapons'])) {
            $valid = [];
            foreach ($data['weapons'] as $w) {
                if (array_key_exists($w, self::$WEAPONS)) $valid[] = $w;
            }
            $data['weapons'] = $valid ? implode(',', array_unique($valid)) : 'epee';
        }
        if (isset($data['hand']) && !array_key_exists($data['hand'], self::$HANDS)) {
            $data['hand'] = 'right';
        }

        $data['member_id'] = $memberId;
        $data['club_id']   = $clubId;
        $row = $this->forMember($memberId);

        if ($row) {
            $set = []; $params = [];
            foreach ($data as $k => $v) {
                if ($k === 'member_id' || $k === 'club_id') continue;
                $set[]    = "`$k` = ?";
                $params[] = $v;
            }
            if (empty($set)) return;
            $params[] = $memberId; $params[] = $clubId;
            $this->db->prepare(
                "UPDATE sport_fencing_member SET " . implode(', ', $set)
                . " WHERE member_id = ? AND club_id = ?"
            )->execute($params);
            return;
        }

        $cols = array_keys($data);
        $ph   = implode(', ', array_fill(0, count($cols), '?'));
        $this->db->prepare(
            "INSERT INTO sport_fencing_member (`" . implode('`, `', $cols) . "`) VALUES ($ph)"
        )->execute(array_values($data));
    }

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, m.first_name, m.last_name, m.member_number
             FROM sport_fencing_member r
             JOIN members m ON m.id = r.member_id
             WHERE r.club_id = ?
             ORDER BY (r.fie_rank IS NULL), r.fie_rank ASC, m.last_name"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function memberBelongsToClub(int $memberId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM members WHERE id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([$memberId, (int)$this->clubId()]);
        return (bool)$stmt->fetchColumn();
    }
}
