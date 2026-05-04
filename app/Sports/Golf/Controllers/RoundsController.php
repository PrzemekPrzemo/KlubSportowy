<?php

namespace App\Sports\Golf\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Golf\Models\GolfRoundModel;

class RoundsController extends BaseController
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
        $this->render('golf/rounds/index', [
            'title'   => 'Rundy golfowe',
            'rounds'  => (new GolfRoundModel())->listForClub(),
            'members' => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'tees'    => GolfRoundModel::$TEES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('golf/rounds'); }
        $tees = array_key_exists($_POST['tees'] ?? '', GolfRoundModel::$TEES) ? $_POST['tees'] : 'yellow';

        (new GolfRoundModel())->insert([
            'member_id'     => $memberId,
            'course_name'   => trim($_POST['course_name'] ?? '') ?: 'Kurs',
            'round_date'    => trim($_POST['round_date'] ?? '') ?: date('Y-m-d'),
            'tees'          => $tees,
            'holes'         => max(1, min(18, (int)($_POST['holes'] ?? 18))),
            'total_strokes' => !empty($_POST['total_strokes']) ? (int)$_POST['total_strokes'] : null,
            'gross_score'   => isset($_POST['gross_score']) && $_POST['gross_score'] !== '' ? (int)$_POST['gross_score'] : null,
            'net_score'     => !empty($_POST['net_score']) ? (float)$_POST['net_score'] : null,
            'slope_rating'  => !empty($_POST['slope_rating']) ? (int)$_POST['slope_rating'] : null,
            'course_rating' => !empty($_POST['course_rating']) ? (float)$_POST['course_rating'] : null,
            'notes'         => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Runda dodana.');
        $this->redirect('golf/rounds');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new GolfRoundModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('golf/rounds');
    }
}
