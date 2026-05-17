<?php

namespace App\Sports\Support\Models;

use App\Models\ClubScopedModel;

/**
 * Wspolny model dla sportow ocenianych przez sedziow:
 * figure_skating, gymnastics, dance_sport.
 *
 * Tabela: sport_judged_performances + sport_judge_scores (1:N).
 */
class JudgedPerformanceModel extends ClubScopedModel
{
    protected string $table = 'sport_judged_performances';

    public function listForSport(string $sportKey, ?int $memberId = null, int $limit = 200): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT p.*, m.first_name, m.last_name, m.member_number
                FROM sport_judged_performances p
                JOIN members m ON m.id = p.member_id
                WHERE p.club_id = ? AND p.sport_key = ?";
        $params = [$clubId, $sportKey];
        if ($memberId !== null) {
            $sql .= " AND p.member_id = ?";
            $params[] = $memberId;
        }
        $sql .= " ORDER BY p.performed_at DESC LIMIT " . max(1, (int)$limit);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function seasonBest(string $sportKey, int $memberId, ?string $routineType = null): ?array
    {
        $clubId = $this->clubId();
        $sql = "SELECT * FROM sport_judged_performances
                WHERE club_id = ? AND sport_key = ? AND member_id = ? AND total_score IS NOT NULL";
        $params = [$clubId, $sportKey, $memberId];
        if ($routineType !== null) {
            $sql .= " AND routine_type = ?";
            $params[] = $routineType;
        }
        $sql .= " ORDER BY total_score DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findOwnedById(int $id, string $sportKey): ?array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM sport_judged_performances WHERE id = ? AND club_id = ? AND sport_key = ?"
        );
        $stmt->execute([$id, $clubId, $sportKey]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Liczy total ze skladowych. Zalezne od dyscypliny — sumuje tylko niepuste pola
     * i odejmuje deductions.
     */
    public static function calcTotal(array $data): ?float
    {
        $sum = 0.0;
        $any = false;
        foreach (['technical_score','presentation_score','difficulty_score','execution_score'] as $k) {
            if (isset($data[$k]) && $data[$k] !== '' && $data[$k] !== null) {
                $sum += (float)$data[$k];
                $any = true;
            }
        }
        if (!$any) return null;
        $sum -= (float)($data['deductions'] ?? 0);
        return round($sum, 2);
    }

    /**
     * Doda jedna ocene sedziego do istniejacego performance.
     */
    public function addJudgeScore(int $performanceId, array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO sport_judge_scores
             (performance_id, judge_name, judge_certification, score_category, score_value, notes)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $performanceId,
            (string)($data['judge_name'] ?? ''),
            !empty($data['judge_certification']) ? (string)$data['judge_certification'] : null,
            !empty($data['score_category']) ? (string)$data['score_category'] : null,
            (float)($data['score_value'] ?? 0),
            !empty($data['notes']) ? (string)$data['notes'] : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function judgeScoresFor(int $performanceId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sport_judge_scores WHERE performance_id = ? ORDER BY id ASC"
        );
        $stmt->execute([$performanceId]);
        return $stmt->fetchAll();
    }

    public function deleteJudgeScore(int $scoreId, int $performanceId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM sport_judge_scores WHERE id = ? AND performance_id = ?"
        );
        return $stmt->execute([$scoreId, $performanceId]);
    }
}
