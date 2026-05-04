<?php

namespace App\Sports\Aikido\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Aikido\Models\AikidoResultModel;

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
        $results = (new AikidoResultModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('aikido/results/index', [
            'title'      => 'Wyniki/Pokazy — Aikido',
            'results'    => $results,
            'members'    => $members,
            'categories' => AikidoResultModel::$CATEGORIES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('aikido/results');
        }

        $category  = array_key_exists($_POST['category'] ?? '', AikidoResultModel::$CATEGORIES)
                        ? $_POST['category'] : 'embu';
        $placement = !empty($_POST['placement']) ? (int)$_POST['placement'] : null;

        (new AikidoResultModel())->insert([
            'member_id'        => $memberId,
            'competition_name' => trim($_POST['competition_name'] ?? ''),
            'competition_date' => trim($_POST['competition_date'] ?? '') ?: date('Y-m-d'),
            'category'         => $category,
            'age_category'     => trim($_POST['age_category'] ?? '') ?: null,
            'placement'        => $placement,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('aikido/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new AikidoResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('aikido/results');
    }
}
