<?php

namespace App\Models;

class MessageModel extends ClubScopedModel
{
    protected string $table = 'messages';

    /**
     * Skrzynka odbiorcza danego użytkownika / zawodnika.
     * Zwraca wiadomości główne (parent_id IS NULL) skierowane do odbiorcy.
     */
    public function inbox(int $recipientId, string $type = 'user', int $page = 1, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT m.*,
                       CASE m.sender_type
                           WHEN 'user'   THEN (SELECT full_name FROM users   WHERE id = m.sender_id)
                           WHEN 'member' THEN (SELECT CONCAT(first_name,' ',last_name) FROM members WHERE id = m.sender_id)
                       END AS sender_name,
                       (SELECT COUNT(*) FROM messages r WHERE r.parent_id = m.id) AS reply_count
                FROM messages m
                WHERE m.recipient_type = ? AND m.recipient_id = ?
                  AND m.parent_id IS NULL";
        $params = [$type, $recipientId];
        if ($clubId !== null) {
            $sql .= " AND m.club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY m.created_at DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Wiadomości wysłane przez użytkownika / zawodnika.
     */
    public function sent(int $senderId, string $type = 'user', int $page = 1, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT m.*,
                       CASE m.recipient_type
                           WHEN 'user'   THEN (SELECT full_name FROM users   WHERE id = m.recipient_id)
                           WHEN 'member' THEN (SELECT CONCAT(first_name,' ',last_name) FROM members WHERE id = m.recipient_id)
                           WHEN 'group'  THEN CONCAT('[Grupa] ', COALESCE(m.group_scope,''))
                       END AS recipient_name,
                       (SELECT COUNT(*) FROM messages r WHERE r.parent_id = m.id) AS reply_count
                FROM messages m
                WHERE m.sender_type = ? AND m.sender_id = ?
                  AND m.parent_id IS NULL";
        $params = [$type, $senderId];
        if ($clubId !== null) {
            $sql .= " AND m.club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY m.created_at DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Wątek — wiadomość główna + odpowiedzi, scoped by club_id.
     */
    public function thread(int $parentId): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT m.*,
                       CASE m.sender_type
                           WHEN 'user'   THEN (SELECT full_name FROM users   WHERE id = m.sender_id)
                           WHEN 'member' THEN (SELECT CONCAT(first_name,' ',last_name) FROM members WHERE id = m.sender_id)
                       END AS sender_name
                FROM messages m
                WHERE (m.id = ? OR m.parent_id = ?)";
        $params = [$parentId, $parentId];
        if ($clubId !== null) {
            $sql .= " AND m.club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY m.created_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Oznacz wiadomość jako przeczytaną, scoped by club_id.
     */
    public function markRead(int $id): bool
    {
        $clubId = $this->clubId();
        $sql = "UPDATE messages SET read_at = NOW() WHERE id = ? AND read_at IS NULL";
        $params = [$id];
        if ($clubId !== null) {
            $sql .= " AND club_id = ?";
            $params[] = $clubId;
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Liczba nieprzeczytanych wiadomości.
     */
    public function countUnread(int $recipientId, string $type = 'user'): int
    {
        $clubId = $this->clubId();
        $sql = "SELECT COUNT(*) FROM messages
                WHERE recipient_type = ? AND recipient_id = ?
                  AND read_at IS NULL AND parent_id IS NULL";
        $params = [$type, $recipientId];
        if ($clubId !== null) {
            $sql .= " AND club_id = ?";
            $params[] = $clubId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
