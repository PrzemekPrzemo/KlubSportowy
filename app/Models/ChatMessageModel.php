<?php

namespace App\Models;

/**
 * Model wiadomosci w komunikatorze (chat_messages).
 *
 * Operacje INSERT/SELECT zawsze parametryzuja club_id (multi-tenant guard).
 * Wywolujacy MUSI uprzednio zweryfikowac, ze sender jest participant
 * watku (MessageThreadModel::isParticipant).
 */
class ChatMessageModel extends BaseModel
{
    protected string $table = 'chat_messages';

    /**
     * Wiadomosci w watku, opcjonalnie tylko po $afterId (do SSE/long-poll delta).
     * Zwraca dane wraz z first_name/last_name nadawcy.
     *
     * @return array<int,array<string,mixed>>
     */
    public function forThread(int $threadId, int $afterId = 0, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $sql = "
            SELECT cm.id, cm.thread_id, cm.sender_member_id, cm.body,
                   cm.attachment_path, cm.created_at,
                   m.first_name, m.last_name, m.photo_path
            FROM chat_messages cm
            JOIN members m ON m.id = cm.sender_member_id
            WHERE cm.thread_id = ?
              AND cm.deleted_at IS NULL
              AND cm.id > ?
            ORDER BY cm.id ASC
            LIMIT {$limit}
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$threadId, $afterId]);
        return $stmt->fetchAll();
    }

    /**
     * Ostatnie N wiadomosci (do initial load — pokazujemy najnowsze, ASC po id).
     *
     * @return array<int,array<string,mixed>>
     */
    public function latestForThread(int $threadId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $sql = "
            SELECT * FROM (
                SELECT cm.id, cm.thread_id, cm.sender_member_id, cm.body,
                       cm.attachment_path, cm.created_at,
                       m.first_name, m.last_name, m.photo_path
                FROM chat_messages cm
                JOIN members m ON m.id = cm.sender_member_id
                WHERE cm.thread_id = ? AND cm.deleted_at IS NULL
                ORDER BY cm.id DESC
                LIMIT {$limit}
            ) AS recent
            ORDER BY id ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$threadId]);
        return $stmt->fetchAll();
    }

    /**
     * Wstaw nowa wiadomosc. Zwraca id rekordu.
     */
    public function send(int $threadId, int $senderMemberId, int $clubId, string $body, ?string $attachmentPath = null): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO chat_messages
                (thread_id, sender_member_id, club_id, body, attachment_path, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$threadId, $senderMemberId, $clubId, $body, $attachmentPath]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Najwyzszy id wiadomosci w watku (do markRead / SSE last-id).
     */
    public function maxIdInThread(int $threadId): int
    {
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(id), 0) FROM chat_messages WHERE thread_id = ?");
        $stmt->execute([$threadId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Pobierz pojedyncza wiadomosc dla wlasciciela (sender == self).
     */
    public function findOwned(int $messageId, int $senderMemberId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM chat_messages
            WHERE id = ? AND sender_member_id = ? AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$messageId, $senderMemberId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
