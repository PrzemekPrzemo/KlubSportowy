<?php

namespace App\Sports\Archery\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Archery\Models\ArcheryBowModel;
use App\Sports\Archery\Models\ArcheryScoreModel;

class ScoresController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $scores  = (new ArcheryScoreModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('archery/scores/index', [
            'title'       => 'Wyniki strzelań — Łucznictwo',
            'scores'      => $scores,
            'members'     => $members,
            'disciplines' => ArcheryScoreModel::$DISCIPLINES,
            'bowTypes'    => ArcheryBowModel::$BOW_TYPES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('archery/scores');
        }

        $discipline = array_key_exists($_POST['discipline'] ?? '', ArcheryScoreModel::$DISCIPLINES)
                        ? $_POST['discipline'] : '18m';
        $bowType    = array_key_exists($_POST['bow_type'] ?? '', ArcheryBowModel::$BOW_TYPES)
                        ? $_POST['bow_type'] : null;

        (new ArcheryScoreModel())->insert([
            'member_id'        => $memberId,
            'competition_name' => trim($_POST['competition_name'] ?? '') ?: null,
            'score_date'       => trim($_POST['score_date'] ?? '') ?: date('Y-m-d'),
            'discipline'       => $discipline,
            'rounds'           => max(1, (int)($_POST['rounds'] ?? 1)),
            'total_score'      => (int)($_POST['total_score'] ?? 0),
            'tens'             => !empty($_POST['tens']) ? (int)$_POST['tens'] : null,
            'xs'               => !empty($_POST['xs']) ? (int)$_POST['xs'] : null,
            'bow_type'         => $bowType,
            'age_category'     => trim($_POST['age_category'] ?? '') ?: null,
            'placement'        => !empty($_POST['placement']) ? (int)$_POST['placement'] : null,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('archery/scores');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new ArcheryScoreModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('archery/scores');
    }
}
