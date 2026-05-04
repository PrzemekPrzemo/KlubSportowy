<?php

namespace App\Sports\Athletics\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\AgeCategoryModel;
use App\Models\MemberModel;
use App\Sports\Athletics\Models\AthleticsResultModel;

class ResultsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $model      = new AthleticsResultModel();
        $discipline = $_GET['discipline'] ?? null;
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $pagination = $model->listForClub($discipline, $page, 30);
        $disciplines = $model->disciplines();
        $ageCategories = (new AgeCategoryModel())->listAvailable(null, $this->currentClub());
        $members    = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $this->render('athletics/results/index', [
            'title'         => 'Wyniki zawodów — Lekka atletyka',
            'pagination'    => $pagination,
            'disciplines'   => $disciplines,
            'commonDisc'    => AthleticsResultModel::$COMMON_DISCIPLINES,
            'units'         => AthleticsResultModel::$UNITS,
            'ageCategories' => $ageCategories,
            'members'       => $members,
            'discFilter'    => $discipline,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('athletics/results');
        }

        $discipline = trim($_POST['discipline_name'] ?? '');
        if ($discipline === '') {
            Session::flash('error', 'Podaj dyscyplinę.');
            $this->redirect('athletics/results');
        }

        $unit = array_key_exists($_POST['result_unit'] ?? '', AthleticsResultModel::$UNITS)
                    ? $_POST['result_unit'] : 's';
        $wind = trim($_POST['wind_ms'] ?? '');

        (new AthleticsResultModel())->insert([
            'member_id'        => $memberId,
            'discipline_name'  => $discipline,
            'result_value'     => (float)($_POST['result_value'] ?? 0),
            'result_unit'      => $unit,
            'wind_ms'          => $wind !== '' ? (float)$wind : null,
            'competition_name' => trim($_POST['competition_name'] ?? ''),
            'competition_date' => trim($_POST['competition_date'] ?? '') ?: date('Y-m-d'),
            'placement'        => !empty($_POST['placement']) ? (int)$_POST['placement'] : null,
            'age_category'     => trim($_POST['age_category'] ?? '') ?: null,
            'location'         => trim($_POST['location'] ?? '') ?: null,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik zapisany.');
        $this->redirect('athletics/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new AthleticsResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('athletics/results');
    }
}
