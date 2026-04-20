<?php

namespace App\Sports\Bjj\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Bjj\Models\BjjResultModel;

class ResultsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $gi   = $_GET['gi']   ?? null;
        $year = !empty($_GET['year']) ? (int)$_GET['year'] : null;

        $this->render('bjj/results/index', [
            'title'      => 'Wyniki walk — BJJ',
            'results'    => (new BjjResultModel())->listForClub(null, $gi, $year),
            'members'    => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'weights'    => BjjResultModel::$WEIGHT_CATEGORIES,
            'filterGi'   => $gi,
            'filterYear' => $year,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('bjj/results');
        }

        $result = in_array($_POST['result'] ?? '', ['win','loss','draw','dq'], true) ? $_POST['result'] : 'win';
        $method = in_array($_POST['method'] ?? '', ['submission','points','decision','referee','walkover'], true) ? $_POST['method'] : null;
        $gi     = in_array($_POST['gi'] ?? 'gi', ['gi','nogi'], true) ? $_POST['gi'] : 'gi';

        (new BjjResultModel())->insert([
            'member_id'       => $memberId,
            'event_name'      => trim($_POST['event_name'] ?? ''),
            'event_date'      => trim($_POST['event_date'] ?? '') ?: date('Y-m-d'),
            'opponent'        => trim($_POST['opponent'] ?? '') ?: null,
            'result'          => $result,
            'method'          => $method,
            'weight_category' => trim($_POST['weight_category'] ?? '') ?: null,
            'gi'              => $gi,
            'placement'       => !empty($_POST['placement']) ? (int)$_POST['placement'] : null,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('bjj/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new BjjResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('bjj/results');
    }
}
