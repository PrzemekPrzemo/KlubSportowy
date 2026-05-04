<?php

namespace App\Sports\Boxing\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Boxing\Models\BoxingResultModel;

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
        $model = new BoxingResultModel();
        $this->render('boxing/results/index', [
            'title'         => 'Walki — Boks (rekordy W-L-D)',
            'results'       => $model->listForClub(),
            'clubRecord'    => $model->clubRecord(),
            'members'       => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'categories'    => BoxingResultModel::$CATEGORIES,
            'weightClasses' => BoxingResultModel::$WEIGHT_CLASSES,
            'resultTypes'   => BoxingResultModel::$RESULTS,
            'methods'       => BoxingResultModel::$METHODS,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('boxing/results');
        }

        $category    = array_key_exists($_POST['category']     ?? '', BoxingResultModel::$CATEGORIES)     ? $_POST['category']     : null;
        $weightClass = array_key_exists($_POST['weight_class'] ?? '', BoxingResultModel::$WEIGHT_CLASSES) ? $_POST['weight_class'] : null;
        $result      = array_key_exists($_POST['result']       ?? '', BoxingResultModel::$RESULTS)       ? $_POST['result']       : null;
        $method      = array_key_exists($_POST['method']       ?? '', BoxingResultModel::$METHODS)       ? $_POST['method']       : null;

        (new BoxingResultModel())->insert([
            'member_id'        => $memberId,
            'competition_name' => trim($_POST['competition_name'] ?? '') ?: 'Walka',
            'competition_date' => trim($_POST['competition_date'] ?? '') ?: date('Y-m-d'),
            'age_category'     => trim($_POST['age_category'] ?? '') ?: null,
            'weight_class'     => $weightClass,
            'category'         => $category,
            'placement'        => !empty($_POST['placement']) ? (int)$_POST['placement'] : null,
            'opponent_name'    => trim($_POST['opponent_name'] ?? '') ?: null,
            'result'           => $result,
            'method'           => $method,
            'rounds_total'     => !empty($_POST['rounds_total'])  ? (int)$_POST['rounds_total']  : null,
            'rounds_fought'    => !empty($_POST['rounds_fought']) ? (int)$_POST['rounds_fought'] : null,
            'amateur'          => isset($_POST['amateur']) ? 1 : 0,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Walka dodana.');
        $this->redirect('boxing/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new BoxingResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('boxing/results');
    }
}
