<?php

namespace App\Models;

class AnnouncementModel extends ClubScopedModel
{
    protected string $table = 'announcements';

    public function listForClub(int $page = 1, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT a.*, u.full_name AS author_name, s.name AS sport_name
                   FROM announcements a
                   LEFT JOIN users u  ON u.id = a.author_id
                   LEFT JOIN sports s ON s.id = a.sport_id
                   WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND a.club_id = ?"; $params[] = $clubId; }
        $sql .= " ORDER BY a.priority = 'urgent' DESC, a.priority = 'important' DESC, a.created_at DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function activePublished(): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT * FROM announcements
                   WHERE published = 1
                     AND (publish_from IS NULL OR publish_from <= NOW())
                     AND (publish_to   IS NULL OR publish_to   >= NOW())";
        $params = [];
        if ($clubId !== null) { $sql .= " AND club_id = ?"; $params[] = $clubId; }
        $sql .= " ORDER BY priority = 'urgent' DESC, priority = 'important' DESC, created_at DESC LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
