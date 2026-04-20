<?php

namespace App\Models;

use App\Helpers\ClubContext;
use PDO;

class SportAttendanceModel extends BaseModel
{
    protected string $table = 'training_attendees';

    /**
     * Returns monthly attendance stats per member for a given sport key and year.
     * Pivoted in PHP: [memberId => ['name' => ..., 'months' => [1 => [attended, total], ...], 'sum_attended', 'sum_total']]
     */
    public function monthlySummary(string $sportKey, int $year): array
    {
        $clubId = ClubContext::current();
        $stmt = $this->db->prepare("
            SELECT
                m.id            AS member_id,
                m.first_name,
                m.last_name,
                m.member_number,
                MONTH(t.start_time)  AS month,
                COUNT(DISTINCT t.id) AS total_trainings,
                SUM(ta.status IN ('obecny','spozniony')) AS attended
            FROM training_attendees ta
            JOIN trainings t ON t.id = ta.training_id
                AND YEAR(t.start_time) = ?
                AND t.club_id = ?
            JOIN sports s ON s.id = t.sport_id AND s.`key` = ?
            JOIN members m ON m.id = ta.member_id AND m.club_id = ?
            GROUP BY m.id, MONTH(t.start_time)
            ORDER BY m.last_name, m.first_name, MONTH(t.start_time)
        ");
        $stmt->execute([$year, $clubId, $sportKey, $clubId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pivoted = [];
        foreach ($rows as $r) {
            $mid = (int)$r['member_id'];
            if (!isset($pivoted[$mid])) {
                $pivoted[$mid] = [
                    'member_id'     => $mid,
                    'first_name'    => $r['first_name'],
                    'last_name'     => $r['last_name'],
                    'member_number' => $r['member_number'],
                    'months'        => [],
                    'sum_attended'  => 0,
                    'sum_total'     => 0,
                ];
            }
            $m = (int)$r['month'];
            $pivoted[$mid]['months'][$m] = [
                'attended' => (int)$r['attended'],
                'total'    => (int)$r['total_trainings'],
            ];
            $pivoted[$mid]['sum_attended'] += (int)$r['attended'];
            $pivoted[$mid]['sum_total']    += (int)$r['total_trainings'];
        }

        return array_values($pivoted);
    }

    /**
     * Returns list of months (1–12) that had at least one training for this sport and year.
     */
    public function activeMonths(string $sportKey, int $year): array
    {
        $clubId = ClubContext::current();
        $stmt = $this->db->prepare("
            SELECT DISTINCT MONTH(t.start_time) AS month
            FROM trainings t
            JOIN sports s ON s.id = t.sport_id AND s.`key` = ?
            WHERE YEAR(t.start_time) = ?
              AND t.club_id = ?
            ORDER BY month
        ");
        $stmt->execute([$sportKey, $year, $clubId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Returns distinct years with trainings for this sport.
     */
    public function years(string $sportKey): array
    {
        $clubId = ClubContext::current();
        $stmt = $this->db->prepare("
            SELECT DISTINCT YEAR(t.start_time) AS year
            FROM trainings t
            JOIN sports s ON s.id = t.sport_id AND s.`key` = ?
            WHERE t.club_id = ?
            ORDER BY year DESC
        ");
        $stmt->execute([$sportKey, $clubId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
