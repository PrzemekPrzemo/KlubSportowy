<?php

namespace App\Sports\Judo\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Judo\Models\JudoResultModel;

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
        $results = (new JudoResultModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('judo/results/index', [
            'title'        => 'Wyniki zawodów — Judo',
            'results'      => $results,
            'members'      => $members,
            'weightClasses' => JudoResultModel::$WEIGHT_CLASSES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('judo/results');
        }

        $placement = !empty($_POST['placement']) ? (int)$_POST['placement'] : null;
        $category  = in_array($_POST['category'] ?? '', ['walka','kata','para_judo'], true) ? $_POST['category'] : 'walka';
        $wc        = in_array($_POST['weight_class'] ?? '', JudoResultModel::$WEIGHT_CLASSES, true)
                        ? $_POST['weight_class'] : null;

        (new JudoResultModel())->insert([
            'member_id'        => $memberId,
            'competition_name' => trim($_POST['competition_name'] ?? ''),
            'competition_date' => trim($_POST['competition_date'] ?? '') ?: date('Y-m-d'),
            'weight_class'     => $wc,
            'age_category'     => trim($_POST['age_category'] ?? '') ?: null,
            'placement'        => $placement,
            'category'         => $category,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('judo/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new JudoResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('judo/results');
    }
}
