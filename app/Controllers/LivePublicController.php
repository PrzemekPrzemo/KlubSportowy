<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\RateLimiter;
use App\Helpers\View;
use App\Models\ClubCustomizationModel;
use App\Models\ClubModel;
use App\Models\TournamentModel;

/**
 * Publiczna strona LIVE wynikow turnieju — bez logowania.
 *
 * Routes:
 *   GET /live/:slug         — pelna strona (HTML)
 *   GET /live/:slug/stream  — SSE endpoint (30s session, polling 3s)
 *   GET /live/:slug/qr      — przekierowanie do generatora QR (qr-server.com)
 *
 * Bezpieczenstwo:
 *   - Tylko gdy tournaments.public_live_enabled = 1
 *   - Slug jest GLOBALNIE unikalny (cross-tenant) — UNIQUE w bazie
 *   - Rate-limit 100/min per IP (anti-scrape)
 *   - Zero PII zawodnikow: imie + inicjal (default) lub pelne nazwisko (opt-in admin)
 *   - Log wyswietlen anonimizowany — SHA-256 IP + UA
 *
 * Wzorzec: PublicProfileController (publiczne profile zawodnikow, PR #157).
 */
class LivePublicController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // BEZ requireLogin / requireClubContext — to jest publiczna strona.
        $this->view->setLayout('none');
    }

    /**
     * GET /live/:slug — wyswietl strone live turnieju.
     */
    public function tournament(string $slug): void
    {
        $slug = $this->normalizeSlug($slug);
        if ($slug === null) {
            $this->notFound();
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!RateLimiter::check($ip, 'live_public_view', 100, 1)) {
            http_response_code(429);
            echo '<h1>429 Too Many Requests</h1><p>Sprobuj ponownie za minute.</p>';
            return;
        }
        RateLimiter::hit($ip, 'live_public_view', 100, 1);

        $tournamentModel = new TournamentModel();
        $tournament      = $tournamentModel->findByPublicSlug($slug);
        if ($tournament === null) {
            $this->notFound();
            return;
        }

        // Audit view (anonimizowany — hash IP).
        $this->logView((int)$tournament['id'], $ip);

        $matches  = $tournamentModel->publicMatches((int)$tournament['id']);
        $byRound  = [];
        foreach ($matches as $m) {
            $byRound[(int)$m['round']][] = $m;
        }
        $recent    = $tournamentModel->recentResultsForLive((int)$tournament['id'], 10);
        $upcoming  = $tournamentModel->upcomingMatchesForLive((int)$tournament['id'], 10);
        $standings = $tournamentModel->standingsForLive((int)$tournament['id']);

        // Branding klubu (logo, kolory, motto) — pokazujemy nazwe klubu i miasto,
        // nie wyciagamy zadnych personalnych danych czlonkow ani PESEL.
        $club     = (new ClubModel())->findById((int)$tournament['club_id']);
        $branding = (new ClubCustomizationModel())->findForClub((int)$tournament['club_id'])
                    ?? ClubCustomizationModel::defaults();

        $maxStats = $tournamentModel->maxMatchIdAndUpdated((int)$tournament['id']);

        $this->view->render('live/tournament', [
            'title'       => 'Live: ' . ($tournament['name'] ?? ''),
            'tournament'  => $tournament,
            'club'        => $club,
            'branding'    => $branding,
            'byRound'     => $byRound,
            'recent'      => $recent,
            'upcoming'    => $upcoming,
            'standings'   => $standings,
            'showFullNames' => (int)($tournament['public_live_full_names'] ?? 0) === 1,
            'streamUrl'   => url('live/' . $slug . '/stream'),
            'pageUrl'     => url('live/' . $slug),
            'qrUrl'       => 'https://api.qrserver.com/v1/create-qr-code/?size=256x256&data='
                              . rawurlencode(url('live/' . $slug)),
            'sinceId'     => $maxStats['max_id'],
        ]);
    }

    /**
     * GET /live/:slug/stream — SSE endpoint.
     *
     * Sesja 30s, polling co 3s. Emituje event "match-update" gdy sa nowe mecze.
     * Klient powinien reconnect po EOF (przegladarki SSE robia to natywnie z retry).
     */
    public function stream(string $slug): void
    {
        $slug = $this->normalizeSlug($slug);
        if ($slug === null) {
            http_response_code(404);
            exit;
        }

        $tournamentModel = new TournamentModel();
        $tournament      = $tournamentModel->findByPublicSlug($slug);
        if ($tournament === null) {
            http_response_code(404);
            exit;
        }
        $tournamentId   = (int)$tournament['id'];
        $showFullNames  = (int)($tournament['public_live_full_names'] ?? 0) === 1;

        $sinceParam = isset($_GET['since']) ? (int)$_GET['since'] : 0;

        @ini_set('zlib.output_compression', '0');
        @ini_set('output_buffering', '0');
        @ini_set('implicit_flush', '1');
        while (ob_get_level() > 0) { @ob_end_flush(); }
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');

        @set_time_limit(35);
        ignore_user_abort(false);
        @ob_implicit_flush(true);

        echo "retry: 2000\n\n";
        @ob_flush(); @flush();

        $lastId        = $sinceParam;
        $start         = time();
        $lastHeartbeat = $start;

        while (!connection_aborted() && (time() - $start) < 30) {
            $rows = $tournamentModel->matchesSinceId($tournamentId, $lastId, 50);
            if (!empty($rows)) {
                $payload = [];
                foreach ($rows as $row) {
                    $payload[] = [
                        'id'           => (int)$row['id'],
                        'round'        => (int)$row['round'],
                        'match_number' => (int)$row['match_number'],
                        'player1'      => $this->formatPlayerName(
                            $row['p1_first'] ?? null,
                            $row['p1_last'] ?? null,
                            $showFullNames
                        ),
                        'player2'      => $this->formatPlayerName(
                            $row['p2_first'] ?? null,
                            $row['p2_last'] ?? null,
                            $showFullNames
                        ),
                        'score1'       => $row['score1'],
                        'score2'       => $row['score2'],
                        'winner_id'    => $row['winner_id'] !== null ? (int)$row['winner_id'] : null,
                        'scheduled_at' => $row['scheduled_at'] ?? null,
                    ];
                    $lastId = max($lastId, (int)$row['id']);
                }
                echo "id: " . $lastId . "\n";
                echo "event: match-update\n";
                echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush(); @flush();
            }
            if ((time() - $lastHeartbeat) >= 10) {
                echo ": hb " . time() . "\n\n";
                @ob_flush(); @flush();
                $lastHeartbeat = time();
            }
            usleep(3 * 1000000); // 3s
        }
        exit;
    }

    /**
     * GET /live/:slug/qr — redirect do qr-server.com (lekkie, bez zewn. paczek).
     *
     * Alternative: serwerowo wygenerowac SVG. Tu zostawiamy redirect (cache TTL
     * przegladarka egzekwuje). Brak PII w URL.
     */
    public function qr(string $slug): void
    {
        $slug = $this->normalizeSlug($slug);
        if ($slug === null) {
            http_response_code(404);
            exit;
        }
        // Defense in depth: tylko gdy live wlaczone.
        $tournamentModel = new TournamentModel();
        $tournament      = $tournamentModel->findByPublicSlug($slug);
        if ($tournament === null) {
            http_response_code(404);
            exit;
        }

        $sizeParam = (int)($_GET['size'] ?? 256);
        $size      = max(64, min(1024, $sizeParam));
        $target    = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size
                     . '&data=' . rawurlencode(url('live/' . $slug));
        header('Cache-Control: public, max-age=86400'); // 1 dzien
        header('Location: ' . $target, true, 302);
        exit;
    }

    // ────────────────────────────────────────────────────────────
    // Internals
    // ────────────────────────────────────────────────────────────

    private function normalizeSlug(string $slug): ?string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || strlen($slug) > 80) return null;
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) return null;
        return $slug;
    }

    private function notFound(): void
    {
        http_response_code(404);
        $view = ROOT_PATH . '/app/Views/errors/404.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<h1>404 — Strona nie istnieje</h1>';
        }
    }

    /**
     * Format zawodnika dla widoku publicznego.
     *
     * Default: "Jan K." (imie + inicjal). Opt-in admina: "Jan Kowalski".
     * Brak imienia/nazwiska → "—".
     */
    private function formatPlayerName(?string $first, ?string $last, bool $full): string
    {
        if (!$first && !$last) return '—';
        $first = trim((string)$first);
        $last  = trim((string)$last);
        if ($full) {
            return trim($first . ' ' . $last);
        }
        $initial = $last !== '' ? mb_substr($last, 0, 1, 'UTF-8') . '.' : '';
        return trim($first . ' ' . $initial);
    }

    /**
     * Loguje wyswietlenie strony — IP zhashowany SHA-256 dla privacy.
     * Best-effort: nigdy nie crashuje requestu.
     */
    private function logView(int $tournamentId, string $ip): void
    {
        try {
            $ipHash = hash('sha256', $ip);
            $ua     = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
            $ref    = substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 255);
            $stmt = Database::pdo()->prepare(
                "INSERT INTO tournament_live_views (tournament_id, ip_hash, user_agent, referer)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$tournamentId, $ipHash, $ua, $ref]);
        } catch (\Throwable) {
            // Brak audit = brak crash.
        }
    }
}
