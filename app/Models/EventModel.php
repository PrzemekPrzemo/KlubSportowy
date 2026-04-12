<?php

namespace App\Models;

class EventModel extends ClubScopedModel
{
    protected string $table = 'events';

    public function listForClub(?int $sportId = null, ?string $type = null, ?string $from = null, int $page = 1, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT e.*, s.name AS sport_name, s.`key` AS sport_key, s.color AS sport_color,
                          th.name AS home_team_name, ta.name AS away_team_name
                   FROM events e
                   LEFT JOIN sports s ON s.id = e.sport_id
                   LEFT JOIN teams th ON th.id = e.home_team_id
                   LEFT JOIN teams ta ON ta.id = e.away_team_id
                   WHERE 1=1";
        $params = [];

        if ($clubId !== null) {
            $sql     .= " AND e.club_id = ?";
            $params[] = $clubId;
        }
        if ($sportId !== null) {
            $sql     .= " AND e.sport_id = ?";
            $params[] = $sportId;
        }
        if ($type !== null && $type !== '') {
            $sql     .= " AND e.type = ?";
            $params[] = $type;
        }
        if ($from !== null) {
            $sql     .= " AND e.event_date >= ?";
            $params[] = $from;
        }

        $sql .= " ORDER BY e.event_date DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function upcomingForClub(int $limit = 5): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT e.*, s.name AS sport_name
                   FROM events e
                   LEFT JOIN sports s ON s.id = e.sport_id
                   WHERE e.event_date >= NOW()";
        $params = [];
        if ($clubId !== null) {
            $sql .= " AND e.club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY e.event_date ASC LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
