<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\LiveStream;
use App\Helpers\Session;
use App\Models\LiveChannelModel;
use App\Models\LiveEventUpdateModel;

/**
 * Live updates (Server-Sent Events) — real-time wyniki meczu/turnieju.
 *
 * Endpointy:
 *  GET  /live/stream/:channel       SSE stream (publiczny dla is_public=1)
 *  POST /live/publish/:channel      admin/trener publikuje update (CSRF + role)
 *  GET  /live/channels              JSON: aktywne kanaly (zalogowani)
 *  GET  /live/channels-public       JSON: publiczne kanaly biezacego klubu
 *  GET  /live                       widok admin: lista live channels
 *  GET  /live/admin/start/:id       (POST) start kanalu -> status=live
 *  GET  /live/admin/end/:id         (POST) end kanalu -> status=finished
 *  POST /live/admin/create          tworzenie kanalu
 *  GET  /club/:slug/live            publiczna strona: live channels klubu
 */
class LiveStreamController extends BaseController
{
    /** Role uprawnione do publikowania update'ow */
    private const PUBLISHER_ROLES = ['zarzad', 'trener', 'instruktor'];

    /**
     * SSE stream — endpoint dlugotrwaly.
     *
     * Auth model: kanal jest publiczny gdy live_channels.is_public=1 — wtedy
     * dostepny bez logowania. W przeciwnym razie wymaga zalogowania + dopasowania
     * club_id (ClubContext).
     */
    public function stream(string $channel): void
    {
        $channelModel = new LiveChannelModel();
        $ch = $channelModel->findByChannelPublic($channel);

        if ($ch === null) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Channel not found\n";
            return;
        }

        $isPublic = (int)$ch['is_public'] === 1;

        if (!$isPublic) {
            // Wymaga zalogowanego usera lub aktywnego ClubContext zgodnego z club_id
            $currentClub = ClubContext::current();
            $isMember    = Auth::id() !== null && $currentClub !== null
                && (int)$currentClub === (int)$ch['club_id'];
            if (!Auth::isSuperAdmin() && !$isMember) {
                http_response_code(403);
                header('Content-Type: text/plain; charset=utf-8');
                echo "Forbidden\n";
                return;
            }
        }

        $sinceId = LiveStream::lastEventIdFromRequest();
        // Timeout: pozwalamy nadpisac przez query (max 120s zeby nie blokowac workera)
        $requested = isset($_GET['timeout']) ? (int)$_GET['timeout'] : LiveStream::DEFAULT_TIMEOUT_SEC;
        $timeout   = max(5, min(120, $requested));

        // Zamknij sesje przed pętlą — inaczej blokuje inne requesty usera
        @session_write_close();

