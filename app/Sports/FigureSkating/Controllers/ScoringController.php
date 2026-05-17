<?php

namespace App\Sports\FigureSkating\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Support\Models\JudgedPerformanceModel;

/**
 * ISU TES+PCS scoring for figure skating.
 * Uses shared sport_judged_performances (sport_key='figureskating').
 */
class ScoringController extends BaseController
{
    use RequiresActiveSport;

    public const SPORT_KEY = 'figureskating';

    public static array $ROUTINES = [
        'short_program' => 'Program krótki (SP)',
        'free_skate'    => 'Jazda dowolna (FS)',
        'exhibition'    => 'Pokaz (exhibition)',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive(self::SPORT_KEY);
    }

    public function index(): void
    {
        $model = new JudgedPerformanceModel();
        $this->render('figureskating/scoring/index', [
            'title'        => 'ISU TES+PCS — Łyżwiarstwo figurowe',
            'performances' => $model->listForSport(self::SPORT_KEY),
            'members'      => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'routines'     => self::$ROUTINES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('figureskating/scoring');
        }
        $routine = array_key_exists($_POST['routine_type'] ?? '', self::$ROUTINES)
            ? $_POST['routine_type'] : 'short_program';

        $tes = $_POST['technical_score'] ?? '';
        $pcs = $_POST['presentation_score'] ?? '';
        $deductions = (float)($_POST['deductions'] ?? 0);

        $data = [
            'sport_key'          => self::SPORT_KEY,
            'member_id'          => $memberId,
            'routine_type'       => $routine,
            'apparatus'          => null,
            'technical_score'    => $tes !== '' ? (float)$tes : null,
            'presentation_score' => $pcs !== '' ? (float)$pcs : null,
            'difficulty_score'   => null,
            'execution_score'    => null,
            'deductions'         => $deductions,
            'rank_position'      => !empty($_POST['rank_position']) ? (int)$_POST['rank_position'] : null,
            'notes'              => trim((string)($_POST['notes'] ?? '')) ?: null,
            'performed_at'       => trim((string)($_POST['performed_at'] ?? '')) ?: date('Y-m-d H:i:s'),
        ];
        $data['total_score'] = JudgedPerformanceModel::calcTotal($data);
        (new JudgedPerformanceModel())->insert($data);

        Session::flash('success', 'Występ ISU zapisany.');
        $this->redirect('figureskating/scoring');
    }

    public function show(string $id): void
    {
        $model = new JudgedPerformanceModel();
        $perf  = $model->findOwnedById((int)$id, self::SPORT_KEY);
        if (!$perf) {
            Session::flash('error', 'Występ nie znaleziony.');
            $this->redirect('figureskating/scoring');
        }
        $this->render('figureskating/scoring/show', [
            'title'       => 'Szczegóły — ISU scoring',
            'performance' => $perf,
            'judges'      => $model->judgeScoresFor((int)$id),
        ]);
    }

    public function addJudge(string $id): void
    {
        Csrf::verify();
        $model = new JudgedPerformanceModel();
        $perf  = $model->findOwnedById((int)$id, self::SPORT_KEY);
        if (!$perf) { Session::flash('error', 'Występ nie znaleziony.'); $this->redirect('figureskating/scoring'); }

        $name = trim((string)($_POST['judge_name'] ?? ''));
        if ($name === '') { Session::flash('error', 'Podaj imię sędziego.'); $this->redirect('figureskating/scoring/' . (int)$id); }

        $model->addJudgeScore((int)$id, [
            'judge_name'          => $name,
            'judge_certification' => trim((string)($_POST['judge_certification'] ?? '')) ?: null,
            'score_category'      => trim((string)($_POST['score_category'] ?? '')) ?: null,
            'score_value'         => (float)($_POST['score_value'] ?? 0),
            'notes'               => trim((string)($_POST['notes'] ?? '')) ?: null,
        ]);
        Session::flash('success', 'Ocena sędziego dodana.');
        $this->redirect('figureskating/scoring/' . (int)$id);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new JudgedPerformanceModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('figureskating/scoring');
    }
}
