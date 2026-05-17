<?php

namespace App\Controllers\Api;

use App\Helpers\Database;
use App\Helpers\PushService;
use App\Models\ChatMessageModel;
use App\Models\MemberModel;
use App\Models\MessageThreadModel;

/**
 * Member-to-member real-time chat (polling + FCM push).
 *
 * Storage: the modern `message_threads` + `chat_messages` tables
 * (migration 085). Mobile keeps a peer-id-based API surface — the
 * controller transparently finds-or-creates a `direct` thread between
 * the authed member and the peer.
 *
 * Endpoints (all require member token mt_...):
 *   GET  /api/v1/messages/threads
 *   GET  /api/v1/messages/thread/:peerId
 *   POST /api/v1/messages/thread/:peerId/read
 *   POST /api/v1/messages/send
 *   GET  /api/v1/messages/poll
 *   GET  /api/v1/messages/unread-count
 */
class MessagesApiController extends BaseApiController
{
    public function threads(): void
    {
        $this->requireMember();
        $rows = (new MessageThreadModel())->forMember($this->memberId, $this->clubId);

        $threadIds = array_map(fn($r) => (int)$r['id'], $rows);
        $peersByThread = $this->peersForDirectThreads($threadIds);
        $peerIds = array_unique(array_values($peersByThread));
        $members = $this->fetchMembers($peerIds);

        $out = [];
        foreach ($rows as $r) {
            if ($r['thread_type'] !== 'direct') continue;
            $tid = (int)$r['id'];
            $peerId = $peersByThread[$tid] ?? null;
            if ($peerId === null) continue;
            $peer = $members[$peerId] ?? null;
            $out[] = [
                'peer_member_id'       => $peerId,
                'peer_first_name'      => $peer['first_name'] ?? '',
                'peer_last_name'       => $peer['last_name'] ?? '',
                'peer_photo_url'       => $this->photoUrl($peer['photo_path'] ?? null),
                'last_message_id'      => null,
                'last_message_preview' => $r['last_body'] !== null ? mb_substr((string)$r['last_body'], 0, 140) : null,
                'last_message_at'      => $r['last_message_at'],
                'last_message_from_me' => isset($r['last_sender_id']) && (int)$r['last_sender_id'] === $this->memberId,
                'unread_count'         => (int)$r['unread_count'],
            ];
        }
        $this->json(['data' => $out, 'next_cursor' => null]);
    }

    public function thread(string $peerId): void
    {
        $this->requireMember();
        $peer = (int)$peerId;
        if ($peer <= 0) {
            $this->error('Nieprawidłowy peer_member_id.', 400, 'invalid_peer');
        }
        $this->ensureSameClub($peer);

        $threadModel = new MessageThreadModel();
        $thread = $threadModel->findDirectBetween($this->memberId, $peer, $this->clubId);

        if ($thread === null) {
            $this->json(['data' => [], 'next_cursor' => null, 'marked_read' => []]);
        }

        $threadId = (int)$thread['id'];
        $cursor = isset($_GET['cursor']) && (int)$_GET['cursor'] > 0 ? (int)$_GET['cursor'] : 0;
        $limit  = max(1, min(100, (int)($_GET['limit'] ?? 50)));

        $messages = $cursor > 0
            ? (new ChatMessageModel())->forThread($threadId, $cursor, $limit)
            : (new ChatMessageModel())->latestForThread($threadId, $limit);

        // Mark thread up to the newest message we just returned as read for the current member.
        $newestId = 0;
        foreach ($messages as $m) {
            if ((int)$m['id'] > $newestId) $newestId = (int)$m['id'];
        }
        $markedReadIds = [];
        if ($newestId > 0) {
            // Collect peer-sent unread ids before the bump (for the response payload).
            $db = Database::pdo();
            $stmt = $db->prepare(
                "SELECT cm.id
                 FROM chat_messages cm
                 JOIN message_thread_participants p
                   ON p.thread_id = cm.thread_id AND p.member_id = ?
                 WHERE cm.thread_id = ?
                   AND cm.sender_member_id <> ?
                   AND cm.deleted_at IS NULL
                   AND (p.last_read_message_id IS NULL OR cm.id > p.last_read_message_id)"
            );
            $stmt->execute([$this->memberId, $threadId, $this->memberId]);
            $markedReadIds = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
            $threadModel->markRead($threadId, $this->memberId, $newestId);
        }

        $data = array_map(
            fn($m) => $this->shapeMessage($m, $peer, in_array((int)$m['id'], $markedReadIds, true)),
            $messages
        );

        $nextCursor = count($messages) >= $limit && !empty($messages)
            ? (int)$messages[count($messages) - 1]['id']
            : null;

        $this->json([
            'data'        => $data,
            'next_cursor' => $nextCursor,
            'marked_read' => $markedReadIds,
        ]);
    }

