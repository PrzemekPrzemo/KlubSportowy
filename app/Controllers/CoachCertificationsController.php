<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\CoachCertificationModel;
use App\Models\MemberModel;
use App\Models\SportModel;

class CoachCertificationsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $sport = $_GET['sport'] ?? null;
        $level = $_GET['level'] ?? null;
        $model = new CoachCertificationModel();

        $this->render('admin/certifications/index', [
            'title'        => 'Uprawnienia trenerskie i sędziowskie',
            'certs'        => $model->listForClub($sport, $level),
            'expiring'     => $model->expiringSoon(60),
            'sports'       => (new SportModel())->listForClub($this->currentClub()),
            'members'      => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'levels'       => CoachCertificationModel::$LEVELS,
            'sportFilter'  => $sport,
            'levelFilter'  => $level,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $certName = trim($_POST['cert_name'] ?? '');
        $sportKey = trim($_POST['sport_key'] ?? '');
        if ($certName === '' || $sportKey === '') {
            Session::flash('error', 'Nazwa uprawnienia i sport są wymagane.');
            $this->redirect('certifications');
        }
        $level = array_key_exists($_POST['cert_level'] ?? '', CoachCertificationModel::$LEVELS)
            ? $_POST['cert_level'] : 'instruktor_sportu';

        $memberId = !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null;
        $userId   = !empty($_POST['user_id'])   ? (int)$_POST['user_id']   : null;
        if (!$memberId && !$userId) {
            Session::flash('error', 'Wybierz zawodnika lub pracownika.');
            $this->redirect('certifications');
        }

        (new CoachCertificationModel())->insert([
            'member_id'    => $memberId,
            'user_id'      => $userId,
            'sport_key'    => $sportKey,
            'cert_name'    => $certName,
            'cert_level'   => $level,
            'cert_number'  => trim($_POST['cert_number'] ?? '') ?: null,
            'issuing_body' => trim($_POST['issuing_body'] ?? '') ?: null,
            'issued_at'    => trim($_POST['issued_at'] ?? '') ?: date('Y-m-d'),
            'valid_until'  => trim($_POST['valid_until'] ?? '') ?: null,
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Uprawnienie zapisane.');
        $this->redirect('certifications');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new CoachCertificationModel())->delete((int)$id);
        Session::flash('success', 'Usunięto uprawnienie.');
        $this->redirect('certifications');
    }
}
