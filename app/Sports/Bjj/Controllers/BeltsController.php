<?php

namespace App\Sports\Bjj\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Bjj\Models\BjjBeltModel;

class BeltsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $this->render('bjj/belts/index', [
            'title'    => 'Pasy BJJ',
            'belts'    => (new BjjBeltModel())->listForClub(),
            'members'  => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'beltMap'  => BjjBeltModel::$BELT_LEVELS,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId  = (int)($_POST['member_id'] ?? 0);
        $beltColor = $_POST['belt_color'] ?? '';

        if ($memberId <= 0 || !array_key_exists($beltColor, BjjBeltModel::$BELT_LEVELS)) {
            Session::flash('error', 'Nieprawidłowe dane.');
            $this->redirect('bjj/belts');
        }

        $stripes = min(4, max(0, (int)($_POST['stripes'] ?? 0)));
        $gi      = in_array($_POST['gi'] ?? 'gi', ['gi','nogi','both'], true) ? $_POST['gi'] : 'gi';

        (new BjjBeltModel())->insert([
            'member_id'  => $memberId,
            'belt_color' => $beltColor,
            'stripes'    => $stripes,
            'gi'         => $gi,
            'exam_date'  => trim($_POST['exam_date'] ?? '') ?: date('Y-m-d'),
            'examiner'   => trim($_POST['examiner'] ?? '') ?: null,
            'notes'      => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Pas BJJ nadany.');
        $this->redirect('bjj/belts');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new BjjBeltModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('bjj/belts');
    }
}
