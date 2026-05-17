<?php

namespace App\Sports\Gymnastics\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Support\Models\JudgedPerformanceModel;

/**
 * D-score + E-score per apparatus (FIG-style).
 * Uses shared sport_judged_performances (sport_key='gymnastics').
 */
class ScoringController extends BaseController
{
    use RequiresActiveSport;

    public const SPORT_KEY = 'gymnastics';

    public static array $APPARATUS_W = [
        'vault'   => 'Skok',
        'bars'    => 'Poręcze asymetryczne',
        'beam'    => 'Równoważnia',
        'floor'   => 'Ćwiczenia wolne',
    ];

    public static array $APPARATUS_M = [
        'floor'           => 'Wolne',
        'pommel'          => 'Koń z łękami',
        'rings'           => 'Kółka',
        'vault'           => 'Skok',
        'parallel'        => 'Poręcze równoległe',
        'horizontal_bar'  => 'Drążek',
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
        $this->render('gymnastics/scoring/index', [
            'title'        => 'D-score + E-score — Gimnastyka',
            'performances' => $model->listForSport(self::SPORT_KEY),
            'members'      => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'apparatus_w'  => self::$APPARATUS_W,
            'apparatus_m'  => self::$APPARATUS_M,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('gymnastics/scoring');
        }
        $apparatus = trim((string)($_POST['apparatus'] ?? '')) ?: null;
        $dScore = $_POST['difficulty_score'] ?? '';
        $eScore = $_POST['execution_score'] ?? '';
        $deductions = (float)($_POST['deductions'] ?? 0);

        $data = [
            'sport_key'        => self::SPORT_KEY,
            'member_id'        => $memberId,
            'routine_type'     => trim((string)($_POST['routine_type'] ?? '')) ?: null,
            'apparatus'        => $apparatus,
            'difficulty_score' => $dScore !== '' ? (float)$dScore : null,
            'execution_score'  => $eScore !== '' ? (float)$eScore : null,
            'deductions'       => $deductions,
            'rank_position'    => !empty($_POST['rank_position']) ? (int)$_POST['rank_position'] : null,
            'notes'            => trim((string)($_POST['notes'] ?? '')) ?: null,
            'performed_at'     => trim((string)($_POST['performed_at'] ?? '')) ?: date('Y-m-d H:i:s'),
        ];
        $data['total_score'] = JudgedPerformanceModel::calcTotal($data);
        (new JudgedPerformanceModel())->insert($data);

        Session::flash('success', 'Występ zapisany.');
        $this->redirect('gymnastics/scoring');
    }

    public function show(string $id): void
    {
        $model = new JudgedPerformanceModel();
        $perf  = $model->findOwnedById((int)$id, self::SPORT_KEY);
        if (!$perf) {
            Session::flash('error', 'Występ nie znaleziony.');
            $this->redirect('gymnastics/scoring');
        }
        $this->render('gymnastics/scoring/show', [
            'title'       => 'Szczegóły — D/E score',
            'performance' => $perf,
            'judges'      => $model->judgeScoresFor((int)$id),
        ]);
    }

    public function addJudge(string $id): void
    {
        Csrf::verify();
        $model = new JudgedPerformanceModel();
        $perf  = $model->findOwnedById((int)$id, self::SPORT_KEY);
        if (!$perf) { Session::flash('error', 'Występ nie znaleziony.'); $this->redirect('gymnastics/scoring'); }
        $name = trim((string)($_POST['judge_name'] ?? ''));
        if ($name === '') { Session::flash('error', 'Podaj imię sędziego.'); $this->redirect('gymnastics/scoring/' . (int)$id); }

        $model->addJudgeScore((int)$id, [
            'judge_name'          => $name,
            'judge_certification' => trim((string)($_POST['judge_certification'] ?? '')) ?: null,
            'score_category'      => trim((string)($_POST['score_category'] ?? '')) ?: null,
            'score_value'         => (float)($_POST['score_value'] ?? 0),
            'notes'               => trim((string)($_POST['notes'] ?? '')) ?: null,
        ]);
        Session::flash('success', 'Ocena sędziego dodana.');
        $this->redirect('gymnastics/scoring/' . (int)$id);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new JudgedPerformanceModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('gymnastics/scoring');
    }
}
