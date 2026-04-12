<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\View;
use App\Models\ClubCustomizationModel;
use App\Models\ClubModel;
use App\Models\EventModel;
use App\Models\SportModel;

class PublicController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->view->setLayout('public');
    }

    /**
     * Lista aktywnych klubow.
     */
    public function clubList(): void
    {
        $db = Database::pdo();

        $clubs = $db->query(
            "SELECT c.*,
                    cc.logo_path, cc.subdomain, cc.motto,
                    (SELECT COUNT(*) FROM members m WHERE m.club_id = c.id AND m.status = 'aktywny') AS member_count
             FROM clubs c
             LEFT JOIN club_customization cc ON cc.club_id = c.id
             WHERE c.is_active = 1
             ORDER BY c.name ASC"
        )->fetchAll();

        // Dociagnij sekcje sportowe per klub
        foreach ($clubs as &$club) {
            $stmt = $db->prepare(
                "SELECT s.name, s.icon, s.color
                 FROM club_sports cs
                 JOIN sports s ON s.id = cs.sport_id
                 WHERE cs.club_id = ? AND cs.is_active = 1
                 ORDER BY s.sort_order, s.name"
            );
            $stmt->execute([(int)$club['id']]);
            $club['sports'] = $stmt->fetchAll();
        }
        unset($club);

        $this->render('public/club_list', [
            'title' => 'Kluby sportowe',
            'clubs' => $clubs,
        ]);
    }

    /**
     * Strona publiczna pojedynczego klubu.
     */
    public function clubPage(string $slug): void
    {
        $db = Database::pdo();

        $club = $this->findClubBySlug($slug);
        if ($club === null) {
            http_response_code(404);
            echo '<h1>404 - Klub nie znaleziony</h1>';
            return;
        }

        $clubId = (int)$club['id'];

        // Sekcje sportowe
        $stmt = $db->prepare(
            "SELECT s.name, s.icon, s.color, s.`key`
             FROM club_sports cs
             JOIN sports s ON s.id = cs.sport_id
             WHERE cs.club_id = ? AND cs.is_active = 1
             ORDER BY s.sort_order, s.name"
        );
        $stmt->execute([$clubId]);
        $sports = $stmt->fetchAll();

        // Nadchodzace wydarzenia (max 10)
        $stmt = $db->prepare(
            "SELECT e.name, e.event_date, e.end_date, e.location, e.type, e.status,
                    s.name AS sport_name, s.color AS sport_color
             FROM events e
             LEFT JOIN sports s ON s.id = e.sport_id
             WHERE e.club_id = ? AND e.event_date >= NOW()
             ORDER BY e.event_date ASC
             LIMIT 10"
        );
        $stmt->execute([$clubId]);
        $events = $stmt->fetchAll();

        $this->render('public/club_page', [
            'title'  => $club['name'],
            'club'   => $club,
            'sports' => $sports,
            'events' => $events,
        ]);
    }

    /**
     * Wyniki / zakonczone wydarzenia klubu.
     */
    public function clubResults(string $slug): void
    {
        $db = Database::pdo();

        $club = $this->findClubBySlug($slug);
        if ($club === null) {
            http_response_code(404);
            echo '<h1>404 - Klub nie znaleziony</h1>';
            return;
        }

        $clubId = (int)$club['id'];

        $stmt = $db->prepare(
            "SELECT e.name, e.event_date, e.end_date, e.location, e.type, e.status,
                    e.description,
                    s.name AS sport_name, s.color AS sport_color,
                    th.name AS home_team_name, ta.name AS away_team_name,
                    e.home_score, e.away_score
             FROM events e
             LEFT JOIN sports s ON s.id = e.sport_id
             LEFT JOIN teams th ON th.id = e.home_team_id
             LEFT JOIN teams ta ON ta.id = e.away_team_id
             WHERE e.club_id = ?
               AND (e.status IN ('zakonczone','zakończone','completed') OR e.event_date < NOW())
             ORDER BY e.event_date DESC
             LIMIT 20"
        );
        $stmt->execute([$clubId]);
        $results = $stmt->fetchAll();

        $this->render('public/club_results', [
            'title'   => $club['name'] . ' - Wyniki',
            'club'    => $club,
            'results' => $results,
        ]);
    }

    /**
     * Znajdz klub po subdomain slug.
     */
    private function findClubBySlug(string $slug): ?array
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT c.*, cc.logo_path, cc.subdomain, cc.motto, cc.primary_color
             FROM clubs c
             JOIN club_customization cc ON cc.club_id = c.id
             WHERE cc.subdomain = ? AND c.is_active = 1
             LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
