<?php

namespace App\Sports\Equestrian\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Support\Models\EquestrianHorseModel;
use App\Sports\Support\Models\EquestrianResultModel;

/**
 * FEI Results (dressage/jumping/eventing) + ranking.
 */
class FeiResultsController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('equestrian');
    }

    public function index(): void
    {
        $discipline = $_GET['discipline'] ?? null;
        if ($discipline !== null && !array_key_exists($discipline, EquestrianResultModel::$DISCIPLINES)) {
            $discipline = null;
        }
        $model = new EquestrianResultModel();

        $ranking = [];
        if ($discipline !== null) {
            $ranking = $model->feiRanking($discipline);
        }

        $this->render('equestrian/fei_results/index', [
            'title'       => 'Wyniki FEI (z rankingiem) — Jeździectwo',
            'results'     => $model->listForClub($discipline),
            'horses'      => (new EquestrianHorseModel())->listForClub(),
            'members'     => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'disciplines' => EquestrianResultModel::$DISCIPLINES,
            'filterDiscipline' => $discipline,
            'ranking'     => $ranking,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('equestrian/fei-results');
        }
        $discipline = array_key_exists($_POST['discipline'] ?? '', EquestrianResultModel::$DISCIPLINES)
            ? $_POST['discipline'] : 'jumping';

        (new EquestrianResultModel())->insert([
            'member_id'      => $memberId,
            'horse_id'       => !empty($_POST['horse_id']) ? (int)$_POST['horse_id'] : null,
            'discipline'     => $discipline,
            'event_name'     => trim((string)($_POST['event_name'] ?? '')) ?: null,
            'event_date'     => trim((string)($_POST['event_date'] ?? '')) ?: date('Y-m-d'),
            'score'          => isset($_POST['score']) && $_POST['score'] !== '' ? (float)$_POST['score'] : null,
            'faults_jumping' => isset($_POST['faults_jumping']) && $_POST['faults_jumping'] !== '' ? (int)$_POST['faults_jumping'] : null,
            'time_seconds'   => isset($_POST['time_seconds']) && $_POST['time_seconds'] !== '' ? (float)$_POST['time_seconds'] : null,
            'rank_position'  => isset($_POST['rank_position']) && $_POST['rank_position'] !== '' ? (int)$_POST['rank_position'] : null,
            'notes'          => trim((string)($_POST['notes'] ?? '')) ?: null,
        ]);
        Session::flash('success', 'Wynik FEI dodany.');
        $this->redirect('equestrian/fei-results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new EquestrianResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('equestrian/fei-results');
    }
}
