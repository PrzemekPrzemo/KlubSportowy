<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\MemberAuth;
use App\Helpers\PushService;
use App\Helpers\RateLimiter;
use App\Helpers\Session;
use App\Models\ChatMessageModel;
use App\Models\MemberModel;
use App\Models\MessageThreadModel;

/**
 * Real-time komunikator klubowy w Portalu Zawodnika.
 *
 * Endpointy:
 *   GET  /portal/messenger                  — lista watkow + UI splitview
 *   GET  /portal/messenger/:id              — wybrany watek (server-render initial)
 *   POST /portal/messenger/send             — wyslij wiadomosc (CSRF, AJAX)
 *   POST /portal/messenger/new-direct       — znajdz/utworz watek 1-1
 *   POST /portal/messenger/:id/mark-read    — ustaw last_read_message_id
 *   GET  /portal/messenger/:id/stream       — SSE (text/event-stream, 30s)
 *   GET  /portal/messenger/:id/poll?since=  — long-poll fallback (JSON)
 *
 * Multi-tenant: kazda operacja sprawdza ze member nalezy do watku
 * (MessageThreadModel::isParticipant) i ze watek nalezy do MemberAuth::clubId().
 *
 * SSE caveat: wymaga konfiguracji serwera (proxy_buffering off w nginx,
 * AcceptPathInfo + brak gzip dla SSE w Apache). Naglowek X-Accel-Buffering:no
 * jest emitowany aby wymusic to po stronie nginx jezeli mozliwe.
 */
