<?php

namespace App\Sports\Dance\Models;

use App\Models\BaseModel;

/**
 * Punkty sedziow dla pojedynczego wystepu (sport_dance_judge_scores).
 * Brak `club_id` — bezpieczenstwo poprzez FK do performance ktore ma `club_id`.
 */
class DanceJudgeScoreModel extends BaseModel
{
    protected string $table = 'sport_dance_judge_scores';

    public function listForPerformance(int $performanceId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE performance_id = ?
             ORDER BY id ASC"
        );
        $stmt->execute([$performanceId]);
        return $stmt->fetchAll();
    }

    public function addScore(int $performanceId, array $data): int
    {
        $tech = isset($data['technique_score']) && $data['technique_score'] !== '' ? round((float)$data['technique_score'], 2) : null;
        $art  = isset($data['artistry_score'])  && $data['artistry_score']  !== '' ? round((float)$data['artistry_score'], 2) : null;
        $diff = isset($data['difficulty_score']) && $data['difficulty_score'] !== '' ? round((float)$data['difficulty_score'], 2) : null;

        $parts  = array_filter([$tech, $art, $diff], fn($v) => $v !== null);
        $total  = !empty($parts) ? round(array_sum($parts) / count($parts), 2) : null;

        return $this->insert([
            'performance_id'   => $performanceId,
            'judge_name'       => trim((string)($data['judge_name'] ?? 'Sedzia')),
            'technique_score'  => $tech,
            'artistry_score'   => $art,
            'difficulty_score' => $diff,
            'total_score'      => $total,
            'notes'            => isset($data['notes']) && $data['notes'] !== '' ? trim((string)$data['notes']) : null,
        ]);
    }
}
