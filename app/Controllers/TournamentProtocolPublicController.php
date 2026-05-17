<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\RateLimiter;
use App\Models\ClubCustomizationModel;
use App\Models\ClubModel;
use App\Models\TournamentProtocolModel;
use PDO;

/**
 * Publiczna strona pobierania PDF protokolu turniejowego — bez logowania.
 *
 * Routes:
 *   GET /protocols/:slug          — landing HTML (branding klubu + podium + link do PDF)
 *   GET /protocols/:slug/download — sam plik PDF (application/pdf)
 *   GET /protocols/:slug/qr       — redirect do generatora QR (qr-server.com)
 *
 * Bezpieczenstwo:
 *   - Tylko gdy tournament_protocols.public_share_enabled = 1
 *   - Slug GLOBALNIE unikalny (cross-tenant) — UNIQUE w bazie
 *   - PDF poza /public/ — pobierany przez readfile() z weryfikacja
 *   - Rate-limit 100/min per IP (anti-scrape)
 *   - Walidacja slug regex ^[a-z0-9-]{1,80}$
 *
 * Wzorzec: LivePublicController (live scoring — PR #181).
 */
class TournamentProtocolPublicController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->view->setLayout('none');
    }

    /**
     * GET /protocols/:slug
     */
    public function landing(string $slug): void
    {
        [$proto, $tournament] = $this->resolve($slug);
        if ($proto === null) { $this->notFound(); return; }

        $clubId = (int)$proto['club_id'];
        $club   = (new ClubModel())->findById($clubId);
        $branding = (new ClubCustomizationModel())->findForClub($clubId)
                    ?? ClubCustomizationModel::defaults();

        // Podium top-3 — z tournament_participants gdzie place IN (1,2,3).
        $podium = $this->loadPodium((int)$proto['tournament_id']);

        // Lekkie metryki.
        $participantCount = (int)Database::pdo()
            ->query("SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = "
                    . (int)$proto['tournament_id'])
            ->fetchColumn();

        $pageUrl     = function_exists('url') ? url('protocols/' . $slug) : '/protocols/' . $slug;
        $downloadUrl = function_exists('url') ? url('protocols/' . $slug . '/download') : '/protocols/' . $slug . '/download';
        $qrUrl       = 'https://api.qrserver.com/v1/create-qr-code/?size=256x256&data='
                       . rawurlencode($pageUrl);

        $this->view->render('public/tournament_protocol', [
            'title'           => 'Protokol turnieju: ' . ($tournament['name'] ?? ''),
            'tournament'      => $tournament,
            'club'            => $club,
            'branding'        => $branding,
            'protocol'        => $proto,
            'podium'          => $podium,
            'participantCount'=> $participantCount,
            'downloadUrl'     => $downloadUrl,
            'pageUrl'         => $pageUrl,
            'qrUrl'           => $qrUrl,
        ]);
    }

    /**
     * GET /protocols/:slug/download — sam plik PDF.
     */
    public function download(string $slug): void
    {
        [$proto] = $this->resolve($slug);
        if ($proto === null) { $this->notFound(); return; }

        $relPath = (string)$proto['pdf_path'];
        // Bezpieczne: NIE ufamy DB-stored sciezce w 100%. Wymuszamy prefix.
        $expectedPrefix = 'storage/tournament_protocols/';
        if (!str_starts_with($relPath, $expectedPrefix)) {
            $this->notFound();
            return;
        }
        $absPath = ROOT_PATH . '/' . $relPath;
        $real    = realpath($absPath);
        $baseReal= realpath(ROOT_PATH . '/storage/tournament_protocols');
        if ($real === false || $baseReal === false || !str_starts_with($real, $baseReal)) {
            $this->notFound();
            return;
        }
        if (!is_file($real)) {
            $this->notFound();
            return;
        }

        // Filename czytelny.
        $filename = 'protokol-' . $slug . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Length: ' . filesize($real));
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Cache-Control: public, max-age=300');
        readfile($real);
        exit;
    }

    /**
     * GET /protocols/:slug/qr
     */
    public function qr(string $slug): void
    {
        [$proto] = $this->resolve($slug);
        if ($proto === null) { $this->notFound(); return; }

        $sizeParam = (int)($_GET['size'] ?? 256);
        $size      = max(64, min(1024, $sizeParam));
        $pageUrl   = function_exists('url') ? url('protocols/' . $slug) : '/protocols/' . $slug;
        $target    = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size
                     . '&data=' . rawurlencode($pageUrl);
        header('Cache-Control: public, max-age=86400');
        header('Location: ' . $target, true, 302);
        exit;
    }

    // ────────────────────────────────────────────────────────────
    // Internals
    // ────────────────────────────────────────────────────────────

    /**
     * Rate-limit + normalize + lookup. Wspoldzielona logika dla wszystkich
     * trzech endpointow. Zwraca [protocol|null, tournament|null].
     *
     * @return array{0:?array,1:?array}
     */
    private function resolve(string $slug): array
    {
        $slug = $this->normalizeSlug($slug);
        if ($slug === null) {
            return [null, null];
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!RateLimiter::check($ip, 'tournament_protocol_view', 100, 1)) {
            http_response_code(429);
            echo '<h1>429 Too Many Requests</h1><p>Sprobuj ponownie za minute.</p>';
            exit;
        }
        RateLimiter::hit($ip, 'tournament_protocol_view', 100, 1);

        $proto = (new TournamentProtocolModel())->findByPublicSlug($slug);
        if ($proto === null) {
            return [null, null];
        }
        $stmt = Database::pdo()->prepare("SELECT * FROM tournaments WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$proto['tournament_id']]);
        $tournament = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tournament) {
            return [null, null];
        }
        return [$proto, $tournament];
    }

    private function loadPodium(int $tournamentId): array
    {
        // Najpierw probujemy po `place` column (jesli istnieje).
        try {
            $stmt = Database::pdo()->prepare(
                "SELECT tp.place, m.first_name, m.last_name
                   FROM tournament_participants tp
                   JOIN members m ON m.id = tp.member_id
                  WHERE tp.tournament_id = ? AND tp.place IN (1,2,3)
               ORDER BY tp.place ASC
                  LIMIT 3"
            );
            $stmt->execute([$tournamentId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) return $rows;
        } catch (\Throwable) {
            // brak kolumny place — pomijamy
        }

        // Fallback: zwyciezca finalu (max rounda) z drabinki.
        try {
            $stmt = Database::pdo()->prepare(
                "SELECT MAX(round) FROM tournament_matches WHERE tournament_id = ?"
            );
            $stmt->execute([$tournamentId]);
            $maxRound = (int)$stmt->fetchColumn();
            if ($maxRound > 0) {
                $stmt2 = Database::pdo()->prepare(
                    "SELECT m.first_name, m.last_name
                       FROM tournament_matches tm
                       JOIN members m ON m.id = tm.winner_id
                      WHERE tm.tournament_id = ? AND tm.round = ?
                      LIMIT 1"
                );
                $stmt2->execute([$tournamentId, $maxRound]);
                $row = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    return [['place' => 1, 'first_name' => $row['first_name'], 'last_name' => $row['last_name']]];
                }
            }
        } catch (\Throwable) {}

        return [];
    }

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
            echo '<h1>404 — Protokol nie istnieje lub link zostal wylaczony</h1>';
        }
        exit;
    }
}
