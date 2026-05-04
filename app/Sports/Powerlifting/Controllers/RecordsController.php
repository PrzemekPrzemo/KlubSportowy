<?php

namespace App\Sports\Powerlifting\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Powerlifting\Models\PowerliftingRecordModel;
use App\Sports\Powerlifting\Models\PowerliftingResultModel;

class RecordsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $model   = new PowerliftingRecordModel();
        $records = $model->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $this->render('powerlifting/records/index', [
            'title'              => 'Rekordy klubu — Trójbój siłowy',
            'records'            => $records,
            'members'            => $members,
            'liftTypes'          => PowerliftingRecordModel::$LIFT_TYPES,
            'weightClassesMen'   => PowerliftingResultModel::$WEIGHT_CLASSES_MEN,
            'weightClassesWomen' => PowerliftingResultModel::$WEIGHT_CLASSES_WOMEN,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('powerlifting/records');
        }

        $liftType = $_POST['lift_type'] ?? '';
        if (!array_key_exists($liftType, PowerliftingRecordModel::$LIFT_TYPES)) {
            Session::flash('error', 'Nieprawidłowy typ podnoszenia.');
            $this->redirect('powerlifting/records');
        }

        $weightKg = (float)($_POST['weight_kg'] ?? 0);
        if ($weightKg <= 0) {
            Session::flash('error', 'Podaj wagę.');
            $this->redirect('powerlifting/records');
        }

        (new PowerliftingRecordModel())->insert([
            'member_id'   => $memberId,
            'lift_type'   => $liftType,
            'weight_class' => trim($_POST['weight_class'] ?? '') ?: null,
            'weight_kg'   => $weightKg,
            'set_date'    => trim($_POST['set_date'] ?? '') ?: date('Y-m-d'),
            'competition' => trim($_POST['competition'] ?? '') ?: null,
            'notes'       => trim($_POST['notes'] ?? '') ?: null,
        ]);

        Session::flash('success', 'Rekord dodany.');
        $this->redirect('powerlifting/records');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new PowerliftingRecordModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('powerlifting/records');
    }
}
