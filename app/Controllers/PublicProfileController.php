<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Database;
use App\Helpers\MemberAuth;
use App\Helpers\RateLimiter;
use App\Models\MemberModel;
use App\Models\SportRankingModel;
use App\Models\TenantAccessLogModel;

/**
 * Publiczny profil zawodnika — opt-in widget.
 *
 * Routes:
 *   GET /u/:slug      — publiczny profil (no auth required)
 *   GET /sitemap.xml  — sitemap dla publicznych profili (SEO)
 *
 * Zasady:
 *   - Domyslnie wszystko PRIVATE (opt-in only)
 *   - club_only: viewer musi byc czlonkiem tego samego klubu (Auth lub MemberAuth)
 *   - public:   dostepne dla wszystkich
 *   - Anonimowani czlonkowie (is_anonymized=1) — auto-blocked w MemberModel
 */
class PublicProfileController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->view->setLayout('public');
    }

    /**
     * GET /u/:slug — wyswietl publiczny profil zawodnika.
     */
    public function show(string $slug): void
    {
        // 1) Walidacja slug-a (regex zgodny ze schema'a)
        $slug = strtolower(trim($slug));
        if (!preg_match('/^[a-z0-9-]{3,120}$/', $slug)) {
            $this->notFound();
            return;
        }

        // 2) IP rate limit — 100 wyswietlen / IP / godz (anti-scrape)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!RateLimiter::check($ip, 'public_profile_view', 100, 60)) {
            http_response_code(429);
            echo '<h1>429 — Zbyt wiele zapytan</h1><p>Sprobuj ponownie za godzine.</p>';
            return;
        }
        RateLimiter::hit($ip, 'public_profile_view', 100, 60);

        // 3) Lookup
        $memberModel = new MemberModel();
        $member      = $memberModel->findByPublicSlug($slug);
        if ($member === null) {
            $this->notFound();
            return;
        }

        // 4) club_only — sprawdz czy viewer ma dostep
        if ($member['public_profile_visibility'] === 'club_only') {
            $viewerClubId = $this->viewerClubId();
            if ($viewerClubId === null || (int)$viewerClubId !== (int)$member['club_id']) {
                // 404 (a nie 403) zeby nie ujawniac istnienia profilu
                $this->notFound();
                return;
            }
        }

        // 5) Increment view counter + audit
        try {
            $db = Database::pdo();
            $db->prepare(
                "UPDATE members SET public_profile_view_count = public_profile_view_count + 1
                 WHERE id = ?"
            )->execute([(int)$member['id']]);

            $stmt = $db->prepare(
                "INSERT INTO public_profile_views (member_id, viewer_ip, user_agent, referrer)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                (int)$member['id'],
                $ip,
                substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
                substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 500),
            ]);
        } catch (\Throwable) {
            // Audit/view counter nie moze crashowac requestu
        }

        // 6) Zbierz dane wg flag visibility
        $payload = $this->buildProfilePayload($member);

        $this->view->render('public/member_profile', $payload);
    }

    /**
     * GET /sitemap.xml — lista publicznych profili dla wyszukiwarek.
     */
    public function sitemap(): void
    {
        $profiles = (new MemberModel())->listPublicProfiles(5000);

        header('Content-Type: application/xml; charset=utf-8');
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $base = rtrim(BASE_URL, '/');
        foreach ($profiles as $p) {
            $slug = (string)($p['public_profile_slug'] ?? '');
            if ($slug === '') continue;
            echo "  <url>\n";
            echo '    <loc>' . htmlspecialchars($base . '/u/' . $slug, ENT_XML1) . "</loc>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.5</priority>\n";
            echo "  </url>\n";
        }
        echo '</urlset>' . "\n";
        exit;
    }

    /**
     * GET /discover — lista publicznych profili (best-effort discovery).
     */
    public function discover(): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 24;
        $offset  = ($page - 1) * $perPage;

        $db = Database::pdo();
        $total = (int)$db->query(
            "SELECT COUNT(*) FROM members
             WHERE public_profile_visibility = 'public'
               AND public_profile_slug IS NOT NULL
               AND (is_anonymized IS NULL OR is_anonymized = 0)"
        )->fetchColumn();

        $stmt = $db->prepare(
            "SELECT m.id, m.first_name, m.last_name, m.public_profile_slug,
                    m.public_profile_view_count, m.public_profile_show_avatar,
                    m.public_profile_show_club, m.photo_path, m.club_id, c.name AS club_name
             FROM members m
             LEFT JOIN clubs c ON c.id = m.club_id
             WHERE m.public_profile_visibility = 'public'
               AND m.public_profile_slug IS NOT NULL
               AND (m.is_anonymized IS NULL OR m.is_anonymized = 0)
             ORDER BY m.public_profile_view_count DESC, m.last_name ASC
             LIMIT " . (int)$perPage . " OFFSET " . (int)$offset
        );
        $stmt->execute();
        $profiles = $stmt->fetchAll();

        $this->view->render('public/discover', [
            'title'    => 'Zawodnicy — odkryj profile',
            'profiles' => $profiles,
            'page'     => $page,
            'perPage'  => $perPage,
            'total'    => $total,
            'lastPage' => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    // -- helpers --------------------------------------------------------

    private function viewerClubId(): ?int
    {
        // Admin / klub-staff
        $authClub = \App\Helpers\ClubContext::current();
        if ($authClub !== null && Auth::id() !== null) {
            return (int)$authClub;
        }
        // Member portal user
        if (MemberAuth::check()) {
            return MemberAuth::clubId();
        }
        return null;
    }

    private function buildProfilePayload(array $member): array
    {
        $memberId = (int)$member['id'];
        $db       = Database::pdo();

        // Klub (jesli show_club)
        $club = null;
        if (!empty($member['public_profile_show_club']) && !empty($member['club_id'])) {
            $stmt = $db->prepare(
                "SELECT c.id, c.name, c.city,
                        cc.subdomain, cc.logo_path, cc.primary_color
                 FROM clubs c
                 LEFT JOIN club_customization cc ON cc.club_id = c.id
                 WHERE c.id = ? LIMIT 1"
            );
            $stmt->execute([(int)$member['club_id']]);
            $club = $stmt->fetch() ?: null;
        }

        // Sporty (jesli show_sports)
        $sports = [];
        if (!empty($member['public_profile_show_sports'])) {
            $stmt = $db->prepare(
                "SELECT s.`key` AS sport_key, s.name, s.icon, s.color
                 FROM member_sports ms
                 JOIN club_sports cs ON cs.id = ms.club_sport_id
                 JOIN sports s ON s.id = cs.sport_id
                 WHERE ms.member_id = ? AND ms.is_active = 1
                 ORDER BY s.sort_order, s.name"
            );
            $stmt->execute([$memberId]);
            $sports = $stmt->fetchAll();
        }

        // Rankingi (jesli show_rankings)
        $rankings = [];
        if (!empty($member['public_profile_show_rankings'])) {
            $stmt = $db->prepare(
                "SELECT sport_key, season, ranking_points, ranking_position,
                        competitions_count, wins
                 FROM sport_rankings
                 WHERE member_id = ?
                 ORDER BY season DESC, ranking_points DESC
                 LIMIT 20"
            );
            $stmt->execute([$memberId]);
            $rankings = $stmt->fetchAll();
        }

        // Turnieje (jesli show_tournaments) — top 10 ostatnich
        $tournaments = [];
        if (!empty($member['public_profile_show_tournaments'])) {
            try {
                $stmt = $db->prepare(
                    "SELECT t.id, t.name, t.start_date, t.end_date, t.status,
                            tp.seed, tp.eliminated
                     FROM tournament_participants tp
                     JOIN tournaments t ON t.id = tp.tournament_id
                     WHERE tp.member_id = ?
                     ORDER BY t.start_date DESC
                     LIMIT 10"
                );
                $stmt->execute([$memberId]);
                $tournaments = $stmt->fetchAll();
            } catch (\Throwable) {
                $tournaments = [];
            }
        }

        // Wiek / rok urodzenia
        $age       = null;
        $birthYear = null;
        if (!empty($member['birth_date'])) {
            $bd = strtotime((string)$member['birth_date']);
            if ($bd !== false) {
                $birthYear = (int)date('Y', $bd);
                if (!empty($member['public_profile_show_age'])) {
                    $age = (int)date('Y') - $birthYear
                        - (date('md') < date('md', $bd) ? 1 : 0);
                }
            }
        }
        if (empty($member['public_profile_show_birth_year'])) {
            $birthYear = null;
        }

        // Bio — sanitize (plain text, max 500)
        $bio = trim((string)($member['public_profile_bio'] ?? ''));
        if (strlen($bio) > 500) {
            $bio = substr($bio, 0, 500);
        }

        // Description for meta tags
        $metaDesc = $bio !== ''
            ? mb_substr($bio, 0, 160, 'UTF-8')
            : trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''))
              . ' — profil zawodnika' . ($club ? ' w klubie ' . $club['name'] : '');

        return [
            'title'       => trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')),
            'member'      => $member,
            'club'        => $club,
            'sports'      => $sports,
            'rankings'    => $rankings,
            'tournaments' => $tournaments,
            'bio'         => $bio,
            'age'         => $age,
            'birthYear'   => $birthYear,
            'metaDesc'    => $metaDesc,
            'isPublic'    => $member['public_profile_visibility'] === 'public',
            'profileUrl'  => rtrim(BASE_URL, '/') . '/u/' . $member['public_profile_slug'],
        ];
    }

    private function notFound(): void
    {
        http_response_code(404);
        $view = ROOT_PATH . '/app/Views/errors/404.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<h1>404 — Profil nie istnieje</h1>';
        }
    }
}
