<?php

namespace App\Sports\Rugby\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Sports\Rugby\Models\RugbyMatchModel;
use App\Sports\Rugby\Models\RugbyMatchStatsModel;
use App\Sports\Rugby\Models\RugbyTeamModel;

class StatsController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('rugby');
    }

    public function statsForm(string $matchId): void
    {
        $sModel = new RugbyMatchStatsModel();
        $match  = $sModel->findMatch((int)$matchId);
        if (!$match) {
            Session::flash('error', 'Mecz nie istnieje lub brak dostępu.');
            $this->redirect('rugby/matches');
        }
        $this->render('rugby/matches/stats_form', [
            'title'        => 'Statystyki meczu — Rugby',
            'match'        => $match,
            'stats'        => $sModel->forMatch((int)$matchId),
            'statsColumns' => $sModel->statsColumns(),
            'sportKey'     => 'rugby',
            'submitUrl'    => 'rugby/matches/' . (int)$matchId . '/stats',
            'backUrl'      => 'rugby/matches',
        ]);
    }

    public function statsSave(string $matchId): void
    {
        Csrf::verify();
        $sModel = new RugbyMatchStatsModel();
        $match  = $sModel->findMatch((int)$matchId);
        if (!$match) {
            Session::flash('error', 'Mecz nie istnieje lub brak dostępu.');
            $this->redirect('rugby/matches');
        }
        foreach (['home', 'away'] as $side) {
            $payload = $_POST[$side] ?? [];
            if (!is_array($payload)) continue;
            $sModel->upsert((int)$matchId, $side, $payload);
        }
        Session::flash('success', 'Statystyki zapisane.');
        $this->redirect('rugby/matches');
    }

    public function dashboard(): void
    {
        $mModel = new RugbyMatchModel();
        $tModel = new RugbyTeamModel();

        // Top scorers — agregacja z rugby_events
        $stmt = Database::pdo()->prepare(
            "SELECT m.id AS member_id, m.first_name, m.last_name,
                    COALESCE(SUM(re.points),0) AS total_points,
                    SUM(CASE WHEN re.event_type='przyłożenie' THEN 1 ELSE 0 END) AS tries
             FROM rugby_events re
             JOIN rugby_players rp ON rp.id = re.player_id
             JOIN members m ON m.id = rp.member_id
             WHERE re.club_id = ?
             GROUP BY m.id, m.first_name, m.last_name
             HAVING total_points > 0
             ORDER BY total_points DESC
             LIMIT 10"
        );
        $stmt->execute([\App\Helpers\ClubContext::current()]);
        $topScorers = $stmt->fetchAll();

        $this->render('rugby/stats/dashboard', [
            'title'      => 'Dashboard — Rugby',
            'teams'      => $tModel->listForClub(),
            'topScorers' => $topScorers,
            'recent'     => array_slice($mModel->listForClub(), 0, 10),
            'sportKey'   => 'rugby',
            'sportLabel' => 'Rugby',
        ]);
    }
}
