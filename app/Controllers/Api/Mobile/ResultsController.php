<?php

namespace App\Controllers\Api\Mobile;

use App\Helpers\Database;

/**
 * Mobile API v1 — results & rankings (read-only stubs for MVP).
 * Pulls best-effort data from existing tournaments/rankings tables.
 * If specific tables are absent in some installs, returns an empty list.
 */
class ResultsController extends V1Controller
{
    /** GET /api/mobile/v1/results — last ~20 tournament results for current member. */
    public function index(): void
    {
        $this->requireAuth();
        try {
            $stmt = Database::pdo()->prepare(
                "SELECT tp.tournament_id, t.name AS tournament_name,
                        tp.seed, tp.eliminated
                 FROM tournament_participants tp
                 JOIN tournaments t ON t.id = tp.tournament_id
                 WHERE tp.member_id = ? AND t.club_id = ?
                 ORDER BY tp.id DESC
                 LIMIT 20"
            );
            $stmt->execute([$this->memberId, $this->clubId]);
            $this->json($stmt->fetchAll());
        } catch (\Throwable $e) {
            // Schema may differ across installs; degrade gracefully.
            $this->json([]);
        }
    }

    /** GET /api/mobile/v1/rankings — current member ranking positions per sport. */
    public function rankings(): void
    {
        $this->requireAuth();
        try {
            $stmt = Database::pdo()->prepare(
                "SELECT sr.sport_key, sr.season, sr.ranking_points, sr.ranking_position,
                        sr.competitions_count, sr.wins
                 FROM sport_rankings sr
                 WHERE sr.member_id = ? AND sr.club_id = ?
                 ORDER BY sr.season DESC, sr.sport_key"
            );
            $stmt->execute([$this->memberId, $this->clubId]);
            $this->json($stmt->fetchAll());
        } catch (\Throwable $e) {
            $this->json([]);
        }
    }
}
