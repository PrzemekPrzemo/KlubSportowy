<?php

namespace App\Sports\Weightlifting\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Weightlifting\Models\WeightliftingRecordModel;
use App\Sports\Weightlifting\Models\WeightliftingResultModel;

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
        $type = $_GET['type'] ?? null;
        $model = new WeightliftingRecordModel();

        $this->render('weightlifting/records/index', [
            'title'       => 'Rekordy — Podnoszenie ciężarów',
            'records'     => $model->listForClub($type),
            'clubRecords' => $model->clubRecords(),
            'members'     => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'recordTypes' => WeightliftingRecordModel::$RECORD_TYPES,
            'lifts'       => WeightliftingRecordModel::$LIFTS,
            'weightClassesM' => WeightliftingResultModel::$WEIGHT_CLASSES_MEN,
            'weightClassesW' => WeightliftingResultModel::$WEIGHT_CLASSES_WOMEN,
            'typeFilter'  => $type,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('weightlifting/records');
        }

        $recordType  = array_key_exists($_POST['record_type'] ?? '', WeightliftingRecordModel::$RECORD_TYPES)
            ? $_POST['record_type'] : 'personal';
        $lift        = array_key_exists($_POST['lift'] ?? '', WeightliftingRecordModel::$LIFTS)
            ? $_POST['lift'] : 'dwubój';

        (new WeightliftingRecordModel())->insert([
            'member_id'    => $memberId,
            'record_type'  => $recordType,
            'lift'         => $lift,
            'weight_class' => trim($_POST['weight_class'] ?? ''),
            'value_kg'     => (float)($_POST['value_kg'] ?? 0),
            'set_at'       => trim($_POST['set_at'] ?? '') ?: date('Y-m-d'),
            'event_name'   => trim($_POST['event_name'] ?? '') ?: null,
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Rekord zapisany.');
        $this->redirect('weightlifting/records');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new WeightliftingRecordModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('weightlifting/records');
    }
}
