<?php

namespace App\Sports\Taekwondo\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Helpers\BeltCertificatePdf;
use App\Sports\Taekwondo\Models\TaekwondoBeltModel;

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
        $belts   = (new TaekwondoBeltModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('taekwondo/belts/index', [
            'title'   => 'Pasy — Taekwondo',
            'belts'   => $belts,
            'members' => $members,
            'beltMap' => TaekwondoBeltModel::$BELTS,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId  = (int)($_POST['member_id'] ?? 0);
        $beltLevel = $_POST['belt_level'] ?? '';

        if ($memberId <= 0 || !array_key_exists($beltLevel, TaekwondoBeltModel::$BELTS)) {
            Session::flash('error', 'Nieprawidłowe dane.');
            $this->redirect('taekwondo/belts');
        }

        (new TaekwondoBeltModel())->insert([
            'member_id'    => $memberId,
            'belt_level'   => $beltLevel,
            'granted_date' => trim($_POST['granted_date'] ?? '') ?: date('Y-m-d'),
            'examiner'     => trim($_POST['examiner'] ?? '') ?: null,
            'location'     => trim($_POST['location'] ?? '') ?: null,
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Pas dodany.');
        $this->redirect('taekwondo/belts');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new TaekwondoBeltModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('taekwondo/belts');
    }

    public function printCertificate(string $id): void
    {
        $belt = (new TaekwondoBeltModel())->findById((int)$id);
        if (!$belt) { $this->redirect('taekwondo/belts'); }
        $member = (new \App\Models\MemberModel())->withoutScope()->findById((int)$belt['member_id']);
        BeltCertificatePdf::generate($belt, $member, TaekwondoBeltModel::$BELTS, 'Taekwondo', 'PZTkd');
    }
}
