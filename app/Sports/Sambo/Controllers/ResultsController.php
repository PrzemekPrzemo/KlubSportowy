<?php

namespace App\Sports\Sambo\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Sambo\Models\SamboResultModel;

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
        $results = (new SamboResultModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('sambo/results/index', [
            'title'        => 'Wyniki zawodów — Sambo',
            'results'      => $results,
            'members'      => $members,
            'styles'       => SamboResultModel::$STYLES,
            'weightClasses' => SamboResultModel::$WEIGHT_CLASSES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('sambo/results');
        }

        $style     = array_key_exists($_POST['style'] ?? '', SamboResultModel::$STYLES)
                        ? $_POST['style'] : 'sport_sambo';
        $placement = !empty($_POST['placement']) ? (int)$_POST['placement'] : null;
        $wc        = in_array($_POST['weight_class'] ?? '', SamboResultModel::$WEIGHT_CLASSES, true)
                        ? $_POST['weight_class'] : null;

        (new SamboResultModel())->insert([
            'member_id'        => $memberId,
            'competition_name' => trim($_POST['competition_name'] ?? ''),
            'competition_date' => trim($_POST['competition_date'] ?? '') ?: date('Y-m-d'),
            'style'            => $style,
            'weight_class'     => $wc,
            'age_category'     => trim($_POST['age_category'] ?? '') ?: null,
            'placement'        => $placement,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('sambo/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new SamboResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('sambo/results');
    }
}
