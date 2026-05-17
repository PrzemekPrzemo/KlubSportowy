<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\RateLimiter;
use App\Models\ClubModel;
use App\Models\SportModel;

/**
 * Publiczny katalog klubow sportowych (Club Discovery).
 *
 * Lead-gen dla nowych klientow: rodzic szuka klubu dla dziecka -> trafia tu
 * przez Google -> kontakt z klubem.
 *
 * Routes (BEZ auth):
 *   GET /discover                  — katalog wszystkich klubow
 *   GET /discover/{sport}          — kluby z danym sportem (np. /discover/judo)
 *   GET /discover/club/{slug}      — landing page pojedynczego klubu
 *   GET /api/discover/clubs.json   — JSON dla mapy Leaflet
 *
 * Bezpieczenstwo:
 *   - Tylko clubs z public_discovery_enabled = 1 (opt-in admina)
 *   - Slug klubu globalnie unikalny (cross-tenant)
 *   - Rate-limit 100/min/IP (anti-scrape)
 *   - Privacy: NIE pokazuje czlonkow imiennie, NIE pokazuje danych finansowych
 *   - Audit log views (club_discovery_views) — hash IP, no PII
 *
 * Wzorzec: LivePublicController (PR live scoring) + PublicProfileController.
 */
class DiscoveryController extends BaseController
{
    private const RATE_LIMIT_ACTION = 'discovery_public_view';
    private const RATE_LIMIT_MAX    = 100; // requests
    private const RATE_LIMIT_WINDOW = 1;   // minutes

    public function __construct()
    {
        parent::__construct();
        // BEZ requireLogin / requireClubContext — to jest publiczna strona.
        $this->view->setLayout('public');
    }

    /**
     * GET /discover — lista wszystkich klubow z opt-in (filtrowanie: city).
     */
    public function index(): void
    {
        if (!$this->checkRateLimit()) return;

        $city = trim((string)($_GET['city'] ?? '')) ?: null;

        $clubModel = new ClubModel();
        $clubs     = $clubModel->listForDiscovery(null, $city, 500);
        $sportsAll = $clubModel->sportsDistribution();

        $this->view->render('discover/index', [
            'title'           => 'Znajdz klub sportowy w Polsce',
            'metaDescription' => 'Katalog klubow sportowych w Polsce — judo, pilka nozna, koszykowka, strzelectwo i wiele innych. Znajdz klub dla dziecka w swojej okolicy.',
            'clubs'           => $clubs,
            'sportsAll'       => $sportsAll,
            'filterCity'      => $city,
            'filterSport'     => null,
            'jsonUrl'         => url('api/discover/clubs.json' . ($city ? '?city=' . rawurlencode($city) : '')),
        ]);
    }

    /**
     * GET /discover/{sport} — per-sport landing (np. /discover/judo).
     */
    public function bySport(string $sportKey): void
    {
        if (!$this->checkRateLimit()) return;

        $sportKey = strtolower(trim($sportKey));
        if (!preg_match('/^[a-z0-9_-]+$/', $sportKey)) {
            $this->notFound();
            return;
        }
        $sport = (new SportModel())->findByKey($sportKey);
        if ($sport === null) {
            $this->notFound();
            return;
        }

        $city = trim((string)($_GET['city'] ?? '')) ?: null;

        $clubModel = new ClubModel();
        $clubs     = $clubModel->listForDiscovery($sportKey, $city, 500);
        $sportsAll = $clubModel->sportsDistribution();

        $this->view->render('discover/sport', [
            'title'           => 'Kluby ' . ($sport['name'] ?? $sportKey) . ' w Polsce',
            'metaDescription' => 'Kluby sportowe ' . ($sport['name'] ?? $sportKey)
                                 . ' w Polsce — zapisz dziecko, znajdz trenera, zobacz mape.',
            'sport'           => $sport,
            'clubs'           => $clubs,
            'sportsAll'       => $sportsAll,
            'filterCity'      => $city,
            'filterSport'     => $sportKey,
            'jsonUrl'         => url('api/discover/clubs.json?sport=' . rawurlencode($sportKey)
                                     . ($city ? '&city=' . rawurlencode($city) : '')),
        ]);
    }

    /**
     * GET /discover/club/{slug} — landing page pojedynczego klubu.
     */
    public function clubProfile(string $slug): void
    {
        if (!$this->checkRateLimit()) return;

        $slug = $this->normalizeSlug($slug);
        if ($slug === null) {
            $this->notFound();
            return;
        }

        $clubModel = new ClubModel();
        $club      = $clubModel->findByPublicSlug($slug);
        if ($club === null) {
            $this->notFound();
            return;
        }

        // Audit view (hash IP).
        $this->logView((int)$club['id'], $this->determineSource());

        // Sporty oferowane: priorytetowo z club_sports (zawsze swieze), fallback JSON cache.
        $sports = $clubModel->sportsForClub((int)$club['id']);
        if (empty($sports) && !empty($club['sports_offered_json'])) {
            $decoded = json_decode((string)$club['sports_offered_json'], true);
            if (is_array($decoded)) {
                $sports = $decoded;
            }
        }

        // Statystyki — TYLKO przybliz, nigdy realnych imion czlonkow.
        $memberCount = $this->safeMemberCount((int)$club['id']);
        $yearsActive = !empty($club['founded_year']) && (int)$club['founded_year'] > 1800
            ? max(0, (int)date('Y') - (int)$club['founded_year'])
            : null;

        $this->view->render('discover/club_profile', [
            'title'           => ($club['name'] ?? 'Klub') . ' — profil publiczny',
            'metaDescription' => $this->buildMetaDescription($club, $sports),
            'club'            => $club,
            'sports'          => $sports,
            'membersApprox'   => ClubModel::approxMembers($memberCount),
            'yearsActive'     => $yearsActive,
            'mapJsonUrl'      => url('api/discover/clubs.json?slug=' . rawurlencode($slug)),
            'canonicalUrl'    => url('discover/club/' . $slug),
        ]);
    }

