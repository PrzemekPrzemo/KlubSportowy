<?php

namespace App\Sports\Kickboxing\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Kickboxing\Models\KickboxingResultModel;

class ResultsController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('kickboxing');
    }

    public function index(): void
    {
        $style = $_GET['style'] ?? null;
        $model = new KickboxingResultModel();
        $this->render('kickboxing/results/index', [
            'title'       => 'Walki — Kickboxing',
            'results'     => $model->listForClub(null, $style),
            'members'     => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'styles'      => KickboxingResultModel::$STYLES,
            'resultTypes' => KickboxingResultModel::$RESULTS,
            'methods'     => KickboxingResultModel::$METHODS,
            'styleFilter' => $style,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('kickboxing/results'); }
        $style  = array_key_exists($_POST['style']  ?? '', KickboxingResultModel::$STYLES)  ? $_POST['style']  : 'K1';
        $result = array_key_exists($_POST['result'] ?? '', KickboxingResultModel::$RESULTS) ? $_POST['result'] : null;
        $method = array_key_exists($_POST['method'] ?? '', KickboxingResultModel::$METHODS) ? $_POST['method'] : null;

        (new KickboxingResultModel())->insert([
            'member_id'     => $memberId,
            'style'         => $style,
            'event_name'    => trim($_POST['event_name'] ?? '') ?: 'Walka',
            'event_date'    => trim($_POST['event_date'] ?? '') ?: date('Y-m-d'),
            'venue'         => trim($_POST['venue'] ?? '') ?: null,
            'opponent_name' => trim($_POST['opponent_name'] ?? '') ?: null,
            'weight_class'  => trim($_POST['weight_class'] ?? '') ?: null,
            'result'        => $result,
            'method'        => $method,
            'rounds_total'  => !empty($_POST['rounds_total'])  ? (int)$_POST['rounds_total']  : null,
            'rounds_fought' => !empty($_POST['rounds_fought']) ? (int)$_POST['rounds_fought'] : null,
            'amateur'       => isset($_POST['amateur']) ? 1 : 0,
            'notes'         => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Walka dodana.');
        $this->redirect('kickboxing/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new KickboxingResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('kickboxing/results');
    }
}
