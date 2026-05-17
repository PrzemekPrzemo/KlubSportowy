<?php

namespace App\Sports\Mma\Models;

use App\Models\ClubScopedModel;

/**
 * Kartoteka MMA — W-L-D + KO/Sub/Dec + discipline mix.
 * Tabela `sport_mma_member_record`.
 */
class MmaRecordModel extends ClubScopedModel
{
    protected string $table = 'sport_mma_member_record';

    public static array $STANCES = [
        'orthodox' => 'Orthodox',
        'southpaw' => 'Southpaw',
        'switch'   => 'Switch',
    ];

    public function forMember(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sport_mma_member_record
             WHERE club_id = ? AND member_id = ? LIMIT 1"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetch() ?: null;
    }

    public function upsert(int $memberId, array $data): void
    {
        $clubId = (int)$this->clubId();
        $row    = $this->forMember($memberId);

        // Normalize discipline mix to sum 100
        $data = $this->normalizeMix($data);

        $data['member_id'] = $memberId;
        $data['club_id']   = $clubId;

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
                "UPDATE sport_mma_member_record SET " . implode(', ', $set)
                . " WHERE member_id = ? AND club_id = ?"
            )->execute($params);
            return;
        }

        $cols = array_keys($data);
        $ph   = implode(', ', array_fill(0, count($cols), '?'));
        $this->db->prepare(
            "INSERT INTO sport_mma_member_record (`" . implode('`, `', $cols) . "`) VALUES ($ph)"
        )->execute(array_values($data));
    }

    private function normalizeMix(array $data): array
    {
        $s = (int)($data['pct_striking']  ?? 33);
        $w = (int)($data['pct_wrestling'] ?? 33);
        $g = (int)($data['pct_grappling'] ?? 34);
        $s = max(0, min(100, $s));
        $w = max(0, min(100, $w));
        $g = max(0, min(100, $g));
        $sum = $s + $w + $g;
        if ($sum !== 100 && $sum > 0) {
            // Rescale to 100 keeping proportions
            $s = (int)round($s * 100 / $sum);
            $w = (int)round($w * 100 / $sum);
            $g = 100 - $s - $w;
            if ($g < 0) { $g = 0; }
        }
        $data['pct_striking']  = $s;
        $data['pct_wrestling'] = $w;
        $data['pct_grappling'] = $g;
        return $data;
    }

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, m.first_name, m.last_name, m.member_number
             FROM sport_mma_member_record r
             JOIN members m ON m.id = r.member_id
             WHERE r.club_id = ?
             ORDER BY m.last_name, m.first_name"
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
