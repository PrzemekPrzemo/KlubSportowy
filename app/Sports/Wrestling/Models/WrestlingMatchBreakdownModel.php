<?php

namespace App\Sports\Wrestling\Models;

use App\Models\ClubScopedModel;

/**
 * Techniczny breakdown meczu zapasniczego (per-zawodnik).
 * Tabela `sport_wrestling_match_breakdown`. FK do `tournament_matches`.
 */
class WrestlingMatchBreakdownModel extends ClubScopedModel
{
    protected string $table = 'sport_wrestling_match_breakdown';

    public function listForMember(int $memberId, int $limit = 50): array
    {
        $limit = max(1, min(500, $limit));
        $stmt  = $this->db->prepare(
            "SELECT b.*
             FROM sport_wrestling_match_breakdown b
             WHERE b.club_id = ? AND b.member_id = ?
             ORDER BY b.id DESC
             LIMIT {$limit}"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetchAll();
    }

    public function listForMatch(int $matchId): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, m.first_name, m.last_name
             FROM sport_wrestling_match_breakdown b
             JOIN members m ON m.id = b.member_id
             WHERE b.club_id = ? AND b.match_id = ?
             ORDER BY b.id"
        );
        $stmt->execute([$this->clubId(), $matchId]);
        return $stmt->fetchAll();
    }

    public function statsForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(takedowns),0)      AS takedowns,
                COALESCE(SUM(exposures),0)      AS exposures,
                COALESCE(SUM(escapes),0)        AS escapes,
                COALESCE(SUM(technical_fall),0) AS technical_falls,
                COALESCE(SUM(pin),0)            AS pins,
                COALESCE(SUM(caution_count),0)  AS cautions,
                COUNT(*)                         AS matches_logged
             FROM sport_wrestling_match_breakdown
             WHERE club_id = ? AND member_id = ?"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        $row = $stmt->fetch() ?: [];
        // Ensure ints
        foreach (['takedowns','exposures','escapes','technical_falls','pins','cautions','matches_logged'] as $k) {
            $row[$k] = (int)($row[$k] ?? 0);
        }
        return $row;
    }

    public function memberBelongsToClub(int $memberId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM members WHERE id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([$memberId, (int)$this->clubId()]);
        return (bool)$stmt->fetchColumn();
    }

    public function matchBelongsToClub(int $matchId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1
             FROM tournament_matches tm
             JOIN tournaments t ON t.id = tm.tournament_id
             WHERE tm.id = ? AND t.club_id = ? LIMIT 1"
        );
        $stmt->execute([$matchId, (int)$this->clubId()]);
        return (bool)$stmt->fetchColumn();
    }
}