    public function markThreadRead(string $peerId): void
    {
        $this->requireMember();
        $peer = (int)$peerId;
        if ($peer <= 0) {
            $this->error('Nieprawidłowy peer_member_id.', 400, 'invalid_peer');
        }
        $thread = (new MessageThreadModel())->findDirectBetween($this->memberId, $peer, $this->clubId);
        if ($thread === null) {
            $this->json(['marked' => 0]);
        }
        $threadId = (int)$thread['id'];
        $newestId = (new ChatMessageModel())->maxIdInThread($threadId);
        if ($newestId > 0) {
            (new MessageThreadModel())->markRead($threadId, $this->memberId, $newestId);
        }
        $this->json(['marked' => $newestId]);
    }

    public function send(): void
    {
        $this->requireMember();

        $input = json_decode(file_get_contents('php://input') ?: '', true) ?: $_POST;
        $peer  = (int)($input['peer_member_id'] ?? 0);
        $body  = trim((string)($input['body'] ?? ''));
        $subj  = isset($input['subject']) ? trim((string)$input['subject']) : null;

        if ($peer <= 0) {
            $this->error('Brak peer_member_id.', 400, 'invalid_peer');
        }
        if ($peer === $this->memberId) {
            $this->error('Nie można wysłać wiadomości do siebie.', 400, 'self_send');
        }
        if ($body === '') {
            $this->error('Treść wiadomości jest pusta.', 400, 'empty_body');
        }
        if (mb_strlen($body) > 4000) {
            $this->error('Wiadomość przekracza 4000 znaków.', 400, 'body_too_long');
        }

        $this->ensureSameClub($peer);

        $threadModel = new MessageThreadModel();
        $thread = $threadModel->findDirectBetween($this->memberId, $peer, $this->clubId);
        $threadId = $thread !== null
            ? (int)$thread['id']
            : $threadModel->createDirect($this->memberId, $peer, $this->clubId);

        $msgId = (new ChatMessageModel())->send($threadId, $this->memberId, $this->clubId, $body);

        // FCM + inbox row for peer. sender_*name included so cold-start chat
        // can render the title without an extra round-trip.
        try {
            $me = (new MemberModel())->findById($this->memberId);
            $firstName = (string)($me['first_name'] ?? '');
            $lastName  = (string)($me['last_name'] ?? '');
            $myName    = trim($firstName . ' ' . $lastName);
            if ($myName === '') $myName = 'Klubowicz';
            $title   = $subj !== null && $subj !== '' ? $subj : $myName;
            $preview = mb_substr($body, 0, 140);
            PushService::sendToMember($peer, $title, $preview, [
                'type'               => 'message',
                'thread_peer_id'     => (string)$this->memberId,
                'message_id'         => (string)$msgId,
                'sender_first_name'  => $firstName,
                'sender_last_name'   => $lastName,
            ]);
        } catch (\Throwable $e) {
            error_log('messages.send push failed: ' . $e->getMessage());
        }

        $row = $this->fetchMessageRow($msgId);
        $this->json($row !== null ? $this->shapeMessage($row, $peer, false) : [
            'id'         => $msgId,
            'from_me'    => true,
            'peer_id'    => $peer,
            'subject'    => $subj,
            'body'       => $body,
            'created_at' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
            'read_at'    => null,
        ], 201);
    }

    public function poll(): void
    {
        $this->requireMember();
        $since = isset($_GET['since']) ? (int)$_GET['since'] : 0;

        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT cm.*, p_self.last_read_message_id
             FROM chat_messages cm
             JOIN message_thread_participants p_self
               ON p_self.thread_id = cm.thread_id AND p_self.member_id = ?
             WHERE cm.id > ?
               AND cm.club_id = ?
               AND cm.deleted_at IS NULL
             ORDER BY cm.id ASC
             LIMIT 50"
        );
        $stmt->execute([$this->memberId, $since, $this->clubId]);
        $rows = $stmt->fetchAll();

