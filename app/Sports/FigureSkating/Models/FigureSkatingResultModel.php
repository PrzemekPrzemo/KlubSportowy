<?php

namespace App\Sports\FigureSkating\Models;

use App\Models\ClubScopedModel;

class FigureSkatingResultModel extends ClubScopedModel
{
    protected string $table = 'figure_skating_results';

    public static array $DISCIPLINES = [
        'singles_m' => 'Solo mężczyźni',
        'singles_w' => 'Solo kobiety',
        'pairs'     => 'Pary sportowe',
        'ice_dance' => 'Taniec na lodzie',
        'synchro'   => 'Drużynowe (synchro)',
    ];

    public static array $LEVELS = [
        'novice'  => 'Novice',
        'junior'  => 'Junior',
        'senior'  => 'Senior',
        'adult'   => 'Adult',
        'masters' => 'Masters',
    ];

    public function listForClub(?int $memberId = null, ?string $discipline = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT r.*, m.first_name, m.last_name, m.member_number
                FROM figure_skating_results r
                JOIN members m ON m.id = r.member_id
                WHERE r.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) { $sql .= " AND r.member_id = ?"; $params[] = $memberId; }
        if ($discipline !== null && array_key_exists($discipline, self::$DISCIPLINES)) {
            $sql .= " AND r.discipline = ?"; $params[] = $discipline;
        }
        $sql .= " ORDER BY r.event_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function bestPerDiscipline(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT discipline, level, MAX(total_score) AS best_total
             FROM figure_skating_results
             WHERE club_id = ? AND member_id = ? AND total_score IS NOT NULL
             GROUP BY discipline, level"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetchAll();
    }
}
