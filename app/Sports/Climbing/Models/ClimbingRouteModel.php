<?php

namespace App\Sports\Climbing\Models;

use App\Models\ClubScopedModel;

class ClimbingRouteModel extends ClubScopedModel
{
    protected string $table = 'climbing_routes';

    public static array $TYPES = [
        'prowadzenie' => ['label' => 'Prowadzenie (Lead)', 'class' => 'danger'],
        'buldering'   => ['label' => 'Bouldering',          'class' => 'primary'],
        'top-rope'    => ['label' => 'Top-Rope',             'class' => 'info'],
    ];

    public static array $FRENCH_GRADES = [
        '4a','4b','4c','5a','5b','5c','6a','6a+','6b','6b+','6c','6c+',
        '7a','7a+','7b','7b+','7c','7c+','8a','8a+','8b','8b+','8c','8c+','9a','9a+','9b','9b+','9c'
    ];

    public static array $V_GRADES = [
        'VB','V0','V1','V2','V3','V4','V5','V6','V7','V8','V9','V10','V11','V12','V13','V14','V15','V16','V17'
    ];

    public function listForClub(bool $includeRetired = false): array
    {
        $sql = "SELECT r.*,
                       (SELECT COUNT(*) FROM climbing_sends s WHERE s.route_id = r.id) AS send_count
                FROM climbing_routes r
                WHERE r.club_id = ?";
        if (!$includeRetired) $sql .= " AND r.retired = 0";
        $sql .= " ORDER BY r.retired ASC, r.type, r.grade_french, r.grade_v, r.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function activeRoutes(?string $type = null): array
    {
        $sql = "SELECT * FROM climbing_routes WHERE club_id = ? AND retired = 0";
        $params = [$this->clubId()];
        if ($type !== null && array_key_exists($type, self::$TYPES)) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        $sql .= " ORDER BY grade_french, name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
