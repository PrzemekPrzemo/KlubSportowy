<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\MemberAuth;
use App\Helpers\Session;
use App\Helpers\View;
use App\Sports\Archery\Models\ArcheryMemberModel;
use App\Sports\Archery\Models\ArcheryScorecardModel;
use App\Sports\Badminton\Models\BadmintonMemberModel;
use App\Sports\Badminton\Models\BadmintonResultModel;
use App\Sports\Golf\Models\GolfCourseModel;
use App\Sports\Golf\Models\GolfMemberModel;
use App\Sports\Golf\Models\GolfScorecardModel;
use App\Sports\Padel\Models\PadelSportPairModel;
use App\Sports\Squash\Models\SquashResultModel;

/**
 * Portal członka — widoki "my_record" + self-report scorecard
 * dla 5 sportów rakietowych FULL: badminton, squash, golf, padel, archery.
 *
 * Routing:
 *   GET  /portal/sport/{key}/my_record
 *   GET  /portal/sport/{key}/scorecard/new  (tylko golf, archery)
 *   POST /portal/sport/{key}/scorecard/store
 *
 * Multi-tenant: scoping przez ClubContext + MemberAuth (active club_id
 * jest zapisany w sesji członka przy logowaniu / selectClub).
 */
class PortalRacketController
{
    private const KEYS = ['badminton', 'squash', 'golf', 'padel', 'archery'];

    private View $view;

    public function __construct()
    {
        $this->view = new View();
    }

    /** ---- ROUTING ENTRY ---- */

    public function myRecord(string $key): void
    {
        $key = $this->guardKey($key);
        $memberId = $this->guardMember();

        $member = MemberAuth::member();
        $data = [
            'title'    => $this->title($key) . ' — Mój profil',
            'member'   => $member,
            'sportKey' => $key,
            'appName'  => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ];

        switch ($key) {
            case 'badminton':
                $bm = (new BadmintonMemberModel())->findByMember($memberId);
                $data['profile']  = $bm;
                $data['ranking']  = (new BadmintonMemberModel())->clubRanking(50);
                $data['results']  = (new BadmintonResultModel())->listForMember($memberId);
                break;
            case 'squash':
                $data['results']  = (new SquashResultModel())->listForClub($memberId);
                break;
            case 'golf':
                $data['profile']      = (new GolfMemberModel())->findByMember($memberId);
                $data['scorecards']   = (new GolfScorecardModel())->listForMember($memberId);
                $data['courses']      = (new GolfCourseModel())->listForClub();
                $data['canSelfReport'] = true;
                break;
            case 'padel':
                $data['myPairs'] = (new PadelSportPairModel())->listForMember($memberId);
                break;
            case 'archery':
                $data['profile']       = (new ArcheryMemberModel())->findByMember($memberId);
                $data['scorecards']    = (new ArcheryScorecardModel())->listForMember($memberId);
                $data['canSelfReport'] = true;
                break;
        }

        $this->view->setLayout('portal');
        $this->view->render('portal/racket/my_record', $data);
    }

    /** GET formularz nowego scorecardu (golf / archery). */
    public function newScorecard(string $key): void
    {
        $key = $this->guardKey($key);
        $this->guardMember();

        if (!in_array($key, ['golf', 'archery'], true)) {
            Session::flash('warning', 'Self-report scorecard nie jest dostępny dla tego sportu.');
            $this->redirect('portal/sport/' . $key . '/my_record');
        }

        $data = [
            'title'    => 'Nowy scorecard — ' . $this->title($key),
            'sportKey' => $key,
            'member'   => MemberAuth::member(),
            'appName'  => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ];
        if ($key === 'golf') {
            $data['courses'] = (new GolfCourseModel())->listForClub();
        }
        if ($key === 'archery') {
            $data['distances'] = ArcheryScorecardModel::DISTANCES;
        }
        $this->view->setLayout('portal');
        $this->view->render('portal/racket/scorecard_new', $data);
    }

