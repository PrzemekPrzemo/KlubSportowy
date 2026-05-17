<?php

namespace App\Sports\Boxing\Models;

use App\Models\ClubScopedModel;

/**
 * Kartoteka bokserska klubu — W-L-D record, licencja, waga, stance.
 *
 * Tabela `sport_boxing_member_record` (PK: member_id).
 * Multi-tenant: kazdy SELECT/UPDATE filtruje po club_id.
 */
class BoxingRecordModel extends ClubScopedModel
{
    protected string $table = 'sport_boxing_member_record';

    public static array $LICENSE_LEVELS = [
        'junior'       => ['label' => 'Junior',       'class' => 'secondary'],
        'senior'       => ['label' => 'Senior',       'class' => 'info'],
        'elite'        => ['label' => 'Elite',        'class' => 'primary'],
        'professional' => ['label' => 'Zawodowiec',   'class' => 'dark'],
    ];

    public static array $STANCES = [
        'orthodox' => 'Orthodox',
        'southpaw' => 'Southpaw',
        'switch'   => 'Switch',
    ];

    public function forMember(int $memberId): ?array
    {
        $clubId = $this->clubId();
        $stmt   = $this->db->prepare(
            "SELECT * FROM sport_boxing_member_record
             WHERE club_id = ? AND member_id = ? LIMIT 1"
        );
        $stmt->execute([$clubId, $memberId]);
        return $stmt->fetch() ?: null;
    }

    public function upsert(int $memberId, array $data): void
    {
        $clubId = (int)$this->clubId();
        $row    = $this->forMember($memberId);

        $data['member_id'] = $memberId;
        $data['club_id']   = $clubId;

        if ($row) {
            // update — preserve club scope
            $set    = [];
            $params = [];
            foreach ($data as $k => $v) {
                if ($k === 'member_id' || $k === 'club_id') continue;
                $set[]    = "`$k` = ?";
                $params[] = $v;
            }
            if (empty($set)) return;
            $params[] = $memberId;
            $params[] = $clubId;
            $sql = "UPDATE sport_boxing_member_record SET " . implode(', ', $set)
                 . " WHERE member_id = ? AND club_id = ?";
            $this->db->prepare($sql)->execute($params);
            return;
        }

        $cols = array_keys($data);
        $ph   = implode(', ', array_fill(0, count($cols), '?'));
        $sql  = "INSERT INTO sport_boxing_member_record (`"
              . implode('`, `', $cols) . "`) VALUES ($ph)";
        $this->db->prepare($sql)->execute(array_values($data));
    }

    /** Lista wszystkich kartotek w klubie z imieniem zawodnika. */
    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, m.first_name, m.last_name, m.member_number
             FROM sport_boxing_member_record r
             JOIN members m ON m.id = r.member_id
             WHERE r.club_id = ?
             ORDER BY m.last_name, m.first_name"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    /** Bezpiecznie sprawdza czy member nalezy do biezacego klubu. */
    public function memberBelongsToClub(int $memberId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM members WHERE id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([$memberId, (int)$this->clubId()]);
        return (bool)$stmt->fetchColumn();
    }
}
