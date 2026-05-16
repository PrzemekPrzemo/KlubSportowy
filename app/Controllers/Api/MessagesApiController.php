<?php

namespace App\Controllers\Api;

use App\Helpers\PushService;
use App\Models\MemberModel;
use App\Models\MessageModel;

/**
 * Member-to-member real-time chat (polling + FCM push).
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
        $cursor = isset($_GET['cursor']) && (int)$_GET['cursor'] > 0 ? (int)$_GET['cursor'] : null;
        $limit  = max(1, min(50, (int)($_GET['limit'] ?? 20)));
        $result = (new MessageModel())->memberThreads($this->clubId, $this->memberId, $cursor, $limit);
        $this->json($result);
    }

    public function thread(string $peerId): void
    {
        $this->requireMember();
        $peer = (int)$peerId;
        if ($peer <= 0) {
            $this->error('Nieprawidłowy peer_member_id.', 400, 'invalid_peer');
        }
        $this->ensureSameClub($peer);

        $cursor = isset($_GET['cursor']) && (int)$_GET['cursor'] > 0 ? (int)$_GET['cursor'] : null;
        $limit  = max(1, min(100, (int)($_GET['limit'] ?? 50)));

        $model  = new MessageModel();
        $result = $model->memberThread($this->clubId, $this->memberId, $peer, $cursor, $limit);

        // Auto-mark all incoming-unread-from-peer messages in the page as read.
        $candidateIds = [];
        foreach ($result['data'] as $m) {
            if (!$m['from_me'] && $m['read_at'] === null) {
                $candidateIds[] = (int)$m['id'];
            }
        }
        $markedRead = $model->markIdsRead($this->clubId, $this->memberId, $candidateIds);
        // Reflect in the returned payload so client doesnt see them as unread.
        if (!empty($markedRead)) {
            $now = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z');
            $set = array_flip($markedRead);
            foreach ($result['data'] as &$m) {
                if (isset($set[(int)$m['id']])) $m['read_at'] = $now;
            }
            unset($m);
        }

        $this->json([
            'data'        => $result['data'],
            'next_cursor' => $result['next_cursor'],
            'marked_read' => $markedRead,
        ]);
    }

    public function markThreadRead(string $peerId): void
    {
        $this->requireMember();
        $peer = (int)$peerId;
        if ($peer <= 0) {
            $this->error('Nieprawidłowy peer_member_id.', 400, 'invalid_peer');
        }
        $n = (new MessageModel())->markThreadRead($this->clubId, $this->memberId, $peer);
        $this->json(['marked' => $n]);
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
        if ($subj !== null && mb_strlen($subj) > 200) {
            $subj = mb_substr($subj, 0, 200);
        }

        $peerMember = $this->ensureSameClub($peer);

        $model = new MessageModel();
        $id    = $model->createDirect($this->clubId, $this->memberId, $peer, $body, $subj);

        // FCM + inbox row for peer.
        try {
            $me     = (new MemberModel())->findById($this->memberId);
            $myName = trim(($me['first_name'] ?? '') . ' ' . ($me['last_name'] ?? ''));
            if ($myName === '') $myName = 'Klubowicz';
            $title   = $subj !== null && $subj !== '' ? $subj : $myName;
            $preview = mb_substr($body, 0, 140);
            PushService::sendToMember($peer, $title, $preview, [
                'type'           => 'message',
                'thread_peer_id' => (string)$this->memberId,
                'message_id'     => (string)$id,
            ]);
        } catch (\Throwable $e) {
            error_log('messages.send push failed: ' . $e->getMessage());
        }

        $msg = $model->findShaped($this->clubId, $this->memberId, $peer, $id);
        $this->json($msg ?? [
            'id' => $id, 'from_me' => true, 'peer_id' => $peer,
            'subject' => $subj, 'body' => $body,
            'created_at' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
            'read_at' => null,
        ], 201);
    }

    public function poll(): void
    {
        $this->requireMember();
        $since = isset($_GET['since']) ? (int)$_GET['since'] : 0;
        $model = new MessageModel();
        $rows  = $model->pollSince($this->clubId, $this->memberId, $since > 0 ? $since : null, 50);
        $stats = $model->unreadStats($this->clubId, $this->memberId);
        $this->json([
            'data'                 => $rows,
            'unread_threads_count' => $stats['unread_threads'],
            'server_now'           => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
        ]);
    }

    public function unreadCount(): void
    {
        $this->requireMember();
        $stats = (new MessageModel())->unreadStats($this->clubId, $this->memberId);
        $this->json(['unread' => $stats['unread'], 'unread_threads' => $stats['unread_threads']]);
    }

    /**
     * Verify $peerId is a member of the same club. Returns the row or 404s.
     */
    private function ensureSameClub(int $peerId): array
    {
        $peer = (new MemberModel())->findById($peerId);
        if (!$peer || (int)$peer['club_id'] !== $this->clubId) {
            $this->error('Zawodnik nie istnieje w tym klubie.', 404, 'peer_not_found');
        }
        return $peer;
    }
}