    /**
     * GET /api/discover/clubs.json — JSON feed dla mapy (Leaflet).
     */
    public function clubsJson(): void
    {
        if (!$this->checkRateLimit()) return;

        $sport = trim((string)($_GET['sport'] ?? '')) ?: null;
        if ($sport !== null && !preg_match('/^[a-z0-9_-]+$/', $sport)) {
            $sport = null;
        }
        $city = trim((string)($_GET['city'] ?? '')) ?: null;
        $slug = trim((string)($_GET['slug'] ?? '')) ?: null;

        $clubModel = new ClubModel();

        if ($slug !== null) {
            $slug = $this->normalizeSlug($slug);
            $rows = ($slug !== null && ($c = $clubModel->findByPublicSlug($slug)) !== null) ? [$c] : [];
        } else {
            $rows = $clubModel->listForDiscovery($sport, $city, 1000);
        }

        $payload = [];
        foreach ($rows as $c) {
            if ($c['latitude'] === null || $c['longitude'] === null) {
                continue; // nie pokazuj na mapie bez geo
            }
            $sportsList = [];
            if (!empty($c['sports_offered_json'])) {
                $decoded = json_decode((string)$c['sports_offered_json'], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $s) {
                        if (isset($s['name'])) $sportsList[] = (string)$s['name'];
                    }
                }
            }
            $payload[] = [
                'id'     => (int)$c['id'],
                'name'   => (string)$c['name'],
                'city'   => (string)($c['city'] ?? ''),
                'lat'    => (float)$c['latitude'],
                'lng'    => (float)$c['longitude'],
                'sports' => $sportsList,
                'slug'   => (string)($c['public_slug'] ?? ''),
                'url'    => url('discover/club/' . ($c['public_slug'] ?? '')),
            ];
        }

        header('Cache-Control: public, max-age=120'); // 2 min
        $this->json($payload);
    }

    // ────────────────────────────────────────────────────────────
    // Internals
    // ────────────────────────────────────────────────────────────

    private function checkRateLimit(): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!RateLimiter::check($ip, self::RATE_LIMIT_ACTION, self::RATE_LIMIT_MAX, self::RATE_LIMIT_WINDOW)) {
            http_response_code(429);
            header('Retry-After: 60');
            echo '<h1>429 Too Many Requests</h1><p>Sprobuj ponownie za minute.</p>';
            return false;
        }
        RateLimiter::hit($ip, self::RATE_LIMIT_ACTION, self::RATE_LIMIT_MAX, self::RATE_LIMIT_WINDOW);
        return true;
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
            echo '<h1>404 — Strona nie istnieje</h1>';
        }
    }

    private function determineSource(): string
    {
        $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
        if (str_contains($referer, '/discover/club/')) return 'direct';
        if (preg_match('#/discover/[a-z0-9_-]+#', $referer)) return 'sport_filter';
        if (str_contains($referer, '/discover')) return 'discover_list';
        return 'direct';
    }

    private function logView(int $clubId, string $source): void
    {
        try {
            $ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $ipHash = hash('sha256', $ip);
            $stmt = Database::pdo()->prepare(
                "INSERT INTO club_discovery_views (club_id, ip_hash, source)
                 VALUES (?, ?, ?)"
            );
            $stmt->execute([$clubId, $ipHash, $source]);
        } catch (\Throwable) {
            // Brak audit = brak crash.
        }
    }

    private function safeMemberCount(int $clubId): int
    {
        try {
            $stmt = Database::pdo()->prepare(
                "SELECT COUNT(*) FROM members WHERE club_id = ? AND status = 'aktywny'"
            );
            $stmt->execute([$clubId]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function buildMetaDescription(array $club, array $sports): string
    {
        $desc = trim((string)($club['description_short'] ?? ''));
        if ($desc !== '') {
            return mb_substr($desc, 0, 250);
        }
        $sportNames = array_map(fn($s) => (string)($s['name'] ?? ''), $sports);
        $sportNames = array_filter($sportNames);
        $sportsStr  = implode(', ', $sportNames);
        $city       = trim((string)($club['city'] ?? ''));
        $parts      = [(string)$club['name']];
        if ($city !== '')      $parts[] = $city;
        if ($sportsStr !== '') $parts[] = 'oferuje: ' . $sportsStr;
        return mb_substr(implode(' — ', $parts), 0, 250);
    }
}
