<?php

namespace App\Models;

use App\Helpers\ClubContext;
use PDO;

class SportHistoryModel extends BaseModel
{
    protected string $table = 'members';

    /**
     * Returns a unified sport history timeline for a member: belts + competition results.
     * Each row has: type (belt|result), sport_key, sport_name, title, subtitle, event_date.
     */
    public function timelineForMember(int $memberId): array
    {
        $clubId = ClubContext::current();
        $rows   = [];

        // Belt tables map: sport_key => [table, level_col, date_col]
        $beltTables = [
            ['judo',      'judo_belts',      'belt_level', 'granted_date', 'Judo'],
            ['karate',    'karate_belts',    'belt_level', 'granted_date', 'Karate'],
            ['taekwondo', 'taekwondo_belts', 'belt_level', 'granted_date', 'Taekwondo'],
            ['aikido',    'aikido_belts',    'belt_level', 'granted_date', 'Aikido'],
            ['sambo',     'sambo_belts',     'belt_level', 'granted_date', 'Sambo'],
        ];

        foreach ($beltTables as [$key, $table, $levelCol, $dateCol, $name]) {
            try {
                $stmt = $this->db->prepare(
                    "SELECT '{$key}' AS sport_key, '{$name}' AS sport_name,
                            'belt' AS type, {$levelCol} AS detail, {$dateCol} AS event_date,
                            examiner, location
                     FROM {$table}
                     WHERE member_id = ? AND club_id = ?"
                );
                $stmt->execute([$memberId, $clubId]);
                $rows = array_merge($rows, $stmt->fetchAll(PDO::FETCH_ASSOC));
            } catch (\Throwable) {
                // table might not exist yet
            }
        }

        // Competition results tables map
        $resultTables = [
            ['judo',      'judo_results',      'competition_name', 'competition_date', 'Judo'],
            ['karate',    'karate_results',    'competition_name', 'competition_date', 'Karate'],
            ['taekwondo', 'taekwondo_results', 'competition_name', 'competition_date', 'Taekwondo'],
            ['aikido',    'aikido_results',    'competition_name', 'competition_date', 'Aikido'],
            ['sambo',     'sambo_results',     'competition_name', 'competition_date', 'Sambo'],
            ['swimming',  'swimming_results',  'competition_name', 'competition_date', 'Pływanie'],
            ['wrestling', 'wrestling_results', 'competition_name', 'competition_date', 'Zapasy'],
            ['athletics', 'athletics_results', 'competition_name', 'competition_date', 'Lekka atletyka'],
        ];

        foreach ($resultTables as [$key, $table, $nameCol, $dateCol, $name]) {
            try {
                $stmt = $this->db->prepare(
                    "SELECT '{$key}' AS sport_key, '{$name}' AS sport_name,
                            'result' AS type, {$nameCol} AS detail, {$dateCol} AS event_date,
                            placement
                     FROM {$table}
                     WHERE member_id = ? AND club_id = ?"
                );
                $stmt->execute([$memberId, $clubId]);
                $rows = array_merge($rows, $stmt->fetchAll(PDO::FETCH_ASSOC));
            } catch (\Throwable) {
                // table might not exist yet
            }
        }

        // Sort by event_date descending
        usort($rows, fn($a, $b) => strcmp($b['event_date'], $a['event_date']));
        return $rows;
    }
}
