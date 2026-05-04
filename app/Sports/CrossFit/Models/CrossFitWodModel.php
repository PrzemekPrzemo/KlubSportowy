<?php

namespace App\Sports\CrossFit\Models;

use App\Models\ClubScopedModel;

class CrossFitWodModel extends ClubScopedModel
{
    protected string $table = 'crossfit_wods';

    public static array $WOD_TYPES = [
        'amrap'    => 'AMRAP',
        'emom'     => 'EMOM',
        'for_time' => 'For Time',
        'max_reps' => 'Max Reps',
        'for_load' => 'For Load (1RM)',
        'chipper'  => 'Chipper',
        'ladder'   => 'Ladder',
        'tabata'   => 'Tabata',
    ];

    public function listForClub(?string $type = null): array
    {
        $sql    = "SELECT * FROM crossfit_wods WHERE club_id = ?";
        $params = [$this->clubId()];
        if ($type !== null) { $sql .= " AND wod_type = ?"; $params[] = $type; }
        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function leaderboard(int $wodId, int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, m.first_name, m.last_name
             FROM crossfit_scores s
             JOIN members m ON m.id = s.member_id
             WHERE s.wod_id = ? AND s.club_id = ?
             ORDER BY s.rx DESC, s.score_date
             LIMIT ?"
        );
        $stmt->execute([$wodId, $this->clubId(), $limit]);
        return $stmt->fetchAll();
    }

    public function recentForMember(int $memberId, int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, w.name AS wod_name, w.wod_type
             FROM crossfit_scores s
             JOIN crossfit_wods w ON w.id = s.wod_id
             WHERE s.member_id = ? AND s.club_id = ?
             ORDER BY s.score_date DESC
             LIMIT ?"
        );
        $stmt->execute([$memberId, $this->clubId(), $limit]);
        return $stmt->fetchAll();
    }

    public function addScore(int $wodId, int $memberId, array $data): void
    {
        $this->db->prepare(
            "INSERT INTO crossfit_scores (club_id, wod_id, member_id, score, rx, scaled, notes, score_date)
             VALUES (?,?,?,?,?,?,?,?)"
        )->execute([
            $this->clubId(), $wodId, $memberId,
            $data['score'],
            $data['rx'] ? 1 : 0,
            $data['scaled'] ? 1 : 0,
            $data['notes'] ?? null,
            $data['score_date'] ?? date('Y-m-d'),
        ]);
    }
}
