<?php

namespace App\Sports\Karate\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Helpers\BeltCertificatePdf;
use App\Sports\Karate\Models\KarateBeltModel;

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
        $belts   = (new KarateBeltModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('karate/belts/index', [
            'title'   => 'Pasy — Karate',
            'belts'   => $belts,
            'members' => $members,
            'beltMap' => KarateBeltModel::$BELTS,
            'styles'  => KarateBeltModel::$STYLES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId  = (int)($_POST['member_id'] ?? 0);
        $beltLevel = $_POST['belt_level'] ?? '';
        $style     = $_POST['style'] ?? 'shotokan';

        if ($memberId <= 0 || !array_key_exists($beltLevel, KarateBeltModel::$BELTS)) {
            Session::flash('error', 'Nieprawidłowe dane.');
            $this->redirect('karate/belts');
        }

        if (!array_key_exists($style, KarateBeltModel::$STYLES)) {
            $style = 'shotokan';
        }

        (new KarateBeltModel())->insert([
            'member_id'    => $memberId,
            'belt_level'   => $beltLevel,
            'granted_date' => trim($_POST['granted_date'] ?? '') ?: date('Y-m-d'),
            'examiner'     => trim($_POST['examiner'] ?? '') ?: null,
            'location'     => trim($_POST['location'] ?? '') ?: null,
            'style'        => $style,
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Pas dodany.');
        $this->redirect('karate/belts');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new KarateBeltModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('karate/belts');
    }

    public function printCertificate(string $id): void
    {
        $belt = (new KarateBeltModel())->findById((int)$id);
        if (!$belt) { $this->redirect('karate/belts'); }
        $member = (new \App\Models\MemberModel())->withoutScope()->findById((int)$belt['member_id']);
        BeltCertificatePdf::generate($belt, $member, KarateBeltModel::$BELTS, 'Karate', 'PZKK');
    }
}
