<?php

namespace App\Models;

/**
 * Model watkow komunikatora klubowego (message_threads).
 *
 * Multi-tenant: zawsze filtrujemy po club_id wprowadzonym przez wywolujacego.
 * Wszystkie publiczne metody wymagaja przekazania $clubId dla defense-in-depth.
 */
class MessageThreadModel extends BaseModel
{
    protected string $table = 'message_threads';

    /**
     * Lista watkow danego czlonka w klubie: ostatnia wiadomosc + unread_count + tytul.
     *
     * @return array<int,array<string,mixed>>
     */
    public function forMember(int $memberId, int $clubId): array
    {
        $sql = "
            SELECT t.id,
                   t.club_id,
                   t.thread_type,
                   t.title,
                   t.section_id,
                   t.event_id,
                   t.last_message_at,
                   p.last_read_message_id,
                   (
                       SELECT cm.body
                       FROM chat_messages cm
                       WHERE cm.thread_id = t.id AND cm.deleted_at IS NULL
                       ORDER BY cm.id DESC LIMIT 1
                   ) AS last_body,
                   (
                       SELECT cm.sender_member_id
                       FROM chat_messages cm
                       WHERE cm.thread_id = t.id AND cm.deleted_at IS NULL
                       ORDER BY cm.id DESC LIMIT 1
                   ) AS last_sender_id,
                   (
                       SELECT COUNT(*)
                       FROM chat_messages cm
                       WHERE cm.thread_id = t.id
                         AND cm.deleted_at IS NULL
                         AND cm.sender_member_id <> p.member_id
                         AND (p.last_read_message_id IS NULL OR cm.id > p.last_read_message_id)
                   ) AS unread_count
            FROM message_threads t
            JOIN message_thread_participants p ON p.thread_id = t.id
            WHERE p.member_id = ? AND t.club_id = ?
            ORDER BY (t.last_message_at IS NULL), t.last_message_at DESC, t.id DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$memberId, $clubId]);
        return $stmt->fetchAll();
    }

    /**
     * Zwraca watek wraz z lista participantow + nazwami czlonkow (do headera UI).
     * Sprawdza przynaleznosc $memberId do watku (membership guard).
     *
     * @return array{thread:array<string,mixed>,participants:array<int,array<string,mixed>>}|null
     */
    public function getThreadForMember(int $threadId, int $memberId, int $clubId): ?array
    {
        $sql = "
            SELECT t.* FROM message_threads t
            JOIN message_thread_participants p ON p.thread_id = t.id
            WHERE t.id = ? AND p.member_id = ? AND t.club_id = ?
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$threadId, $memberId, $clubId]);
        $thread = $stmt->fetch();
        if (!$thread) {
            return null;
        }

        $stmt2 = $this->db->prepare("
            SELECT p.member_id, p.last_read_message_id, p.notifications_muted,
                   m.first_name, m.last_name, m.photo_path
            FROM message_thread_participants p
            JOIN members m ON m.id = p.member_id
            WHERE p.thread_id = ?
            ORDER BY m.last_name, m.first_name
        ");
        $stmt2->execute([$threadId]);
        $participants = $stmt2->fetchAll();

        return ['thread' => $thread, 'participants' => $participants];
    }

    /**
     * Znajdz istniejacy watek 'direct' miedzy dwoma czlonkami klubu (lub null).
     */
    public function findDirectBetween(int $memberA, int $memberB, int $clubId): ?array
    {
        $sql = "
            SELECT t.*
            FROM message_threads t
            JOIN message_thread_participants p1 ON p1.thread_id = t.id AND p1.member_id = ?
            JOIN message_thread_participants p2 ON p2.thread_id = t.id AND p2.member_id = ?
            WHERE t.thread_type = 'direct' AND t.club_id = ?
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$memberA, $memberB, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Utworz watek direct + dodaj 2 participantow (transakcja).
     */
    public function createDirect(int $memberA, int $memberB, int $clubId): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO message_threads (club_id, thread_type, created_by_member_id, created_at)
                VALUES (?, 'direct', ?, NOW())
            ");
            $stmt->execute([$clubId, $memberA]);
            $threadId = (int)$this->db->lastInsertId();

            $stmt2 = $this->db->prepare("
                INSERT INTO message_thread_participants (thread_id, member_id, joined_at)
                VALUES (?, ?, NOW()), (?, ?, NOW())
            ");
            $stmt2->execute([$threadId, $memberA, $threadId, $memberB]);

            $this->db->commit();
            return $threadId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Sprawdz czy member nalezy do watku (membership guard, defense-in-depth).
     */
    public function isParticipant(int $threadId, int $memberId, int $clubId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM message_thread_participants p
            JOIN message_threads t ON t.id = p.thread_id
            WHERE p.thread_id = ? AND p.member_id = ? AND t.club_id = ?
            LIMIT 1
        ");
        $stmt->execute([$threadId, $memberId, $clubId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Lista innych member_id w watku (do FCM push i powiadomien).
     *
     * @return int[]
     */
    public function otherParticipantIds(int $threadId, int $excludeMemberId): array
    {
        $stmt = $this->db->prepare("
            SELECT member_id FROM message_thread_participants
            WHERE thread_id = ? AND member_id <> ? AND notifications_muted = 0
        ");
        $stmt->execute([$threadId, $excludeMemberId]);
        $ids = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $v) {
            $ids[] = (int)$v;
        }
        return $ids;
    }

    public function touchLastMessage(int $threadId): void
    {
        $stmt = $this->db->prepare("UPDATE message_threads SET last_message_at = NOW() WHERE id = ?");
        $stmt->execute([$threadId]);
    }

    /**
     * Ustaw last_read_message_id dla membera w watku.
     */
    public function markRead(int $threadId, int $memberId, int $lastMessageId): void
    {
        $stmt = $this->db->prepare("
            UPDATE message_thread_participants
            SET last_read_message_id = GREATEST(COALESCE(last_read_message_id, 0), ?)
            WHERE thread_id = ? AND member_id = ?
        ");
        $stmt->execute([$lastMessageId, $threadId, $memberId]);
    }

    /**
     * Sumaryczny unread_count po wszystkich watkach (do badge w navie).
     */
    public function totalUnreadForMember(int $memberId, int $clubId): int
    {
        $sql = "
            SELECT COALESCE(SUM(unread), 0) AS total FROM (
                SELECT (
                    SELECT COUNT(*)
                    FROM chat_messages cm
                    WHERE cm.thread_id = t.id
                      AND cm.deleted_at IS NULL
                      AND cm.sender_member_id <> p.member_id
                      AND (p.last_read_message_id IS NULL OR cm.id > p.last_read_message_id)
                ) AS unread
                FROM message_threads t
                JOIN message_thread_participants p ON p.thread_id = t.id
                WHERE p.member_id = ? AND t.club_id = ?
            ) AS sub
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$memberId, $clubId]);
        return (int)$stmt->fetchColumn();
    }
}
