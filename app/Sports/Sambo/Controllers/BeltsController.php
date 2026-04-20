<?php

namespace App\Sports\Sambo\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Sambo\Models\SamboBeltModel;

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
        $belts   = (new SamboBeltModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('sambo/belts/index', [
            'title'   => 'Pasy — Sambo',
            'belts'   => $belts,
            'members' => $members,
            'beltMap' => SamboBeltModel::$BELTS,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId    = (int)($_POST['member_id'] ?? 0);
        $beltLevel   = $_POST['belt_level'] ?? '';
        $grantedDate = trim($_POST['granted_date'] ?? '') ?: date('Y-m-d');

        if ($memberId <= 0 || !array_key_exists($beltLevel, SamboBeltModel::$BELTS)) {
            Session::flash('error', 'Nieprawidłowe dane.');
            $this->redirect('sambo/belts');
        }

        (new SamboBeltModel())->insert([
            'member_id'    => $memberId,
            'belt_level'   => $beltLevel,
            'granted_date' => $grantedDate,
            'examiner'     => trim($_POST['examiner'] ?? '') ?: null,
            'location'     => trim($_POST['location'] ?? '') ?: null,
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Pas nadany.');
        $this->redirect('sambo/belts');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new SamboBeltModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('sambo/belts');
    }
}
