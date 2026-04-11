<?php

namespace App\Models;

class CalendarEventModel extends ClubScopedModel
{
    protected string $table = 'calendar_events';

    public function listForMonth(int $year, int $month): array
    {
        $clubId = $this->clubId();
        $from   = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $to     = date('Y-m-t 23:59:59', strtotime($from));
        $sql    = "SELECT ce.*, cec.name AS category_name, cec.color AS category_color, cec.icon AS category_icon,
                          s.name AS sport_name
                   FROM calendar_events ce
                   LEFT JOIN calendar_event_categories cec ON cec.id = ce.category_id
                   LEFT JOIN sports s ON s.id = ce.sport_id
                   WHERE ce.start_at BETWEEN ? AND ?";
        $params = [$from, $to];
        if ($clubId !== null) { $sql .= " AND ce.club_id = ?"; $params[] = $clubId; }
        $sql .= " ORDER BY ce.start_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listUpcoming(int $days = 30): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT ce.*, cec.name AS category_name, cec.color AS category_color
                   FROM calendar_events ce
                   LEFT JOIN calendar_event_categories cec ON cec.id = ce.category_id
                   WHERE ce.start_at >= NOW() AND ce.start_at <= DATE_ADD(NOW(), INTERVAL ? DAY)";
        $params = [$days];
        if ($clubId !== null) { $sql .= " AND ce.club_id = ?"; $params[] = $clubId; }
        $sql .= " ORDER BY ce.start_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
