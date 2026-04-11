<?php

namespace App\Models;

class NotificationModel extends ClubScopedModel
{
    protected string $table = 'notifications';

    public function notify(int $clubId, ?int $userId, string $type, string $title, ?string $body = null, ?string $link = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO notifications (club_id, user_id, type, title, body, link)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$clubId, $userId, $type, $title, $body, $link]);
        return (int)$this->db->lastInsertId();
    }

    public function unreadForUser(int $userId, int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications
             WHERE (user_id = ? OR user_id IS NULL)
               AND read_at IS NULL
             ORDER BY created_at DESC
             LIMIT " . (int)$limit
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function markRead(int $id, int $userId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE notifications SET read_at = NOW() WHERE id = ? AND (user_id = ? OR user_id IS NULL)"
        );
        $stmt->execute([$id, $userId]);
    }

    public function countUnread(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM notifications
             WHERE (user_id = ? OR user_id IS NULL) AND read_at IS NULL"
        );
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
}
