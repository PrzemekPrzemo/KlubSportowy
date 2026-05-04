<?php

namespace App\Sports\Tennis\Models;

use App\Models\ClubScopedModel;

class TennisMatchModel extends ClubScopedModel
{
    protected string $table = 'tennis_matches';

    public static array $SURFACES = [
        'clay'    => ['label' => 'Mączka (Clay)', 'color' => '#d2691e'],
        'hard'    => ['label' => 'Twarda (Hard)',  'color' => '#3a78b8'],
        'grass'   => ['label' => 'Trawa (Grass)',  'color' => '#3a9d3a'],
        'indoor'  => ['label' => 'Hala',            'color' => '#555'],
        'carpet'  => ['label' => 'Dywan',           'color' => '#9b59b6'],
    ];

    public static array $MATCH_TYPES = [
        'rankingowy'  => 'Rankingowy',
        'turniejowy'  => 'Turniejowy',
        'towarzyski'  => 'Towarzyski',
        'treningowy'  => 'Treningowy',
    ];

    public function listForClub(?int $memberId = null, ?string $surface = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT tm.*,
                       p1.first_name AS p1_first, p1.last_name AS p1_last, p1.member_number AS p1_num,
                       p2.first_name AS p2_first, p2.last_name AS p2_last, p2.member_number AS p2_num
                FROM tennis_matches tm
                JOIN members p1 ON p1.id = tm.player1_id
                JOIN members p2 ON p2.id = tm.player2_id
                WHERE tm.club_id = ?";
        $params = [$clubId];

        if ($memberId !== null) {
            $sql .= " AND (tm.player1_id = ? OR tm.player2_id = ?)";
            $params[] = $memberId;
            $params[] = $memberId;
        }
        if ($surface !== null && array_key_exists($surface, self::$SURFACES)) {
            $sql .= " AND tm.surface = ?";
            $params[] = $surface;
        }
        $sql .= " ORDER BY tm.match_date DESC, tm.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function headToHead(int $memberA, int $memberB): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT *
             FROM tennis_matches
             WHERE club_id = ?
               AND ((player1_id = ? AND player2_id = ?) OR (player1_id = ? AND player2_id = ?))
             ORDER BY match_date DESC"
        );
        $stmt->execute([$clubId, $memberA, $memberB, $memberB, $memberA]);
        $matches = $stmt->fetchAll();

        $winsA = $winsB = 0;
        foreach ($matches as $m) {
            if ((int)$m['winner_id'] === $memberA) $winsA++;
            elseif ((int)$m['winner_id'] === $memberB) $winsB++;
        }

        return ['matches' => $matches, 'wins_a' => $winsA, 'wins_b' => $winsB];
    }

    public function statsForMember(int $memberId): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN winner_id = ? THEN 1 ELSE 0 END) AS wins,
                SUM(CASE WHEN winner_id IS NOT NULL AND winner_id != ? AND (player1_id = ? OR player2_id = ?) THEN 1 ELSE 0 END) AS losses
             FROM tennis_matches
             WHERE club_id = ? AND (player1_id = ? OR player2_id = ?)"
        );
        $stmt->execute([$memberId, $memberId, $memberId, $memberId, $clubId, $memberId, $memberId]);
        return $stmt->fetch() ?: ['total' => 0, 'wins' => 0, 'losses' => 0];
    }
}
