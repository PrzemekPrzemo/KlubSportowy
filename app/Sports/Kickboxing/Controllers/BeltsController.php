<?php

namespace App\Sports\Kickboxing\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Kickboxing\Models\KickboxingBeltModel;

class BeltsController extends BaseController
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
        $this->render('kickboxing/belts/index', [
            'title'   => 'Pasy — Kickboxing',
            'belts'   => (new KickboxingBeltModel())->listForClub(),
            'members' => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'beltMap' => KickboxingBeltModel::$BELTS,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        $color    = $_POST['belt_color'] ?? '';
        if ($memberId <= 0 || !array_key_exists($color, KickboxingBeltModel::$BELTS)) {
            Session::flash('error', 'Nieprawidłowe dane.');
            $this->redirect('kickboxing/belts');
        }
        (new KickboxingBeltModel())->insert([
            'member_id'  => $memberId,
            'belt_color' => $color,
            'dan'        => max(0, (int)($_POST['dan'] ?? 0)),
            'exam_date'  => trim($_POST['exam_date'] ?? '') ?: date('Y-m-d'),
            'examiner'   => trim($_POST['examiner'] ?? '') ?: null,
            'notes'      => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Pas nadany.');
        $this->redirect('kickboxing/belts');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new KickboxingBeltModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('kickboxing/belts');
    }
}
