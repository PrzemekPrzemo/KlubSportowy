<?php

namespace App\Sports\Wrestling\Models;

use App\Models\ClubScopedModel;

/**
 * Profil zapasnika — style + waga + ranking.
 * Tabela `sport_wrestling_member`.
 */
class WrestlingMemberModel extends ClubScopedModel
{
    protected string $table = 'sport_wrestling_member';

    public static array $STYLE_KEYS = [
        'freestyle'   => 'Wolny (Freestyle)',
        'greco_roman' => 'Klasyczny (Greco-Roman)',
        'womens'      => "Kobiety (Women's Freestyle)",
    ];

    public function forMember(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sport_wrestling_member
             WHERE club_id = ? AND member_id = ? LIMIT 1"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        $row = $stmt->fetch() ?: null;
        if ($row && isset($row['styles']) && is_string($row['styles'])) {
            $row['styles_list'] = $row['styles'] !== '' ? explode(',', $row['styles']) : [];
        }
        return $row;
    }

    public function upsert(int $memberId, array $data): void
    {
        $clubId = (int)$this->clubId();

        // styles: array → SET csv
        if (isset($data['styles']) && is_array($data['styles'])) {
            $valid = [];
            foreach ($data['styles'] as $s) {
                if (array_key_exists($s, self::$STYLE_KEYS)) {
                    $valid[] = $s;
                }
            }
            $data['styles'] = $valid ? implode(',', array_unique($valid)) : 'freestyle';
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
                "UPDATE sport_wrestling_member SET " . implode(', ', $set)
                . " WHERE member_id = ? AND club_id = ?"
            )->execute($params);
            return;
        }

        $cols = array_keys($data);
        $ph   = implode(', ', array_fill(0, count($cols), '?'));
        $this->db->prepare(
            "INSERT INTO sport_wrestling_member (`" . implode('`, `', $cols) . "`) VALUES ($ph)"
        )->execute(array_values($data));
    }

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, m.first_name, m.last_name, m.member_number
             FROM sport_wrestling_member r
             JOIN members m ON m.id = r.member_id
             WHERE r.club_id = ?
             ORDER BY r.rank_points DESC, m.last_name"
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
