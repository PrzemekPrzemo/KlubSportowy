<?php

namespace App\Models;

class AthleteTrainingLogModel extends ClubScopedModel
{
    protected string $table = 'athlete_training_logs';

    public static array $SESSION_TYPES = [
        'trening'     => ['label' => 'Trening',      'class' => 'primary'],
        'zawody'      => ['label' => 'Zawody',       'class' => 'warning'],
        'regeneracja' => ['label' => 'Regeneracja',  'class' => 'info'],
        'sparing'     => ['label' => 'Sparing',      'class' => 'danger'],
        'test'        => ['label' => 'Test/pomiar',  'class' => 'secondary'],
    ];

    public function listForMember(int $memberId, int $limit = 100): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM athlete_training_logs
             WHERE club_id = ? AND member_id = ?
             ORDER BY log_date DESC, id DESC
             LIMIT " . max(1, (int)$limit)
        );
        $stmt->execute([$clubId, $memberId]);
        return $stmt->fetchAll();
    }

    public function weekLogs(int $memberId, string $weekStart): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM athlete_training_logs
             WHERE club_id = ? AND member_id = ?
               AND log_date BETWEEN ? AND DATE_ADD(?, INTERVAL 6 DAY)
             ORDER BY log_date ASC, id ASC"
        );
        $stmt->execute([$clubId, $memberId, $weekStart, $weekStart]);
        return $stmt->fetchAll();
    }

    public function monthlySummary(int $memberId, int $year, int $month): array
    {
        $clubId = $this->clubId();
        $from   = sprintf('%04d-%02d-01', $year, $month);
        $to     = date('Y-m-t', strtotime($from));
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*)                          AS session_count,
                COALESCE(SUM(duration_min), 0)    AS total_minutes,
                COALESCE(SUM(distance_km), 0)     AS total_distance_km,
                COALESCE(SUM(volume_kg), 0)       AS total_volume_kg,
                COALESCE(AVG(intensity), 0)       AS avg_intensity,
                sport_key
             FROM athlete_training_logs
             WHERE club_id = ? AND member_id = ?
               AND log_date BETWEEN ? AND ?
             GROUP BY sport_key
             WITH ROLLUP"
        );
        $stmt->execute([$clubId, $memberId, $from, $to]);
        return $stmt->fetchAll();
    }

    public function weeklyTotal(int $memberId, string $weekStart): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*)                        AS sessions,
                COALESCE(SUM(duration_min), 0)  AS minutes,
                COALESCE(SUM(distance_km), 0)   AS km,
                COALESCE(SUM(volume_kg), 0)     AS volume_kg
             FROM athlete_training_logs
             WHERE club_id = ? AND member_id = ?
               AND log_date BETWEEN ? AND DATE_ADD(?, INTERVAL 6 DAY)"
        );
        $stmt->execute([$clubId, $memberId, $weekStart, $weekStart]);
        return $stmt->fetch() ?: ['sessions' => 0, 'minutes' => 0, 'km' => 0, 'volume_kg' => 0];
    }
}
