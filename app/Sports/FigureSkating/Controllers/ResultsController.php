<?php

namespace App\Sports\FigureSkating\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\FigureSkating\Models\FigureSkatingResultModel;

class ResultsController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('figureskating');
    }

    public function index(): void
    {
        $disc = $_GET['discipline'] ?? null;
        $model = new FigureSkatingResultModel();
        $this->render('figureskating/results/index', [
            'title'       => 'Wyniki — Łyżwiarstwo figurowe',
            'results'     => $model->listForClub(null, $disc),
            'members'     => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'disciplines' => FigureSkatingResultModel::$DISCIPLINES,
            'levels'      => FigureSkatingResultModel::$LEVELS,
            'discFilter'  => $disc,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('figureskating/results'); }
        $disc  = array_key_exists($_POST['discipline'] ?? '', FigureSkatingResultModel::$DISCIPLINES) ? $_POST['discipline'] : 'singles_w';
        $level = array_key_exists($_POST['level'] ?? '',      FigureSkatingResultModel::$LEVELS)      ? $_POST['level']      : 'senior';

        // Auto-calc totals
        $spTes = !empty($_POST['sp_tes']) ? (float)$_POST['sp_tes'] : null;
        $spPcs = !empty($_POST['sp_pcs']) ? (float)$_POST['sp_pcs'] : null;
        $fsTes = !empty($_POST['fs_tes']) ? (float)$_POST['fs_tes'] : null;
        $fsPcs = !empty($_POST['fs_pcs']) ? (float)$_POST['fs_pcs'] : null;
        $deductions = !empty($_POST['deductions']) ? (float)$_POST['deductions'] : 0;

        $spTotal = ($spTes !== null && $spPcs !== null) ? round($spTes + $spPcs, 2) : null;
        $fsTotal = ($fsTes !== null && $fsPcs !== null) ? round($fsTes + $fsPcs, 2) : null;
        $total   = null;
        if ($spTotal !== null && $fsTotal !== null) $total = round($spTotal + $fsTotal - $deductions, 2);

        (new FigureSkatingResultModel())->insert([
            'member_id'    => $memberId,
            'discipline'   => $disc,
            'level'        => $level,
            'event_name'   => trim($_POST['event_name'] ?? '') ?: 'Zawody',
            'event_date'   => trim($_POST['event_date'] ?? '') ?: date('Y-m-d'),
            'venue'        => trim($_POST['venue'] ?? '') ?: null,
            'category'     => trim($_POST['category'] ?? '') ?: null,
            'partner_name' => trim($_POST['partner_name'] ?? '') ?: null,
            'sp_tes'       => $spTes,
            'sp_pcs'       => $spPcs,
            'sp_total'     => $spTotal,
            'fs_tes'       => $fsTes,
            'fs_pcs'       => $fsPcs,
            'fs_total'     => $fsTotal,
            'total_score'  => $total,
            'deductions'   => $deductions,
            'place'        => !empty($_POST['place']) ? (int)$_POST['place'] : null,
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('figureskating/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new FigureSkatingResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('figureskating/results');
    }
}
