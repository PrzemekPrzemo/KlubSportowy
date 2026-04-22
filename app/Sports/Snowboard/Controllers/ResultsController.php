<?php

namespace App\Sports\Snowboard\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Snowboard\Models\SnowboardResultModel;

class ResultsController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('snowboard');
    }

    public function index(): void
    {
        $disc = $_GET['discipline'] ?? null;
        $model = new SnowboardResultModel();
        $this->render('snowboard/results/index', [
            'title'       => 'Wyniki — Snowboard',
            'results'     => $model->listForClub(null, $disc),
            'members'     => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'disciplines' => SnowboardResultModel::$DISCIPLINES,
            'discFilter'  => $disc,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('snowboard/results'); }
        $disc = array_key_exists($_POST['discipline'] ?? '', SnowboardResultModel::$DISCIPLINES) ? $_POST['discipline'] : 'halfpipe';

        $r1 = !empty($_POST['run1_score']) ? (float)$_POST['run1_score'] : null;
        $r2 = !empty($_POST['run2_score']) ? (float)$_POST['run2_score'] : null;
        $best = null;
        if ($r1 !== null && $r2 !== null) $best = max($r1, $r2);
        elseif ($r1 !== null)             $best = $r1;

        (new SnowboardResultModel())->insert([
            'member_id'  => $memberId,
            'discipline' => $disc,
            'event_name' => trim($_POST['event_name'] ?? '') ?: 'Zawody',
            'event_date' => trim($_POST['event_date'] ?? '') ?: date('Y-m-d'),
            'venue'      => trim($_POST['venue'] ?? '') ?: null,
            'category'   => trim($_POST['category'] ?? '') ?: null,
            'run1_score' => $r1,
            'run2_score' => $r2,
            'best_score' => $best,
            'place'      => !empty($_POST['place']) ? (int)$_POST['place'] : null,
            'fis_points' => !empty($_POST['fis_points']) ? (float)$_POST['fis_points'] : null,
            'dnf'        => isset($_POST['dnf']) ? 1 : 0,
            'dns'        => isset($_POST['dns']) ? 1 : 0,
            'notes'      => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('snowboard/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new SnowboardResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('snowboard/results');
    }
}
