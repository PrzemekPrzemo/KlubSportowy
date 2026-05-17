<?php

namespace App\Sports\Dance\Models;

use App\Models\ClubScopedModel;

/**
 * Wystepy tanca w turniejach + zbiorczy wynik (sredni z judges scores).
 */
class DancePerformanceModel extends ClubScopedModel
{
    protected string $table = 'sport_dance_performances';

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT p.*,
                    m.first_name, m.last_name, m.member_number,
                    pp.first_name AS partner_first, pp.last_name AS partner_last,
                    t.name AS tournament_name, t.date_start AS tournament_date,
                    s.display_name AS style_name
             FROM `{$this->table}` p
             JOIN members m              ON m.id = p.member_id
             LEFT JOIN members pp        ON pp.id = p.partner_member_id
             JOIN tournaments t          ON t.id = p.tournament_id
             LEFT JOIN sport_dance_styles s
                    ON s.style_code = p.style_code
                   AND (s.club_id IS NULL OR s.club_id = p.club_id)
             WHERE p.club_id = ?
             ORDER BY t.date_start DESC, p.total_score DESC"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function listForTournament(int $tournamentId): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT p.*,
                    m.first_name, m.last_name, m.member_number,
                    pp.first_name AS partner_first, pp.last_name AS partner_last,
                    s.display_name AS style_name
             FROM `{$this->table}` p
             JOIN members m              ON m.id = p.member_id
             LEFT JOIN members pp        ON pp.id = p.partner_member_id
             LEFT JOIN sport_dance_styles s
                    ON s.style_code = p.style_code
                   AND (s.club_id IS NULL OR s.club_id = p.club_id)
             WHERE p.club_id = ? AND p.tournament_id = ?
             ORDER BY p.total_score DESC, p.id ASC"
        );
        $stmt->execute([$clubId, $tournamentId]);
        return $stmt->fetchAll();
    }

    public function listForMember(int $memberId): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT p.*, t.name AS tournament_name, t.date_start AS tournament_date,
                    s.display_name AS style_name
             FROM `{$this->table}` p
             JOIN tournaments t ON t.id = p.tournament_id
             LEFT JOIN sport_dance_styles s
                    ON s.style_code = p.style_code
                   AND (s.club_id IS NULL OR s.club_id = p.club_id)
             WHERE p.club_id = ? AND p.member_id = ?
             ORDER BY t.date_start DESC"
        );
        $stmt->execute([$clubId, $memberId]);
        return $stmt->fetchAll();
    }

    /** Przelicz total_score jako srednia z judge_scores i zaktualizuj. */
    public function recomputeTotal(int $performanceId): void
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT AVG(total_score) AS avg_score
             FROM sport_dance_judge_scores
             WHERE performance_id = ?"
        );
        $stmt->execute([$performanceId]);
        $avg = $stmt->fetchColumn();
        if ($avg !== null && $avg !== false) {
            $this->update($performanceId, ['total_score' => round((float)$avg, 2)]);
        }
        // Re-rank w obrebie turnieju
        $stmt = $this->db->prepare(
            "SELECT tournament_id FROM `{$this->table}` WHERE id = ? AND club_id = ?"
        );
        $stmt->execute([$performanceId, $clubId]);
        $tid = (int)$stmt->fetchColumn();
        if ($tid > 0) {
            $this->rerankTournament($tid);
        }
    }

    public function rerankTournament(int $tournamentId): void
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT id FROM `{$this->table}`
             WHERE club_id = ? AND tournament_id = ?
             ORDER BY total_score DESC, id ASC"
        );
        $stmt->execute([$clubId, $tournamentId]);
        $rank = 1;
        $upd  = $this->db->prepare(
            "UPDATE `{$this->table}` SET `rank` = ? WHERE id = ? AND club_id = ?"
        );
        foreach ($stmt->fetchAll() as $row) {
            $upd->execute([$rank++, (int)$row['id'], $clubId]);
        }
    }
}
