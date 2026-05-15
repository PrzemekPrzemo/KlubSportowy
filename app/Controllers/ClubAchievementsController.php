<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use PDO;

/**
 * Admin: zarzadzanie achievements per klub.
 *
 * - GET  /club/achievements                 — lista (global + custom klubu)
 * - GET  /club/achievements/create          — formularz nowej odznaki
 * - POST /club/achievements/store           — zapisanie
 * - GET  /club/achievements/:id/edit        — edycja (tylko custom)
 * - POST /club/achievements/:id/update      — update
 * - POST /club/achievements/:id/delete      — usuniecie (tylko custom)
 * - POST /club/achievements/:id/toggle      — aktywuj/dezaktywuj
 */
final class ClubAchievementsController extends BaseController
{
    private const CATEGORIES = [
        'attendance', 'tournament', 'training', 'milestone', 'sport_specific', 'social', 'other',
    ];
    private const RARITIES = ['common', 'uncommon', 'rare', 'epic', 'legendary'];

    private const CRITERIA_TYPES = [
        'trainings_count'          => 'Liczba treningow (obecnych)',
        'tournament_played'        => 'Wzial udzial w turnieju',
        'tournament_place'         => 'Miejsce w turnieju',
        'tournament_top'           => 'Top N w turnieju',
        'tournaments_played_count' => 'Liczba turniejow',
        'season_wins'              => 'Liczba zwyciestw w sezonie',
        'membership_years'         => 'Lata w klubie',
        'perfect_month'            => 'Miesiac z pelna frekwencja',
        'training_streak'          => 'Streak treningow z rzedu',
        'referrals_count'          => 'Liczba polecen',
        'team_match_won'           => 'Wygrana w meczu druzynowym',
        'belt_promotions_count'    => 'Liczba promocji pasa',
        'profile_complete'         => 'Pelny profil',
    ];

