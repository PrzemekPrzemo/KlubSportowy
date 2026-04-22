<?php

namespace App\Sports\Mma\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Mma\Models\MmaResultModel;

class ResultsController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('mma');
    }

    public function index(): void
    {
        $model = new MmaResultModel();
        $this->render('mma/results/index', [
            'title'       => 'Walki MMA',
            'results'     => $model->listForClub(),
            'members'     => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'resultTypes' => MmaResultModel::$RESULTS,
            'methods'     => MmaResultModel::$METHODS,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('mma/results'); }
        $result = array_key_exists($_POST['result'] ?? '', MmaResultModel::$RESULTS) ? $_POST['result'] : null;
        $method = array_key_exists($_POST['method'] ?? '', MmaResultModel::$METHODS) ? $_POST['method'] : null;

        (new MmaResultModel())->insert([
            'member_id'     => $memberId,
            'opponent_name' => trim($_POST['opponent_name'] ?? '') ?: null,
            'event_name'    => trim($_POST['event_name'] ?? '') ?: 'Walka',
            'event_date'    => trim($_POST['event_date'] ?? '') ?: date('Y-m-d'),
            'venue'         => trim($_POST['venue'] ?? '') ?: null,
            'result'        => $result,
            'method'        => $method,
            'round'         => !empty($_POST['round'])  ? (int)$_POST['round']  : null,
            'time_s'        => !empty($_POST['time_s']) ? (int)$_POST['time_s'] : null,
            'weight_class'  => trim($_POST['weight_class'] ?? '') ?: null,
            'amateur'       => isset($_POST['amateur']) ? 1 : 0,
            'notes'         => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Walka dodana.');
        $this->redirect('mma/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new MmaResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('mma/results');
    }
}
