<?php

namespace App\Sports\Tennis\Models;

use App\Models\ClubScopedModel;

class TennisRankingModel extends ClubScopedModel
{
    protected string $table = 'tennis_rankings';

    public function ranking(?string $season = null): array
    {
        $clubId = $this->clubId();
        $season = $season ?: (string)date('Y');
        $stmt   = $this->db->prepare(
            "SELECT tr.*, m.first_name, m.last_name, m.member_number
             FROM tennis_rankings tr
             JOIN members m ON m.id = tr.member_id
             WHERE tr.club_id = ? AND tr.season = ?
             ORDER BY tr.points DESC, tr.wins DESC"
        );
        $stmt->execute([$clubId, $season]);
        $rows = $stmt->fetchAll();

        $pos = 0;
        foreach ($rows as &$r) {
            $pos++;
            $r['position'] = $pos;
        }
        return $rows;
    }

    public function memberEntry(int $memberId, ?string $season = null): ?array
    {
        $clubId = $this->clubId();
        $season = $season ?: (string)date('Y');
        $stmt = $this->db->prepare(
            "SELECT * FROM tennis_rankings
             WHERE club_id = ? AND member_id = ? AND season = ? LIMIT 1"
        );
        $stmt->execute([$clubId, $memberId, $season]);
        return $stmt->fetch() ?: null;
    }

    public function bumpAfterMatch(int $winnerId, int $loserId, string $matchType): void
    {
        $clubId = $this->clubId();
        $season = (string)date('Y');
        $winnerPoints = match ($matchType) {
            'turniejowy' => 50,
            'rankingowy' => 20,
            'towarzyski' => 5,
            default      => 1,
        };

        foreach ([[$winnerId, $winnerPoints, 1, 0], [$loserId, 0, 0, 1]] as [$mid, $pts, $w, $l]) {
            $stmt = $this->db->prepare(
                "INSERT INTO tennis_rankings (club_id, member_id, season, points, matches_played, wins, losses)
                 VALUES (?, ?, ?, ?, 1, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    points = points + VALUES(points),
                    matches_played = matches_played + 1,
                    wins = wins + VALUES(wins),
                    losses = losses + VALUES(losses)"
            );
            $stmt->execute([$clubId, $mid, $season, $pts, $w, $l]);
        }
    }
}
