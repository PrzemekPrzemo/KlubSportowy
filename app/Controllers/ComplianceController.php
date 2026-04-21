<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\AntiDopingModel;
use App\Models\MinorConsentModel;
use App\Models\MemberModel;

class ComplianceController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSensitiveAccess();
    }

    public function index(): void
    {
        \App\Models\SensitiveAccessLogModel::log('anti_doping', 'list');

        $ad    = new AntiDopingModel();
        $minor = new MinorConsentModel();

        $this->render('admin/compliance/index', [
            'title'            => 'Zgodność — WADA anti-doping + zgody małoletnich',
            'declarations'     => $ad->listForClub(),
            'expiringSoon'     => $ad->expiringSoon(30),
            'requiringWada'    => $ad->membersRequiringDeclaration(),
            'minorsMissing'    => $minor->minorsWithoutConsent(),
            'declarationTypes' => AntiDopingModel::$DECLARATION_TYPES,
            'members'          => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
        ]);
    }

    public function storeDeclaration(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('admin/compliance');
        }

        $type = array_key_exists($_POST['declaration_type'] ?? '', AntiDopingModel::$DECLARATION_TYPES)
            ? $_POST['declaration_type'] : 'WADA';

        (new AntiDopingModel())->insert([
            'member_id'        => $memberId,
            'declaration_type' => $type,
            'signed_date'      => trim($_POST['signed_date'] ?? '') ?: date('Y-m-d'),
            'valid_until'      => trim($_POST['valid_until'] ?? '') ?: date('Y-m-d', strtotime('+1 year')),
            'witness'          => trim($_POST['witness'] ?? '') ?: null,
            'signed_ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Deklaracja anti-dopingowa zapisana.');
        $this->redirect('admin/compliance');
    }

    public function deleteDeclaration(string $id): void
    {
        Csrf::verify();
        (new AntiDopingModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('admin/compliance');
    }

    public function storeMinorConsent(string $memberId): void
    {
        Csrf::verify();
        $mid = (int)$memberId;
        (new MinorConsentModel())->upsert($mid, [
            'guardian_name'      => trim($_POST['guardian_name'] ?? ''),
            'guardian_id_number' => trim($_POST['guardian_id_number'] ?? '') ?: null,
            'guardian_phone'     => trim($_POST['guardian_phone'] ?? '') ?: null,
            'guardian_email'     => trim($_POST['guardian_email'] ?? '') ?: null,
            'photo_consent'      => isset($_POST['photo_consent']) ? 1 : 0,
            'media_consent'      => isset($_POST['media_consent']) ? 1 : 0,
            'travel_consent'     => isset($_POST['travel_consent']) ? 1 : 0,
            'medical_decisions'  => isset($_POST['medical_decisions']) ? 1 : 0,
            'signed_date'        => trim($_POST['signed_date'] ?? '') ?: date('Y-m-d'),
            'valid_until'        => trim($_POST['valid_until'] ?? '') ?: null,
            'notes'              => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Zgoda opiekuna zapisana.');
        $this->redirect('admin/compliance');
    }
}
