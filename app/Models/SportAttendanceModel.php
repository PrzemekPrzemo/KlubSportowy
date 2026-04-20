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
     * Recent training attendance records for a specific member (portal use).
     */
    public function recentForMember(int $memberId, int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT ta.status, ta.registered_at,
                   t.start_time, t.name AS training_name, t.location,
                   s.name AS sport_name, s.color
            FROM training_attendees ta
            JOIN trainings t ON t.id = ta.training_id
            JOIN club_sports cs ON cs.id = t.club_sport_id
            JOIN sports s ON s.id = cs.sport_id
            WHERE ta.member_id = ?
              AND t.status IN ('zakonczony','w_trakcie')
            ORDER BY t.start_time DESC
            LIMIT ?
        ");
        $stmt->execute([$memberId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Per-sport monthly attendance summary for a single member (portal use).
     * Returns: [sport_name => [month => [attended, total]]]
     */
    public function memberYearlySummary(int $memberId, int $year): array
    {
        $stmt = $this->db->prepare("
            SELECT s.name AS sport_name,
                   MONTH(t.start_time) AS month,
                   COUNT(DISTINCT t.id) AS total_trainings,
                   SUM(ta.status IN ('obecny','spozniony')) AS attended
            FROM training_attendees ta
            JOIN trainings t ON t.id = ta.training_id AND YEAR(t.start_time) = ?
            JOIN club_sports cs ON cs.id = t.club_sport_id
            JOIN sports s ON s.id = cs.sport_id
            WHERE ta.member_id = ?
            GROUP BY s.name, MONTH(t.start_time)
            ORDER BY s.name, MONTH(t.start_time)
        ");
        $stmt->execute([$year, $memberId]);
        $rows   = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $r) {
            $sn = $r['sport_name'];
            if (!isset($result[$sn])) $result[$sn] = [];
            $result[$sn][(int)$r['month']] = ['attended' => (int)$r['attended'], 'total' => (int)$r['total_trainings']];
        }
        return $result;
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
