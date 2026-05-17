<?php

namespace App\Sports\DanceSport\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Support\Models\JudgedPerformanceModel;

/**
 * Dance Sport — skating system (judge marks + finalist promotion).
 * Uses shared sport_judged_performances (sport_key='dance_sport').
 */
class ScoringController extends BaseController
{
    use RequiresActiveSport;

    public const SPORT_KEY = 'dance_sport';

    public static array $ROUTINES = [
        'standard' => 'Standard (waltz, tango, foxtrot, quickstep, slow_waltz)',
        'latin'    => 'Latynoamerykańskie (samba, cha-cha, rumba, paso, jive)',
        'ten_dance' => '10-dance (kombinowane)',
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
        $this->render('dance_sport/scoring/index', [
            'title'        => 'Skating system — Taniec sportowy',
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
            Session::flash('error', 'Wybierz tancerza/parę.');
            $this->redirect('dance_sport/scoring');
        }
        $routine = array_key_exists($_POST['routine_type'] ?? '', self::$ROUTINES)
            ? $_POST['routine_type'] : 'standard';

        $tech = $_POST['technical_score'] ?? '';
        $pres = $_POST['presentation_score'] ?? '';

        $data = [
            'sport_key'          => self::SPORT_KEY,
            'member_id'          => $memberId,
            'routine_type'       => $routine,
            'technical_score'    => $tech !== '' ? (float)$tech : null,
            'presentation_score' => $pres !== '' ? (float)$pres : null,
            'deductions'         => (float)($_POST['deductions'] ?? 0),
            'rank_position'      => !empty($_POST['rank_position']) ? (int)$_POST['rank_position'] : null,
            'notes'              => trim((string)($_POST['notes'] ?? '')) ?: null,
            'performed_at'       => trim((string)($_POST['performed_at'] ?? '')) ?: date('Y-m-d H:i:s'),
        ];
        $data['total_score'] = JudgedPerformanceModel::calcTotal($data);
        (new JudgedPerformanceModel())->insert($data);

        Session::flash('success', 'Wystąp zapisany.');
        $this->redirect('dance_sport/scoring');
    }

    /**
     * Skating-system tally — promuje rank_position najnizszych (suma rank z wszystkich sedziow)
     * do finalu. W tej implementacji bierzemy najnizszy rank_position per member jako wskaznik.
     */
    public function finalists(): void
    {
        $model = new JudgedPerformanceModel();
        $rows = $model->listForSport(self::SPORT_KEY);
        $byMember = [];
        foreach ($rows as $r) {
            $mid = (int)$r['member_id'];
            if (!isset($byMember[$mid])) {
                $byMember[$mid] = [
                    'member_id'   => $mid,
                    'first_name'  => $r['first_name'],
                    'last_name'   => $r['last_name'],
                    'best_rank'   => PHP_INT_MAX,
                    'total_score' => 0.0,
                    'starts'      => 0,
                ];
            }
            if ($r['rank_position'] !== null && (int)$r['rank_position'] < $byMember[$mid]['best_rank']) {
                $byMember[$mid]['best_rank'] = (int)$r['rank_position'];
            }
            $byMember[$mid]['total_score'] += (float)($r['total_score'] ?? 0);
            $byMember[$mid]['starts']++;
        }
        usort($byMember, fn($a, $b) => $a['best_rank'] <=> $b['best_rank']);
        $finalists = array_slice($byMember, 0, 6);
        $this->render('dance_sport/scoring/finalists', [
            'title'     => 'Finaliści — Skating system',
            'finalists' => $finalists,
        ]);
    }

    public function show(string $id): void
    {
        $model = new JudgedPerformanceModel();
        $perf  = $model->findOwnedById((int)$id, self::SPORT_KEY);
        if (!$perf) {
            Session::flash('error', 'Występ nie znaleziony.');
            $this->redirect('dance_sport/scoring');
        }
        $this->render('dance_sport/scoring/show', [
            'title'       => 'Szczegóły występu',
            'performance' => $perf,
            'judges'      => $model->judgeScoresFor((int)$id),
        ]);
    }

    public function addJudge(string $id): void
    {
        Csrf::verify();
        $model = new JudgedPerformanceModel();
        $perf  = $model->findOwnedById((int)$id, self::SPORT_KEY);
        if (!$perf) { Session::flash('error', 'Występ nie znaleziony.'); $this->redirect('dance_sport/scoring'); }
        $name = trim((string)($_POST['judge_name'] ?? ''));
        if ($name === '') { Session::flash('error', 'Podaj imię sędziego.'); $this->redirect('dance_sport/scoring/' . (int)$id); }

        $model->addJudgeScore((int)$id, [
            'judge_name'          => $name,
            'judge_certification' => trim((string)($_POST['judge_certification'] ?? '')) ?: null,
            'score_category'      => trim((string)($_POST['score_category'] ?? '')) ?: null,
            'score_value'         => (float)($_POST['score_value'] ?? 0),
            'notes'               => trim((string)($_POST['notes'] ?? '')) ?: null,
        ]);
        Session::flash('success', 'Ocena sędziego dodana.');
        $this->redirect('dance_sport/scoring/' . (int)$id);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new JudgedPerformanceModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('dance_sport/scoring');
    }
}
