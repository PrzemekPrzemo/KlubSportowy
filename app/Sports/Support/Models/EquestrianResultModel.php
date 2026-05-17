<?php

namespace App\Sports\Support\Models;

use App\Models\ClubScopedModel;

/**
 * Wyniki w nowym schemacie sport_equestrian_results (z FK do sport_equestrian_horses).
 * Stary EquestrianResultModel pozostawiamy nietkniety — to obsluga FEI/dressage/jumping
 * w pelnym schema 106.
 */
class EquestrianResultModel extends ClubScopedModel
{
    protected string $table = 'sport_equestrian_results';

    public static array $DISCIPLINES = [
        'dressage'  => 'Ujeżdżenie',
        'jumping'   => 'Skoki',
        'eventing'  => 'WKKW (eventing)',
        'vaulting'  => 'Woltyżerka',
        'endurance' => 'Rajdy długodystansowe',
        'para'      => 'Para-jeździectwo',
    ];

    public function listForClub(?string $discipline = null, int $limit = 200): array
    {
        $sql = "SELECT r.*, m.first_name, m.last_name, m.member_number,
                       h.name AS horse_name, h.fei_id
                FROM sport_equestrian_results r
                JOIN members m ON m.id = r.member_id
                LEFT JOIN sport_equestrian_horses h ON h.id = r.horse_id
                WHERE r.club_id = ?";
        $params = [$this->clubId()];
        if ($discipline !== null && array_key_exists($discipline, self::$DISCIPLINES)) {
            $sql .= " AND r.discipline = ?";
            $params[] = $discipline;
        }
        $sql .= " ORDER BY r.event_date DESC, r.id DESC LIMIT " . max(1, (int)$limit);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function feiRanking(string $discipline, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.id AS member_id, m.first_name, m.last_name,
                    COUNT(*) AS starts,
                    AVG(r.score) AS avg_score,
                    MIN(r.rank_position) AS best_rank
             FROM sport_equestrian_results r
             JOIN members m ON m.id = r.member_id
             WHERE r.club_id = ? AND r.discipline = ?
             GROUP BY m.id, m.first_name, m.last_name
             ORDER BY avg_score DESC, best_rank ASC
             LIMIT " . max(1, (int)$limit)
        );
        $stmt->execute([$this->clubId(), $discipline]);
        return $stmt->fetchAll();
    }
}