        LiveStream::stream($channel, $sinceId, $timeout);
        exit;
    }

    /**
     * POST /live/publish/:channel — publish update.
     * Body: event_type=goal&payload={"team":"A","score":1}  (payload jako JSON string lub form fields)
     */
    public function publish(string $channel): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(self::PUBLISHER_ROLES);
        Csrf::verify();

        $eventType = trim((string)($_POST['event_type'] ?? 'message'));
        if ($eventType === '') {
            $this->json(['ok' => false, 'error' => 'event_type required'], 400);
        }

        // Payload: preferuj JSON pole, fallback do calego $_POST minus event_type i CSRF
        $payload = [];
        if (!empty($_POST['payload_json'])) {
            $decoded = json_decode((string)$_POST['payload_json'], true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }
        if (empty($payload)) {
            $payload = $_POST;
            unset($payload['_csrf'], $payload['event_type'], $payload['payload_json']);
        }

        try {
            $id = LiveStream::publish($channel, $eventType, $payload);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 400);
        }

        $this->json(['ok' => true, 'id' => $id, 'channel' => $channel, 'event_type' => $eventType]);
    }

    /**
     * GET /live/channels — JSON lista aktywnych kanalow dla biezacego klubu.
     */
    public function channels(): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        $rows = (new LiveChannelModel())->findActiveForClub();
        $this->json(['ok' => true, 'channels' => $rows]);
    }

    /**
     * GET /live — widok zarzadzania kanalami live (admin/trener).
     */
    public function index(): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        $model = new LiveChannelModel();
        $active = $model->findActiveForClub();
        // wszystkie kanaly klubu
        $clubId = (int)ClubContext::current();
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM live_channels WHERE club_id = ? ORDER BY id DESC LIMIT 100"
        );
        $stmt->execute([$clubId]);
        $all = $stmt->fetchAll();

        $this->render('live/channels', [
            'title'   => 'Live updates',
            'active'  => $active,
            'all'     => $all,
        ]);
    }

    /**
     * POST /live/admin/create — utworz kanal.
     */
    public function adminCreate(): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(self::PUBLISHER_ROLES);
        Csrf::verify();

        $channel = trim((string)($_POST['channel'] ?? ''));
        $title   = trim((string)($_POST['title']   ?? ''));
        $sport   = trim((string)($_POST['sport_key'] ?? '')) ?: null;
        $isPub   = isset($_POST['is_public']) ? 1 : 0;

        // Walidacja channel — alphanum + : _ -
        if (!preg_match('/^[a-zA-Z0-9:_\-]{3,60}$/', $channel) || $title === '') {
            Session::flash('error', 'Channel (3-60 znakow alfanumerycznych/: _ -) i tytul wymagane.');
            $this->redirect('live');
        }

        $model = new LiveChannelModel();
        if ($model->findByChannel($channel) !== null) {
            Session::flash('error', 'Kanal o takiej nazwie juz istnieje.');
            $this->redirect('live');
        }

        $model->insert([
            'channel'   => $channel,
            'title'     => $title,
            'sport_key' => $sport,
            'is_public' => $isPub,
            'status'    => 'scheduled',
        ]);

        Session::flash('success', 'Kanal utworzony.');
        $this->redirect('live');
    }

    public function adminStart(string $id): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(self::PUBLISHER_ROLES);
        Csrf::verify();

        (new LiveChannelModel())->startChannel((int)$id);
        Session::flash('success', 'Kanal wystartowany.');
        $this->redirect('live');
    }

    public function adminEnd(string $id): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(self::PUBLISHER_ROLES);
        Csrf::verify();

        $model = new LiveChannelModel();
        $ch = $model->findById((int)$id);
        if ($ch) {
            $model->endChannel((int)$id);
            // Wstaw zamykajacy event 'end' zeby klient zamknal EventSource
            try {
                (new LiveEventUpdateModel())->append(
                    (int)$ch['club_id'], (string)$ch['channel'], 'end',
                    ['ended_at' => date('c')]
                );
            } catch (\Throwable) {}
        }
        Session::flash('success', 'Kanal zakonczony.');
        $this->redirect('live');
    }

    public function adminDelete(string $id): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(self::PUBLISHER_ROLES);
        Csrf::verify();

        (new LiveChannelModel())->delete((int)$id);
        Session::flash('success', 'Kanal usuniety.');
        $this->redirect('live');
    }

    /**
     * GET /club/:slug/live — publiczna strona z live channels danego klubu.
     */
    public function publicClubLive(string $slug): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT c.* FROM clubs c
             LEFT JOIN club_customization cc ON cc.club_id = c.id
             WHERE c.is_active = 1 AND (c.slug = ? OR cc.subdomain = ?)
             LIMIT 1"
        );
        try {
            $stmt->execute([$slug, $slug]);
            $club = $stmt->fetch();
        } catch (\Throwable) {
            // jesli kolumna 'slug' nie istnieje w clubs, fallback po subdomenie
            $stmt2 = $db->prepare(
                "SELECT c.* FROM clubs c
                 JOIN club_customization cc ON cc.club_id = c.id
                 WHERE c.is_active = 1 AND cc.subdomain = ? LIMIT 1"
            );
            $stmt2->execute([$slug]);
            $club = $stmt2->fetch();
        }

        if (!$club) {
            http_response_code(404);
            echo '<h1>404 - Klub nie znaleziony</h1>';
            return;
        }

        $channels = (new LiveChannelModel())->publicLiveForClub((int)$club['id']);

        $this->view->setLayout('public');
        $this->render('live/public', [
            'title'    => 'Live: ' . ($club['name'] ?? ''),
            'club'     => $club,
            'channels' => $channels,
        ]);
    }
}