    /** POST zapis self-report scorecardu. Zawsze verified=0. */
    public function storeScorecard(string $key): void
    {
        Csrf::verify();
        $key = $this->guardKey($key);
        $memberId = $this->guardMember();
        $clubId   = ClubContext::current();
        if ($clubId === null) {
            Session::flash('error', 'Brak kontekstu klubu.');
            $this->redirect('portal/sport/' . $key . '/my_record');
        }

        if ($key === 'golf') {
            $courseId = !empty($_POST['course_id']) ? (int)$_POST['course_id'] : null;
            $playedAt = trim((string)($_POST['played_at'] ?? '')) ?: date('Y-m-d');
            $hcp      = isset($_POST['handicap_used']) && $_POST['handicap_used'] !== ''
                            ? (float)$_POST['handicap_used'] : null;

            $rawHoles = $_POST['hole_scores'] ?? [];
            $holes = [];
            if (is_array($rawHoles)) {
                foreach ($rawHoles as $h) {
                    if ($h === '' || $h === null) continue;
                    $holes[] = max(1, min(15, (int)$h));
                }
            }

            // Walidacja course belongs to klub
            $parTotal = 72;
            if ($courseId) {
                $cm = new GolfCourseModel();
                $course = $cm->findById($courseId);
                if (!$course) {
                    Session::flash('error', 'Nieprawidłowe pole golfowe.');
                    $this->redirect('portal/sport/golf/my_record');
                }
                $parTotal = (int)($course['par_total'] ?: 72);
            }
            $totals = GolfScorecardModel::computeTotals($holes, $parTotal);

            (new GolfScorecardModel())->insert([
                'member_id'        => $memberId,
                'course_id'        => $courseId,
                'played_at'        => $playedAt,
                'total_strokes'    => $totals['total_strokes'] ?: null,
                'total_to_par'     => $totals['total_strokes'] ? $totals['total_to_par'] : null,
                'handicap_used'    => $hcp,
                'hole_scores_json' => $holes ? json_encode($holes) : null,
                'verified'         => 0,
                'notes'            => trim((string)($_POST['notes'] ?? '')) ?: null,
            ]);
            Session::flash('success', 'Scorecard zapisany. Czeka na weryfikację klubu.');
            $this->redirect('portal/sport/golf/my_record');
        }

        if ($key === 'archery') {
            $shotAt    = trim((string)($_POST['shot_at'] ?? '')) ?: date('Y-m-d');
            $distance  = max(1, min(150, (int)($_POST['distance_m'] ?? 18)));
            $perEnd    = max(1, min(12, (int)($_POST['arrows_per_end'] ?? 6)));
            $totalEnds = max(1, min(20, (int)($_POST['total_ends'] ?? 6)));

            // scores: posted jako tablica end[][arrow]
            $rawEnds = $_POST['ends'] ?? [];
            $ends = [];
            if (is_array($rawEnds)) {
                foreach ($rawEnds as $end) {
                    if (!is_array($end)) continue;
                    $row = [];
                    foreach ($end as $arrow) {
                        $arrow = is_string($arrow) ? trim($arrow) : $arrow;
                        if ($arrow === '' || $arrow === null) continue;
                        $row[] = is_string($arrow) && strtoupper($arrow) === 'X' ? 'X' : (int)$arrow;
                    }
                    if ($row) $ends[] = $row;
                }
            }
            $totals = ArcheryScorecardModel::computeTotals($ends);

            (new ArcheryScorecardModel())->insert([
                'member_id'      => $memberId,
                'shot_at'        => $shotAt,
                'distance_m'     => $distance,
                'arrows_per_end' => $perEnd,
                'total_ends'     => $totalEnds,
                'scores_json'    => $ends ? json_encode($ends) : null,
                'total_score'    => $totals['total_score'] ?: null,
                'tens'           => $totals['tens'],
                'x_count'        => $totals['x_count'],
                'verified'       => 0,
                'notes'          => trim((string)($_POST['notes'] ?? '')) ?: null,
            ]);
            Session::flash('success', 'Scorecard łuczniczy zapisany. Czeka na weryfikację klubu.');
            $this->redirect('portal/sport/archery/my_record');
        }

        Session::flash('warning', 'Self-report nie wspierany dla tego sportu.');
        $this->redirect('portal/sport/' . $key . '/my_record');
    }

    /** ---- HELPERS ---- */

    private function guardKey(string $key): string
    {
        $key = strtolower(preg_replace('/[^a-z0-9_]/', '', $key) ?? '');
        if (!in_array($key, self::KEYS, true)) {
            http_response_code(404);
            echo 'Sport nieznany.';
            exit;
        }
        return $key;
    }

    private function guardMember(): int
    {
        MemberAuth::requireLogin();
        if (MemberAuth::isMultiClub() && MemberAuth::clubId() === null) {
            header('Location: ' . url('portal/club-select'));
            exit;
        }
        return (int)MemberAuth::id();
    }

    private function title(string $key): string
    {
        return [
            'badminton' => 'Badminton',
            'squash'    => 'Squash',
            'golf'      => 'Golf',
            'padel'     => 'Padel',
            'archery'   => 'Łucznictwo',
        ][$key] ?? ucfirst($key);
    }

    private function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }
}