class PortalMessengerController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        MemberAuth::requireLogin();
    }

    /**
     * GET /portal/messenger — lista watkow + (opcjonalnie) aktywny watek.
     */
    public function index(): void
    {
        $this->renderMessenger(null);
    }

    /**
     * GET /portal/messenger/:id — pokaz wybrany watek.
     */
    public function thread(string $id): void
    {
        $this->renderMessenger((int)$id);
    }

    private function renderMessenger(?int $activeThreadId): void
    {
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();
        if ($clubId === 0) {
            Session::flash('error', 'Wybierz klub aby uzyc komunikatora.');
            $this->redirect('portal/dashboard');
        }

        $threadModel  = new MessageThreadModel();
        $threads      = $threadModel->forMember($memberId, $clubId);

        $activeThread = null;
        $activeParticipants = [];
        $activeMessages = [];
        $activeOther = null;
        if ($activeThreadId !== null) {
            $bundle = $threadModel->getThreadForMember($activeThreadId, $memberId, $clubId);
            if ($bundle === null) {
                Session::flash('error', 'Brak dostepu do watku.');
                $this->redirect('portal/messenger');
            }
            $activeThread       = $bundle['thread'];
            $activeParticipants = $bundle['participants'];
            $activeMessages     = (new ChatMessageModel())->latestForThread($activeThreadId, 80);

            // Direct thread: znajdz "drugiego" do wyswietlenia w headerze.
            if (($activeThread['thread_type'] ?? '') === 'direct') {
                foreach ($activeParticipants as $p) {
                    if ((int)$p['member_id'] !== $memberId) {
                        $activeOther = $p;
                        break;
                    }
                }
            }
        }

        // Lista czlonkow klubu do modal "nowa rozmowa" (bez siebie).
        $candidates = (new MemberModel())->withoutScope()->listForClubExcept($clubId, $memberId);

        $this->view->setLayout('portal');
        $this->view->render('portal/messenger/index', [
            'title'              => 'Wiadomosci',
            'threads'            => $threads,
            'activeThreadId'     => $activeThreadId,
            'activeThread'       => $activeThread,
            'activeParticipants' => $activeParticipants,
            'activeMessages'     => $activeMessages,
            'activeOther'        => $activeOther,
            'candidates'         => $candidates,
            'currentMemberId'    => $memberId,
            'appName'            => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    /**
     * POST /portal/messenger/send — wyslij wiadomosc (AJAX). Zwraca JSON.
     */
    public function send(): void
    {
        Csrf::verify();
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();
        if ($clubId === 0) {
            $this->json(['ok' => false, 'error' => 'no_club'], 400);
        }

        // Rate limit: 60 req/min per member dla send.
        $ip  = ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . ':m' . $memberId;
        if (!RateLimiter::check($ip, 'messenger_send', 60, 1)) {
            $this->json(['ok' => false, 'error' => 'rate_limit'], 429);
        }
        RateLimiter::hit($ip, 'messenger_send', 60, 1);

        $threadId = (int)($_POST['thread_id'] ?? 0);
        $body     = trim((string)($_POST['body'] ?? ''));
        if ($threadId === 0 || $body === '') {
            $this->json(['ok' => false, 'error' => 'invalid_input'], 400);
        }
        if (mb_strlen($body) > 4000) {
            $this->json(['ok' => false, 'error' => 'body_too_long'], 400);
        }

        $threadModel = new MessageThreadModel();
        if (!$threadModel->isParticipant($threadId, $memberId, $clubId)) {
            $this->json(['ok' => false, 'error' => 'forbidden'], 403);
        }

        $chat = new ChatMessageModel();
        $messageId = $chat->send($threadId, $memberId, $clubId, $body);
        $threadModel->touchLastMessage($threadId);
        // Sender zawsze "przeczytal" wlasna wiadomosc.
        $threadModel->markRead($threadId, $memberId, $messageId);

        // FCM push best-effort do pozostalych participantow (poza muted).
        $sender = (new MemberModel())->withoutScope()->findById($memberId);
        $senderName = trim((string)($sender['first_name'] ?? '') . ' ' . (string)($sender['last_name'] ?? ''));
        if ($senderName === '') {
            $senderName = 'Czlonek klubu';
        }
        $bodyPreview = mb_substr($body, 0, 140);
        $otherIds = $threadModel->otherParticipantIds($threadId, $memberId);
        foreach ($otherIds as $rid) {
            try {
                PushService::sendToMember($rid, $senderName, $bodyPreview, [
                    'type'      => 'chat_message',
                    'thread_id' => (string)$threadId,
                    'message_id'=> (string)$messageId,
                ]);
            } catch (\Throwable $e) {
                error_log('Messenger push failed: ' . $e->getMessage());
            }
        }

        $this->json([
            'ok' => true,
            'message' => [
                'id'               => $messageId,
                'thread_id'        => $threadId,
                'sender_member_id' => $memberId,
                'sender_name'      => $senderName,
                'body'             => $body,
                'created_at'       => date('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * POST /portal/messenger/new-direct — znajdz lub utworz watek 1-1.
     */
    public function newDirect(): void
    {
        Csrf::verify();
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();
        $targetId = (int)($_POST['target_member_id'] ?? 0);

        if ($targetId === 0 || $targetId === $memberId) {
            Session::flash('error', 'Wybierz innego czlonka.');
            $this->redirect('portal/messenger');
        }

        // Sprawdz ze target nalezy do tego samego klubu.
        $target = (new MemberModel())->withoutScope()->findById($targetId);
        if (!$target || (int)$target['club_id'] !== $clubId) {
            Session::flash('error', 'Brak dostepu do tego czlonka.');
            $this->redirect('portal/messenger');
        }

        $threadModel = new MessageThreadModel();
        $thread = $threadModel->findDirectBetween($memberId, $targetId, $clubId);
        if ($thread) {
            $this->redirect('portal/messenger/' . (int)$thread['id']);
        }

        $threadId = $threadModel->createDirect($memberId, $targetId, $clubId);
        $this->redirect('portal/messenger/' . $threadId);
    }

    /**
     * POST /portal/messenger/:id/mark-read.
     */
    public function markRead(string $id): void
    {
        // Tolerujemy zarowno klasyczne POST z formularza jak i AJAX (X-Requested-With).
        Csrf::verify();
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();
        $threadId = (int)$id;

        $threadModel = new MessageThreadModel();
        if (!$threadModel->isParticipant($threadId, $memberId, $clubId)) {
            $this->json(['ok' => false, 'error' => 'forbidden'], 403);
        }
        $maxId = (new ChatMessageModel())->maxIdInThread($threadId);
        $threadModel->markRead($threadId, $memberId, $maxId);
        $this->json(['ok' => true, 'last_read_message_id' => $maxId]);
    }

    /**
     * GET /portal/messenger/:id/stream — Server-Sent Events.
     * 30s session: polling DB co 1s, emitter heartbeat co 10s.
     * Klient powinien reconnect po EOF.
     */
    public function stream(string $id): void
    {
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();
        $threadId = (int)$id;

        $threadModel = new MessageThreadModel();
        if (!$threadModel->isParticipant($threadId, $memberId, $clubId)) {
            http_response_code(403);
            exit;
        }

        $sinceParam = isset($_GET['since']) ? (int)$_GET['since'] : 0;

        // Naglowki SSE.
        @ini_set('zlib.output_compression', '0');
        @ini_set('output_buffering', '0');
        @ini_set('implicit_flush', '1');
        while (ob_get_level() > 0) { @ob_end_flush(); }
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('X-Accel-Buffering: no'); // nginx
        header('Connection: keep-alive');

        @set_time_limit(35);
        ignore_user_abort(false);
        @ob_implicit_flush(true);

        // Wyslij retry hint (jak szybko reconnect po EOF).
        echo "retry: 1500\n\n";
        @ob_flush(); @flush();

        $chat        = new ChatMessageModel();
        $lastId      = $sinceParam;
        $start       = time();
        $lastHeartbeat = $start;

        // Rezygnujemy z trzymania pojedynczej PDO na cale 30s, polling lekki.
        while (!connection_aborted() && (time() - $start) < 30) {
            $rows = $chat->forThread($threadId, $lastId, 50);
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $payload = [
                        'id'               => (int)$row['id'],
                        'thread_id'        => (int)$row['thread_id'],
                        'sender_member_id' => (int)$row['sender_member_id'],
                        'sender_name'      => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                        'body'             => (string)$row['body'],
                        'created_at'       => (string)$row['created_at'],
                    ];
                    echo "id: " . (int)$row['id'] . "\n";
                    echo "event: message\n";
                    echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n";
                    $lastId = max($lastId, (int)$row['id']);
                }
                @ob_flush(); @flush();
            }
            if ((time() - $lastHeartbeat) >= 10) {
                echo ": hb " . time() . "\n\n";
                @ob_flush(); @flush();
                $lastHeartbeat = time();
            }
            usleep(1000000); // 1s
        }
        exit;
    }

    /**
     * GET /portal/messenger/:id/poll?since=ID — fallback dla browsers bez SSE.
     * Zwraca natychmiast JSON nowych wiadomosci > since (brak czekania).
     */
    public function poll(string $id): void
    {
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();
        $threadId = (int)$id;
        $since    = isset($_GET['since']) ? (int)$_GET['since'] : 0;

        $threadModel = new MessageThreadModel();
        if (!$threadModel->isParticipant($threadId, $memberId, $clubId)) {
            $this->json(['ok' => false, 'error' => 'forbidden'], 403);
        }

        // Rate limit dla pollu: 60/min per user (zakladajac client polluje co 3-5s).
        $ip = ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . ':p' . $memberId;
        if (!RateLimiter::check($ip, 'messenger_poll', 60, 1)) {
            $this->json(['ok' => false, 'error' => 'rate_limit'], 429);
        }
        RateLimiter::hit($ip, 'messenger_poll', 60, 1);

        $rows = (new ChatMessageModel())->forThread($threadId, $since, 50);
        $out  = [];
        foreach ($rows as $row) {
            $out[] = [
                'id'               => (int)$row['id'],
                'thread_id'        => (int)$row['thread_id'],
                'sender_member_id' => (int)$row['sender_member_id'],
                'sender_name'      => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                'body'             => (string)$row['body'],
                'created_at'       => (string)$row['created_at'],
            ];
        }
        $this->json(['ok' => true, 'messages' => $out]);
    }
}
