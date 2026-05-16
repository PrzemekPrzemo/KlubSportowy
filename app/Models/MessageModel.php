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

    // ============================================================
    // Mobile member-to-member chat (Phase: real-time messages)
    // All methods below operate ONLY on sender_type=member,
    // recipient_type=member rows for the given $clubId.
    // ============================================================

    /**
     * List of conversation threads (= distinct peer_member_id) for current
     * member, with last message preview + unread count, cursor by last id desc.
     *
     * Cursor semantics: last_message_id of the last item from previous page.
     * Returns at most $limit threads. next_cursor = null when no more.
     */
    public function memberThreads(int $clubId, int $meId, ?int $cursor, int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));

        // Step 1: distinct peers + last message id per peer (newest first).
        $sql = "SELECT peer_id, MAX(id) AS last_message_id
                FROM (
                    SELECT id,
                           CASE WHEN sender_id = :me THEN recipient_id ELSE sender_id END AS peer_id
                    FROM messages
                    WHERE club_id = :club
                      AND sender_type = 'member' AND recipient_type = 'member'
                      AND ((sender_id = :me2 AND recipient_id IS NOT NULL)
                           OR recipient_id = :me3)
                ) p
                GROUP BY peer_id
                ORDER BY last_message_id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':me'   => $meId,
            ':club' => $clubId,
            ':me2'  => $meId,
            ':me3'  => $meId,
        ]);
        $peers = $stmt->fetchAll();

        if (empty($peers)) {
            return ['data' => [], 'next_cursor' => null];
        }

        // Apply cursor (cursor = last_message_id of previous page tail).
        if ($cursor !== null && $cursor > 0) {
            $peers = array_values(array_filter($peers, fn($r) => (int)$r['last_message_id'] < $cursor));
        }

        $hasMore = count($peers) > $limit;
        $peers   = array_slice($peers, 0, $limit);
        $nextCursor = $hasMore && !empty($peers) ? (int)end($peers)['last_message_id'] : null;

        if (empty($peers)) {
            return ['data' => [], 'next_cursor' => null];
        }

        $peerIds   = array_map(fn($r) => (int)$r['peer_id'], $peers);
        $lastMsgIds = array_map(fn($r) => (int)$r['last_message_id'], $peers);

        // Step 2: load last messages.
        $in = implode(',', array_fill(0, count($lastMsgIds), '?'));
        $sql = "SELECT id, sender_id, recipient_id, subject, body, created_at
                FROM messages WHERE id IN ($in)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($lastMsgIds);
        $lastById = [];
        foreach ($stmt->fetchAll() as $row) {
            $lastById[(int)$row['id']] = $row;
        }

        // Step 3: peer member rows.
        $in2 = implode(',', array_fill(0, count($peerIds), '?'));
        $stmt = $this->db->prepare(
            "SELECT id, first_name, last_name, photo_path FROM members WHERE id IN ($in2) AND club_id = ?"
        );
        $stmt->execute([...$peerIds, $clubId]);
        $memById = [];
        foreach ($stmt->fetchAll() as $m) {
            $memById[(int)$m['id']] = $m;
        }

        // Step 4: per-peer unread count (incoming, unread).
        $sql = "SELECT sender_id AS peer_id, COUNT(*) AS c
                FROM messages
                WHERE club_id = ? AND sender_type = 'member' AND recipient_type = 'member'
                  AND recipient_id = ? AND read_at IS NULL
                  AND sender_id IN ($in2)
                GROUP BY sender_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId, $meId, ...$peerIds]);
        $unreadByPeer = [];
        foreach ($stmt->fetchAll() as $u) {
            $unreadByPeer[(int)$u['peer_id']] = (int)$u['c'];
        }

        // Compose result.
        $data = [];
        foreach ($peers as $p) {
            $peerId = (int)$p['peer_id'];
            $msgId  = (int)$p['last_message_id'];
            $msg    = $lastById[$msgId] ?? null;
            $mem    = $memById[$peerId] ?? null;
            if ($msg === null) continue;
            $body = (string)$msg['body'];
            $data[] = [
                'peer_member_id'       => $peerId,
                'peer_first_name'      => $mem['first_name'] ?? null,
                'peer_last_name'       => $mem['last_name']  ?? null,
                'peer_photo_url'       => self::photoUrl($mem['photo_path'] ?? null),
                'last_message_id'      => $msgId,
                'last_message_preview' => mb_substr($body, 0, 140),
                'last_message_at'      => self::isoUtc($msg['created_at']),
                'last_message_from_me' => (int)$msg['sender_id'] === $meId,
                'unread_count'         => $unreadByPeer[$peerId] ?? 0,
            ];
        }

        return ['data' => $data, 'next_cursor' => $nextCursor];
    }

    /**
     * Messages between $meId and $peerId, newest first, cursor by id desc.
     * Returns rows in API-shape (from_me etc). Does NOT mark anything as read.
     */
    public function memberThread(int $clubId, int $meId, int $peerId, ?int $cursor, int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        $sql = "SELECT id, sender_id, recipient_id, subject, body, created_at, read_at
                FROM messages
                WHERE club_id = :club
                  AND sender_type = 'member' AND recipient_type = 'member'
                  AND ((sender_id = :me AND recipient_id = :peer)
                       OR (sender_id = :peer2 AND recipient_id = :me2))";
        $params = [
            ':club' => $clubId,
            ':me'   => $meId,
            ':me2'  => $meId,
            ':peer' => $peerId,
            ':peer2'=> $peerId,
        ];
        if ($cursor !== null && $cursor > 0) {
            $sql .= " AND id < :cursor";
            $params[':cursor'] = $cursor;
        }
        $sql .= " ORDER BY id DESC LIMIT " . ($limit + 1);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $hasMore = count($rows) > $limit;
        if ($hasMore) array_pop($rows);
        $nextCursor = $hasMore && !empty($rows) ? (int)end($rows)['id'] : null;

        $data = array_map(fn($r) => self::shape($r, $meId, $peerId), $rows);
        return ['data' => $data, 'next_cursor' => $nextCursor];
    }

    /**
     * Mark every unread incoming message in a thread as read. Returns rowCount.
     */
    public function markThreadRead(int $clubId, int $meId, int $peerId): int
    {
        $stmt = $this->db->prepare(
            "UPDATE messages SET read_at = NOW()
             WHERE club_id = ? AND sender_type = 'member' AND recipient_type = 'member'
               AND sender_id = ? AND recipient_id = ? AND read_at IS NULL"
        );
        $stmt->execute([$clubId, $peerId, $meId]);
        return $stmt->rowCount();
    }

    /**
     * Mark specific message ids (incoming, current member's) as read; returns ids actually marked.
     */
    public function markIdsRead(int $clubId, int $meId, array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
        if (empty($ids)) return [];
        $in = implode(',', array_fill(0, count($ids), '?'));
        // Find which are currently unread + recipient = me.
        $stmt = $this->db->prepare(
            "SELECT id FROM messages
             WHERE club_id = ? AND recipient_type = 'member' AND recipient_id = ?
               AND read_at IS NULL AND id IN ($in)"
        );
        $stmt->execute([$clubId, $meId, ...$ids]);
        $toMark = array_map('intval', array_column($stmt->fetchAll(), 'id'));
        if (empty($toMark)) return [];
        $in2 = implode(',', array_fill(0, count($toMark), '?'));
        $stmt = $this->db->prepare(
            "UPDATE messages SET read_at = NOW() WHERE id IN ($in2)"
        );
        $stmt->execute($toMark);
        return $toMark;
    }

    /**
     * Insert direct member-to-member message. Returns inserted id.
     */
    public function createDirect(int $clubId, int $meId, int $peerId, string $body, ?string $subject = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO messages
                (club_id, sender_type, sender_id, recipient_type, recipient_id, subject, body, created_at)
             VALUES (?, 'member', ?, 'member', ?, ?, ?, NOW())"
        );
        $stmt->execute([$clubId, $meId, $peerId, $subject ?? '', $body]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Fetch a single message in API shape for given recipient/peer.
     */
    public function findShaped(int $clubId, int $meId, int $peerId, int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, sender_id, recipient_id, subject, body, created_at, read_at
             FROM messages WHERE id = ? AND club_id = ?
               AND sender_type = 'member' AND recipient_type = 'member'"
        );
        $stmt->execute([$id, $clubId]);
        $row = $stmt->fetch();
        return $row ? self::shape($row, $meId, $peerId) : null;
    }

    /**
     * New messages newer than $sinceId addressed to me (recipient) OR sent by me
     * (so mobile can reconcile multi-device send echoes). Cap at 50.
     */
    public function pollSince(int $clubId, int $meId, ?int $sinceId, int $limit = 50): array
    {
        $limit = max(1, min(50, $limit));
        $sql = "SELECT id, sender_id, recipient_id, subject, body, created_at, read_at
                FROM messages
                WHERE club_id = ?
                  AND sender_type = 'member' AND recipient_type = 'member'
                  AND (recipient_id = ? OR sender_id = ?)";
        $params = [$clubId, $meId, $meId];
        if ($sinceId !== null && $sinceId > 0) {
            $sql .= " AND id > ?";
            $params[] = $sinceId;
        }
        $sql .= " ORDER BY id ASC LIMIT " . $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        return array_map(function ($r) use ($meId) {
            $peer = (int)$r['sender_id'] === $meId ? (int)$r['recipient_id'] : (int)$r['sender_id'];
            return self::shape($r, $meId, $peer);
        }, $rows);
    }

    /**
     * Count of unread incoming messages + number of distinct peers with unread.
     */
    public function unreadStats(int $clubId, int $meId): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS unread, COUNT(DISTINCT sender_id) AS unread_threads
             FROM messages
             WHERE club_id = ? AND sender_type = 'member' AND recipient_type = 'member'
               AND recipient_id = ? AND read_at IS NULL"
        );
        $stmt->execute([$clubId, $meId]);
        $row = $stmt->fetch();
        return [
            'unread'         => (int)($row['unread'] ?? 0),
            'unread_threads' => (int)($row['unread_threads'] ?? 0),
        ];
    }

    private static function shape(array $row, int $meId, int $peerId): array
    {
        return [
            'id'         => (int)$row['id'],
            'from_me'    => (int)$row['sender_id'] === $meId,
            'peer_id'    => $peerId,
            'subject'    => ($row['subject'] ?? '') !== '' ? $row['subject'] : null,
            'body'       => (string)$row['body'],
            'created_at' => self::isoUtc($row['created_at']),
            'read_at'    => $row['read_at'] !== null ? self::isoUtc($row['read_at']) : null,
        ];
    }

    private static function isoUtc(?string $mysqlDateTime): ?string
    {
        if ($mysqlDateTime === null || $mysqlDateTime === '') return null;
        try {
            return (new \DateTime($mysqlDateTime, new \DateTimeZone(date_default_timezone_get())))
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s\Z');
        } catch (\Throwable $e) {
            return $mysqlDateTime;
        }
    }

    private static function photoUrl(?string $relPath): ?string
    {
        if ($relPath === null || $relPath === '') return null;
        if (preg_match('#^https?://#i', $relPath)) return $relPath;
        $base = rtrim((string)($_ENV['APP_URL'] ?? $_SERVER['HTTP_ORIGIN'] ?? ''), '/');
        if ($base === '') {
            // Build from current request as fallback.
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? '';
            if ($host !== '') $base = $scheme . '://' . $host;
        }
        return $base . '/' . ltrim($relPath, '/');
    }
}
