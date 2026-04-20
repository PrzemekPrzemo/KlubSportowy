<?php

namespace App\Sports\DanceSport\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\DanceSport\Models\DanceCoupleModel;
use App\Sports\DanceSport\Models\DanceResultModel;

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
        $results = (new DanceResultModel())->listForClub();
        $couples = (new DanceCoupleModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('dance_sport/results/index', [
            'title'       => 'Wyniki zawodów — Taniec sportowy',
            'results'     => $results,
            'couples'     => $couples,
            'members'     => $members,
            'disciplines' => DanceCoupleModel::$DISCIPLINES,
            'classes'     => DanceCoupleModel::$CLASSES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $leaderId = (int)($_POST['leader_id'] ?? 0);
        if ($leaderId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('dance_sport/results');
        }

        $discipline = array_key_exists($_POST['discipline'] ?? '', DanceCoupleModel::$DISCIPLINES)
                        ? $_POST['discipline'] : 'standard';
        $class      = array_key_exists($_POST['class_level'] ?? '', DanceCoupleModel::$CLASSES)
                        ? $_POST['class_level'] : 'D';
        $coupleId   = (int)($_POST['couple_id'] ?? 0) ?: null;

        (new DanceResultModel())->insert([
            'leader_id'        => $leaderId,
            'couple_id'        => $coupleId,
            'competition_name' => trim($_POST['competition_name'] ?? ''),
            'competition_date' => trim($_POST['competition_date'] ?? '') ?: date('Y-m-d'),
            'discipline'       => $discipline,
            'class_level'      => $class,
            'age_category'     => trim($_POST['age_category'] ?? '') ?: null,
            'placement'        => !empty($_POST['placement']) ? (int)$_POST['placement'] : null,
            'round_reached'    => trim($_POST['round_reached'] ?? '') ?: null,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('dance_sport/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new DanceResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('dance_sport/results');
    }
}
