<?php

namespace App\Sports\CrossFit\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\CrossFit\Models\CrossFitPrModel;

class PersonalRecordsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $memberId = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
        $model    = new CrossFitPrModel();
        $members  = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $prs      = $memberId ? $model->listForMember($memberId) : [];
        $topPrs   = $memberId ? $model->topByMember($memberId, 10) : [];

        $this->render('crossfit/prs/index', [
            'title'           => 'Personal Records — CrossFit',
            'members'         => $members,
            'selectedMember'  => $memberId,
            'prs'             => $prs,
            'topPrs'          => $topPrs,
            'commonMovements' => CrossFitPrModel::$COMMON_MOVEMENTS,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        $movement = trim($_POST['movement'] ?? '');
        $prValue  = trim($_POST['pr_value'] ?? '');

        if (!$memberId || !$movement || !$prValue) {
            Session::flash('error', 'Uzupełnij wszystkie wymagane pola.');
            $this->redirect('crossfit/prs');
        }

        (new CrossFitPrModel())->setRecord([
            'member_id' => $memberId,
            'movement'  => $movement,
            'pr_value'  => $prValue,
            'unit'      => $_POST['unit'] ?? 'kg',
            'pr_date'   => trim($_POST['pr_date'] ?? '') ?: date('Y-m-d'),
            'notes'     => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'PR zapisany.');
        $this->redirect('crossfit/prs?member_id=' . $memberId);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new CrossFitPrModel())->delete((int)$id);
        Session::flash('success', 'Usunięto rekord.');
        $this->redirect('crossfit/prs');
    }
}
