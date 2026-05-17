<?php

namespace App\Sports\Support\Models;

use App\Models\ClubScopedModel;

class ClimbingRouteLibraryModel extends ClubScopedModel
{
    protected string $table = 'sport_climbing_routes';

    public static array $DISCIPLINES = [
        'lead'       => 'Lead (z dolnej)',
        'bouldering' => 'Bouldering',
        'speed'      => 'Speed',
    ];

    public function listForClub(?string $discipline = null, bool $activeOnly = true): array
    {
        $sql = "SELECT r.*,
                       (SELECT COUNT(*) FROM sport_climbing_attempts a WHERE a.route_id = r.id) AS attempts_count,
                       (SELECT COUNT(*) FROM sport_climbing_attempts a WHERE a.route_id = r.id AND a.result IN ('top','flash','onsight')) AS tops_count
                FROM sport_climbing_routes r
                WHERE r.club_id = ?";
        $params = [$this->clubId()];
        if ($discipline !== null && array_key_exists($discipline, self::$DISCIPLINES)) {
            $sql .= " AND r.discipline = ?";
            $params[] = $discipline;
        }
        if ($activeOnly) {
            $sql .= " AND r.retired_date IS NULL";
        }
        $sql .= " ORDER BY r.set_date DESC, r.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function retire(int $routeId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sport_climbing_routes SET retired_date = CURDATE()
             WHERE id = ? AND club_id = ?"
        );
        return $stmt->execute([$routeId, $this->clubId()]);
    }
}
