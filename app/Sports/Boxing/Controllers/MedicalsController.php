<?php

namespace App\Sports\Boxing\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Boxing\Models\BoxingMedicalModel;

class MedicalsController extends BaseController
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
        $model = new BoxingMedicalModel();
        $this->render('boxing/medicals/index', [
            'title'           => 'Badania lekarskie — Boks',
            'medicals'        => $model->listForClub(),
            'members'         => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'clearanceTypes'  => BoxingMedicalModel::$CLEARANCE_TYPES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('boxing/medicals');
        }

        $clearance = array_key_exists($_POST['clearance_type'] ?? '', BoxingMedicalModel::$CLEARANCE_TYPES)
            ? $_POST['clearance_type'] : 'amateur';

        (new BoxingMedicalModel())->insert([
            'member_id'      => $memberId,
            'exam_date'      => trim($_POST['exam_date'] ?? '') ?: date('Y-m-d'),
            'valid_until'    => trim($_POST['valid_until'] ?? '') ?: date('Y-m-d', strtotime('+1 year')),
            'clearance_type' => $clearance,
            'doctor_name'    => trim($_POST['doctor_name'] ?? '') ?: null,
            'notes'          => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Badanie lekarskie dodane.');
        $this->redirect('boxing/medicals');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new BoxingMedicalModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('boxing/medicals');
    }
}