    public function index(): void
    {
        $this->requireRole(['zarzad', 'admin']);
        $clubId = (int)(ClubContext::current() ?? 0);

        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT a.*, (SELECT COUNT(*) FROM member_achievements ma WHERE ma.achievement_id = a.id) AS earned_count
             FROM achievement_catalog a
             WHERE a.club_id IS NULL OR a.club_id = ?
             ORDER BY (a.club_id IS NULL) DESC, a.sort_order ASC, a.id ASC"
        );
        $stmt->execute([$clubId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view->setLayout('main');
        $this->view->render('club/achievements/index', [
            'title' => 'Odznaki klubu',
            'items' => $items,
            'clubId' => $clubId,
        ]);
    }

    public function create(): void
    {
        $this->requireRole(['zarzad', 'admin']);
        $this->view->setLayout('main');
        $this->view->render('club/achievements/form', [
            'title'         => 'Nowa odznaka',
            'achievement'   => null,
            'categories'    => self::CATEGORIES,
            'rarities'      => self::RARITIES,
            'criteriaTypes' => self::CRITERIA_TYPES,
        ]);
    }

    public function store(): void
    {
        $this->requireRole(['zarzad', 'admin']);
        Csrf::verify();
        $clubId = (int)(ClubContext::current() ?? 0);
        if ($clubId === 0) {
            Session::flash('error', 'Brak aktywnego klubu.');
            $this->redirect('club/achievements');
        }

        $data = $this->parsePost();
        if ($data === null) {
            $this->redirect('club/achievements/create');
        }

        $db = Database::pdo();
        try {
            $stmt = $db->prepare(
                "INSERT INTO achievement_catalog
                   (club_id, code, name, description, category, icon, rarity, points,
                    criteria, sport_key, is_active, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $clubId,
                $data['code'],
                $data['name'],
                $data['description'],
                $data['category'],
                $data['icon'],
                $data['rarity'],
                $data['points'],
                $data['criteria'],
                $data['sport_key'],
                $data['is_active'],
                $data['sort_order'],
            ]);
            Session::flash('success', 'Odznaka utworzona.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Blad zapisu: ' . $e->getMessage());
            $this->redirect('club/achievements/create');
        }
        $this->redirect('club/achievements');
    }

    public function edit(string $id): void
    {
        $this->requireRole(['zarzad', 'admin']);
        $clubId = (int)(ClubContext::current() ?? 0);
        $achievement = $this->loadCustom((int)$id, $clubId);
        if ($achievement === null) {
            Session::flash('error', 'Mozesz edytowac tylko wlasne odznaki klubu.');
            $this->redirect('club/achievements');
        }
        $this->view->setLayout('main');
        $this->view->render('club/achievements/form', [
            'title'         => 'Edycja odznaki',
            'achievement'   => $achievement,
            'categories'    => self::CATEGORIES,
            'rarities'      => self::RARITIES,
            'criteriaTypes' => self::CRITERIA_TYPES,
        ]);
    }

    public function update(string $id): void
    {
        $this->requireRole(['zarzad', 'admin']);
        Csrf::verify();
        $clubId = (int)(ClubContext::current() ?? 0);
        $achievement = $this->loadCustom((int)$id, $clubId);
        if ($achievement === null) {
            Session::flash('error', 'Brak uprawnien do edycji tej odznaki.');
            $this->redirect('club/achievements');
        }
        $data = $this->parsePost();
        if ($data === null) {
            $this->redirect('club/achievements/' . (int)$id . '/edit');
        }
        $db = Database::pdo();
        $stmt = $db->prepare(
            "UPDATE achievement_catalog
             SET code=?, name=?, description=?, category=?, icon=?, rarity=?,
                 points=?, criteria=?, sport_key=?, is_active=?, sort_order=?
             WHERE id = ? AND club_id = ?"
        );
        $stmt->execute([
            $data['code'], $data['name'], $data['description'], $data['category'],
            $data['icon'], $data['rarity'], $data['points'], $data['criteria'],
            $data['sport_key'], $data['is_active'], $data['sort_order'],
            (int)$id, $clubId,
        ]);
        Session::flash('success', 'Odznaka zaktualizowana.');
        $this->redirect('club/achievements');
    }

    public function delete(string $id): void
    {
        $this->requireRole(['zarzad', 'admin']);
        Csrf::verify();
        $clubId = (int)(ClubContext::current() ?? 0);
        $achievement = $this->loadCustom((int)$id, $clubId);
        if ($achievement === null) {
            Session::flash('error', 'Mozna usuwac tylko wlasne odznaki klubu.');
            $this->redirect('club/achievements');
        }
        $db = Database::pdo();
        $db->prepare("DELETE FROM achievement_catalog WHERE id = ? AND club_id = ?")
           ->execute([(int)$id, $clubId]);
        Session::flash('success', 'Odznaka usunieta.');
        $this->redirect('club/achievements');
    }

    public function toggle(string $id): void
    {
        $this->requireRole(['zarzad', 'admin']);
        Csrf::verify();
        $clubId = (int)(ClubContext::current() ?? 0);
        $achievement = $this->loadCustom((int)$id, $clubId);
        if ($achievement === null) {
            Session::flash('error', 'Mozna przelaczac tylko wlasne odznaki klubu.');
            $this->redirect('club/achievements');
        }
        $db = Database::pdo();
        $db->prepare("UPDATE achievement_catalog SET is_active = 1 - is_active WHERE id = ? AND club_id = ?")
           ->execute([(int)$id, $clubId]);
        Session::flash('success', 'Status zmieniony.');
        $this->redirect('club/achievements');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadCustom(int $id, int $clubId): ?array
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT * FROM achievement_catalog WHERE id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([$id, $clubId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parsePost(): ?array
    {
        $code = trim((string)($_POST['code'] ?? ''));
        $name = trim((string)($_POST['name'] ?? ''));
        $category = (string)($_POST['category'] ?? 'other');
        $rarity = (string)($_POST['rarity'] ?? 'common');
        $criteriaType = (string)($_POST['criteria_type'] ?? '');

        if ($code === '' || $name === '' || $criteriaType === '') {
            Session::flash('error', 'Pola code, name oraz typ kryterium sa wymagane.');
            return null;
        }
        if (!in_array($category, self::CATEGORIES, true)) {
            $category = 'other';
        }
        if (!in_array($rarity, self::RARITIES, true)) {
            $rarity = 'common';
        }

        // Zbuduj criteria JSON wedlug typu.
        $criteria = ['type' => $criteriaType];
        $count = (int)($_POST['criteria_count'] ?? 0);
        $place = (int)($_POST['criteria_place'] ?? 0);
        $years = (int)($_POST['criteria_years'] ?? 0);
        $n     = (int)($_POST['criteria_n'] ?? 0);
        switch ($criteriaType) {
            case 'trainings_count':
            case 'tournaments_played_count':
            case 'season_wins':
            case 'training_streak':
            case 'referrals_count':
            case 'belt_promotions_count':
                $criteria['count'] = max(1, $count);
                break;
            case 'tournament_place':
                $criteria['place'] = max(1, $place);
                break;
            case 'tournament_top':
                $criteria['n'] = max(1, $n);
                break;
            case 'membership_years':
                $criteria['years'] = max(1, $years);
                break;
            // tournament_played, perfect_month, team_match_won, profile_complete — brak parametrow
        }

        return [
            'code'        => substr($code, 0, 60),
            'name'        => substr($name, 0, 120),
            'description' => trim((string)($_POST['description'] ?? '')) ?: null,
            'category'    => $category,
            'icon'        => substr(trim((string)($_POST['icon'] ?? '🏆')) ?: '🏆', 0, 80),
            'rarity'      => $rarity,
            'points'      => max(0, (int)($_POST['points'] ?? 10)),
            'criteria'    => json_encode($criteria, JSON_UNESCAPED_UNICODE),
            'sport_key'   => trim((string)($_POST['sport_key'] ?? '')) ?: null,
            'is_active'   => !empty($_POST['is_active']) ? 1 : 0,
            'sort_order'  => max(0, (int)($_POST['sort_order'] ?? 0)),
        ];
    }
}
