<?php

namespace App\Sports\Support\Models;

use App\Models\ClubScopedModel;

class CrossFitResultModel extends ClubScopedModel
{
    protected string $table = 'sport_crossfit_results';

    public static array $LEVELS = [
        'RX'          => 'RX (skala oryginalna)',
        'scaled'      => 'Scaled',
        'foundations' => 'Foundations',
    ];

    public function listForWod(int $wodId, int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, m.first_name, m.last_name, m.member_number
             FROM sport_crossfit_results r
             JOIN members m ON m.id = r.member_id
             WHERE r.club_id = ? AND r.wod_id = ?
             ORDER BY r.scaled_or_rx ASC,
                      CASE WHEN r.result_time_seconds IS NOT NULL THEN r.result_time_seconds ELSE 999999 END ASC,
                      r.result_reps DESC,
                      r.result_load_kg DESC
             LIMIT " . max(1, (int)$limit)
        );
        $stmt->execute([$this->clubId(), $wodId]);
        return $stmt->fetchAll();
    }

    public function listForMember(int $memberId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, w.name AS wod_name, w.type AS wod_type
             FROM sport_crossfit_results r
             JOIN sport_crossfit_wods w ON w.id = r.wod_id
             WHERE r.club_id = ? AND r.member_id = ?
             ORDER BY r.recorded_at DESC
             LIMIT " . max(1, (int)$limit)
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetchAll();
    }

    /**
     * Leaderboard per WOD: lepszy = mniej sekund (for_time) lub wiecej reps (amrap).
     */
    public function leaderboard(int $wodId, string $wodType, int $limit = 20): array
    {
        $clubId = $this->clubId();
        if (in_array($wodType, ['for_time'], true)) {
            $stmt = $this->db->prepare(
                "SELECT r.*, m.first_name, m.last_name
                 FROM sport_crossfit_results r
                 JOIN members m ON m.id = r.member_id
                 WHERE r.club_id = ? AND r.wod_id = ? AND r.result_time_seconds IS NOT NULL
                 ORDER BY r.scaled_or_rx = 'RX' DESC, r.result_time_seconds ASC
                 LIMIT " . max(1, (int)$limit)
            );
        } elseif (in_array($wodType, ['amrap','rounds_reps'], true)) {
            $stmt = $this->db->prepare(
                "SELECT r.*, m.first_name, m.last_name
                 FROM sport_crossfit_results r
                 JOIN members m ON m.id = r.member_id
                 WHERE r.club_id = ? AND r.wod_id = ? AND r.result_reps IS NOT NULL
                 ORDER BY r.scaled_or_rx = 'RX' DESC, r.result_reps DESC
                 LIMIT " . max(1, (int)$limit)
            );
        } else {
            $stmt = $this->db->prepare(
                "SELECT r.*, m.first_name, m.last_name
                 FROM sport_crossfit_results r
                 JOIN members m ON m.id = r.member_id
                 WHERE r.club_id = ? AND r.wod_id = ? AND r.result_load_kg IS NOT NULL
                 ORDER BY r.scaled_or_rx = 'RX' DESC, r.result_load_kg DESC
                 LIMIT " . max(1, (int)$limit)
            );
        }
        $stmt->execute([$clubId, $wodId]);
        return $stmt->fetchAll();
    }
}
