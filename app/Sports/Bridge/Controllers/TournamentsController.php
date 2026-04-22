<?php

namespace App\Sports\Bridge\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Bridge\Models\BridgePartnershipModel;
use App\Sports\Bridge\Models\BridgeTournamentModel;

class TournamentsController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('bridge');
    }

    public function index(): void
    {
        $this->render('bridge/tournaments/index', [
            'title'        => 'Turnieje brydża',
            'tournaments'  => (new BridgeTournamentModel())->listForClub(),
            'partnerships' => (new BridgePartnershipModel())->listForClub(true),
            'members'      => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'types'        => BridgeTournamentModel::$TYPES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') { Session::flash('error', 'Podaj nazwę turnieju.'); $this->redirect('bridge/tournaments'); }
        $type = array_key_exists($_POST['tournament_type'] ?? '', BridgeTournamentModel::$TYPES) ? $_POST['tournament_type'] : 'para';

        (new BridgeTournamentModel())->insert([
            'name'            => $name,
            'tournament_type' => $type,
            'tournament_date' => trim($_POST['tournament_date'] ?? '') ?: date('Y-m-d'),
            'location'        => trim($_POST['location'] ?? '') ?: null,
            'partnership_id'  => !empty($_POST['partnership_id']) ? (int)$_POST['partnership_id'] : null,
            'member_id'       => !empty($_POST['member_id'])      ? (int)$_POST['member_id']      : null,
            'place'           => !empty($_POST['place']) ? (int)$_POST['place'] : null,
            'score_mp'        => $_POST['score_mp']  !== '' ? (float)$_POST['score_mp']  : null,
            'score_imp'       => $_POST['score_imp'] !== '' ? (float)$_POST['score_imp'] : null,
            'pzbs_points'     => $_POST['pzbs_points'] !== '' ? (float)$_POST['pzbs_points'] : null,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Turniej zapisany.');
        $this->redirect('bridge/tournaments');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new BridgeTournamentModel())->delete((int)$id);
        Session::flash('success', 'Turniej usunięty.');
        $this->redirect('bridge/tournaments');
    }
}