        // Resolve peer per thread (for response shape).
        $threadIds = array_unique(array_map(fn($r) => (int)$r['thread_id'], $rows));
        $peersByThread = $this->peersForDirectThreads($threadIds);

        $data = [];
        foreach ($rows as $r) {
            $tid = (int)$r['thread_id'];
            $peer = $peersByThread[$tid] ?? 0;
            $data[] = $this->shapeMessage($r, $peer, false);
        }

        $threads = $this->countUnreadThreads();

        $this->json([
            'data'                 => $data,
            'unread_threads_count' => $threads,
            'server_now'           => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
        ]);
    }

    public function unreadCount(): void
    {
        $this->requireMember();
        $db = Database::pdo();

        // Sum unread chat_messages across the member's threads (peer-sent + after last_read).
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM chat_messages cm
             JOIN message_thread_participants p ON p.thread_id = cm.thread_id AND p.member_id = ?
             WHERE cm.deleted_at IS NULL
               AND cm.sender_member_id <> ?
               AND cm.club_id = ?
               AND (p.last_read_message_id IS NULL OR cm.id > p.last_read_message_id)"
        );
        $stmt->execute([$this->memberId, $this->memberId, $this->clubId]);
        $unread = (int)$stmt->fetchColumn();

        $this->json(['unread' => $unread, 'unread_threads' => $this->countUnreadThreads()]);
    }

    private function countUnreadThreads(): int
    {
        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(DISTINCT cm.thread_id)
             FROM chat_messages cm
             JOIN message_thread_participants p ON p.thread_id = cm.thread_id AND p.member_id = ?
             WHERE cm.deleted_at IS NULL
               AND cm.sender_member_id <> ?
               AND cm.club_id = ?
               AND (p.last_read_message_id IS NULL OR cm.id > p.last_read_message_id)"
        );
        $stmt->execute([$this->memberId, $this->memberId, $this->clubId]);
        return (int)$stmt->fetchColumn();
    }

    // --- helpers -------------------------------------------------------------

    /**
     * @param array<int> $threadIds
     * @return array<int,int> threadId => peerMemberId
     */
    private function peersForDirectThreads(array $threadIds): array
    {
        if (empty($threadIds)) return [];
        $placeholders = implode(',', array_fill(0, count($threadIds), '?'));
        $params = array_merge($threadIds, [$this->memberId]);
        $stmt = Database::pdo()->prepare(
            "SELECT thread_id, member_id FROM message_thread_participants
             WHERE thread_id IN ($placeholders) AND member_id <> ?"
        );
        $stmt->execute($params);
        $out = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $out[(int)$row['thread_id']] = (int)$row['member_id'];
        }
        return $out;
    }

    /**
     * @param array<int> $ids
     * @return array<int,array<string,mixed>>
     */
    private function fetchMembers(array $ids): array
    {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT id, first_name, last_name, photo_path FROM members WHERE id IN ($placeholders)"
        );
        $stmt->execute($ids);
        $out = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $out[(int)$row['id']] = $row;
        }
        return $out;
    }

    private function fetchMessageRow(int $messageId): ?array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM chat_messages WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$messageId]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    private function shapeMessage(array $r, int $peerId, bool $markedNow): array
    {
        $fromMe = (int)$r['sender_member_id'] === $this->memberId;
        $readAt = null;
        if ($fromMe || $markedNow) {
            $readAt = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z');
        }
        return [
            'id'         => (int)$r['id'],
            'from_me'    => $fromMe,
            'peer_id'    => $peerId,
            'subject'    => null,  // chat_messages has no subject column
            'body'       => $r['body'],
            'created_at' => $r['created_at'],
            'read_at'    => $readAt,
        ];
    }

    /**
     * Verify $peerId is a member of the same club.
     */
    private function ensureSameClub(int $peerId): void
    {
        $peer = (new MemberModel())->findById($peerId);
        if (!$peer || (int)$peer['club_id'] !== $this->clubId) {
            $this->error('Zawodnik nie istnieje w tym klubie.', 404, 'peer_not_found');
        }
    }

    private function photoUrl(?string $path): ?string
    {
        if ($path === null || $path === '') return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return '/' . ltrim($path, '/');
    }
}
