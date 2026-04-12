<?php

namespace App\Sports\Athletics\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\DisciplineModel;
use App\Models\MemberModel;
use App\Models\SportModel;
use App\Sports\Athletics\Models\AthleticsRecordModel;

class RecordsController extends BaseController
{
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }

    public function index(): void
    {
        $disc = isset($_GET['discipline']) ? (int)$_GET['discipline'] : null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = (new AthleticsRecordModel())->listForClub($disc, $page, 25);
        $pbs        = (new AthleticsRecordModel())->personalBests($disc, 10);
        $clubRecs   = (new AthleticsRecordModel())->clubRecords();
        $sport = (new SportModel())->findByKey('athletics');
        $disciplines = $sport ? (new DisciplineModel())->listForSport((int)$sport['id'], $this->currentClub()) : [];
        $this->render('athletics/records/index', [
            'title' => 'Rekordy lekkoatletyczne', 'pagination' => $pagination,
            'pbs' => $pbs, 'clubRecords' => $clubRecs,
            'disciplines' => $disciplines, 'discFilter' => $disc,
        ]);
    }

    public function create(): void
    {
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $sport = (new SportModel())->findByKey('athletics');
        $disciplines = $sport ? (new DisciplineModel())->listForSport((int)$sport['id'], $this->currentClub()) : [];
        $this->render('athletics/records/form', [
            'title' => 'Nowy wynik', 'record' => null,
            'members' => $members, 'disciplines' => $disciplines,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = [
            'member_id'        => (int)($_POST['member_id'] ?? 0),
            'discipline_id'    => !empty($_POST['discipline_id']) ? (int)$_POST['discipline_id'] : null,
            'result_value'     => (float)($_POST['result_value'] ?? 0),
            'result_unit'      => in_array($_POST['result_unit'] ?? '', ['s','min','m','cm','kg'], true) ? $_POST['result_unit'] : 's',
            'record_date'      => trim($_POST['record_date'] ?? date('Y-m-d')),
            'competition_name' => trim($_POST['competition_name'] ?? '') ?: null,
            'location'         => trim($_POST['location'] ?? '') ?: null,
            'is_personal_best' => isset($_POST['is_personal_best']) ? 1 : 0,
            'is_club_record'   => isset($_POST['is_club_record']) ? 1 : 0,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
            'created_by'       => Auth::id(),
        ];
        if ($data['member_id'] <= 0 || $data['result_value'] <= 0) {
            Session::flash('error', 'Zawodnik i wynik wymagane.');
            $this->redirect('athletics/records/create');
        }
        (new AthleticsRecordModel())->insert($data);
        Session::flash('success', 'Wynik zapisany.');
        $this->redirect('athletics/records');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new AthleticsRecordModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('athletics/records');
    }
}
