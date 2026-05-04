<?php

namespace App\Sports\Golf\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Golf\Models\GolfHandicapModel;

class HandicapsController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('golf');
    }

    public function index(): void
    {
        $this->render('golf/handicaps/index', [
            'title'     => 'Handicap WHS — Golf',
            'handicaps' => (new GolfHandicapModel())->listForClub(),
            'members'   => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'sources'   => GolfHandicapModel::$SOURCES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('golf/handicaps'); }
        $source = array_key_exists($_POST['source'] ?? '', GolfHandicapModel::$SOURCES) ? $_POST['source'] : 'klubowy';

        (new GolfHandicapModel())->insert([
            'member_id'  => $memberId,
            'whs_index'  => (float)($_POST['whs_index'] ?? 0),
            'updated_at' => trim($_POST['updated_at'] ?? '') ?: date('Y-m-d'),
            'source'     => $source,
            'notes'      => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Handicap zapisany.');
        $this->redirect('golf/handicaps');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new GolfHandicapModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('golf/handicaps');
    }
}
