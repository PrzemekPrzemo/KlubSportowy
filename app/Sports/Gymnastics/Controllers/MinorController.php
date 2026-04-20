<?php

namespace App\Sports\Gymnastics\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Gymnastics\Models\GymnasticsMinorModel;

class MinorController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $minorModel = new GymnasticsMinorModel();
        $consents   = $minorModel->listWithMembers();
        $members    = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        // Mark which members already have consent records
        $consentMap = [];
        foreach ($consents as $c) {
            $consentMap[$c['member_id']] = $c;
        }

        $this->render('gymnastics/minors/index', [
            'title'      => 'Zgody małoletnich — Gimnastyka',
            'consents'   => $consents,
            'consentMap' => $consentMap,
            'members'    => $members,
        ]);
    }

    public function save(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('gymnastics/minors');
        }

        (new GymnasticsMinorModel())->setConsent([
            'member_id'      => $memberId,
            'guardian_name'  => trim($_POST['guardian_name'] ?? ''),
            'guardian_phone' => trim($_POST['guardian_phone'] ?? '') ?: null,
            'photo_consent'  => !empty($_POST['photo_consent']),
            'media_consent'  => !empty($_POST['media_consent']),
            'signed_date'    => trim($_POST['signed_date'] ?? '') ?: date('Y-m-d'),
            'notes'          => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Zgody zapisane.');
        $this->redirect('gymnastics/minors');
    }
}
