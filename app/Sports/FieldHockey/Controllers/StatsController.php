<?php

namespace App\Sports\FieldHockey\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\ClubContext;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Sports\FieldHockey\Models\FieldHockeyMatchStatsModel;

class StatsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function statsForm(string $matchId): void
    {
        $sModel = new FieldHockeyMatchStatsModel();
        $match  = $sModel->findMatch((int)$matchId);
        if (!$match) {
            Session::flash('error', 'Mecz nie istnieje lub brak dostępu.');
            $this->redirect('fieldhockey/matches');
        }
        $this->render('field_hockey/matches/stats_form', [
            'title'        => 'Statystyki meczu — Hokej na trawie',
            'match'        => $match,
            'stats'        => $sModel->forMatch((int)$matchId),
            'statsColumns' => $sModel->statsColumns(),
            'sportKey'     => 'fieldhockey',
            'submitUrl'    => 'fieldhockey/matches/' . (int)$matchId . '/stats',
            'backUrl'      => 'fieldhockey/matches',
        ]);
    }

    public function statsSave(string $matchId): void
    {
        Csrf::verify();
        $sModel = new FieldHockeyMatchStatsModel();
        $match  = $sModel->findMatch((int)$matchId);
        if (!$match) {
            Session::flash('error', 'Mecz nie istnieje lub brak dostępu.');
            $this->redirect('fieldhockey/matches');
        }
        foreach (['home', 'away'] as $side) {
            $payload = $_POST[$side] ?? [];
            if (!is_array($payload)) continue;
            $sModel->upsert((int)$matchId, $side, $payload);
        }
        Session::flash('success', 'Statystyki zapisane.');
        $this->redirect('fieldhockey/matches');
    }

    public function dashboard(): void
    {
        $clubId = ClubContext::current();
        $stmt = Database::pdo()->prepare(
            "SELECT m.id AS member_id, m.first_name, m.last_name,
                    SUM(CASE WHEN e.event_type='gol' THEN 1 ELSE 0 END) AS goals,
                    SUM(CASE WHEN e.event_type='asysta' THEN 1 ELSE 0 END) AS assists,
                    SUM(CASE WHEN e.event_type='PC' THEN 1 ELSE 0 END) AS penalty_corners
             FROM field_hockey_events e
             JOIN field_hockey_players p ON p.id = e.player_id
             JOIN members m ON m.id = p.member_id
             WHERE e.club_id = ?
             GROUP BY m.id, m.first_name, m.last_name
             HAVING goals > 0 OR assists > 0
             ORDER BY goals DESC, assists DESC
             LIMIT 10"
        );
        $stmt->execute([$clubId]);
        $topScorers = $stmt->fetchAll();

        $teamsStmt = Database::pdo()->prepare("SELECT * FROM field_hockey_teams WHERE club_id=? ORDER BY name");
        $teamsStmt->execute([$clubId]);
        $teams = $teamsStmt->fetchAll();

        $recentStmt = Database::pdo()->prepare(
            "SELECT m.*, t.name AS home_team_name
             FROM field_hockey_matches m
             JOIN field_hockey_teams t ON t.id = m.home_team_id
             WHERE m.club_id = ?
             ORDER BY m.match_date DESC LIMIT 10"
        );
        $recentStmt->execute([$clubId]);

        $this->render('field_hockey/stats/dashboard', [
            'title'      => 'Dashboard — Hokej na trawie',
            'teams'      => $teams,
            'topScorers' => $topScorers,
            'recent'     => $recentStmt->fetchAll(),
            'sportKey'   => 'fieldhockey',
            'sportLabel' => 'Hokej na trawie',
        ]);
    }
}
