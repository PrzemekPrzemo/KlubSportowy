<?php

namespace App\Sports\Aikido\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Aikido\Models\AikidoBeltModel;

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
        $belts   = (new AikidoBeltModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('aikido/belts/index', [
            'title'   => 'Pasy — Aikido',
            'belts'   => $belts,
            'members' => $members,
            'beltMap' => AikidoBeltModel::$BELTS,
            'styles'  => AikidoBeltModel::$STYLES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId    = (int)($_POST['member_id'] ?? 0);
        $beltLevel   = $_POST['belt_level'] ?? '';
        $grantedDate = trim($_POST['granted_date'] ?? '') ?: date('Y-m-d');

        if ($memberId <= 0 || !array_key_exists($beltLevel, AikidoBeltModel::$BELTS)) {
            Session::flash('error', 'Nieprawidłowe dane.');
            $this->redirect('aikido/belts');
        }

        $style = $_POST['style'] ?? 'aikikai';
        if (!array_key_exists($style, AikidoBeltModel::$STYLES)) {
            $style = 'aikikai';
        }

        (new AikidoBeltModel())->insert([
            'member_id'    => $memberId,
            'belt_level'   => $beltLevel,
            'granted_date' => $grantedDate,
            'examiner'     => trim($_POST['examiner'] ?? '') ?: null,
            'location'     => trim($_POST['location'] ?? '') ?: null,
            'style'        => $style,
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Pas nadany.');
        $this->redirect('aikido/belts');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new AikidoBeltModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('aikido/belts');
    }
}
