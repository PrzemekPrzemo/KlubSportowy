<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Archery\Models\ArcheryScorecardModel;
use App\Sports\Golf\Models\GolfCourseModel;
use App\Sports\Golf\Models\GolfScorecardModel;
use App\Sports\Padel\Models\PadelSportPairModel;

/**
 * Admin (klub) — zarządzanie zasobami dla 5 sportów rakietowych FULL.
 *
 * Routing:
 *   GET  /club/sport/golf/courses              (lista / form)
 *   POST /club/sport/golf/courses/store
 *   POST /club/sport/golf/courses/:id/delete
 *
 *   GET  /club/sport/padel/pairs               (lista / form)
 *   POST /club/sport/padel/pairs/store
 *   POST /club/sport/padel/pairs/:id/toggle
 *   POST /club/sport/padel/pairs/:id/delete
 *
 *   GET  /club/sport/{golf|archery}/scorecards (verify queue)
 *   POST /club/sport/{golf|archery}/scorecards/:id/verify
 *   POST /club/sport/{golf|archery}/scorecards/:id/delete
 */
class AdminRacketController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    /** ---- GOLF: COURSES ---- */

    public function golfCourses(): void
    {
        $courses = (new GolfCourseModel())->listForClub();
        $this->render('admin/racket/golf_courses', [
            'title'   => 'Golf — Pola',
            'courses' => $courses,
        ]);
    }

    public function golfCoursesStore(): void
    {
        Csrf::verify();
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            Session::flash('error', 'Nazwa pola wymagana.');
            $this->redirect('club/sport/golf/courses');
        }
        (new GolfCourseModel())->insert([
            'name'        => $name,
            'city'        => trim((string)($_POST['city'] ?? '')) ?: null,
            'holes_count' => max(9, min(36, (int)($_POST['holes_count'] ?? 18))),
            'par_total'   => max(30, min(150, (int)($_POST['par_total'] ?? 72))),
            'rating'      => isset($_POST['rating']) && $_POST['rating'] !== ''
                                ? (float)$_POST['rating'] : null,
            'slope'       => isset($_POST['slope']) && $_POST['slope'] !== ''
                                ? max(55, min(155, (int)$_POST['slope'])) : null,
        ]);
        Session::flash('success', 'Pole dodane.');
        $this->redirect('club/sport/golf/courses');
    }

    public function golfCoursesDelete(string $id): void
    {
        Csrf::verify();
        (new GolfCourseModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('club/sport/golf/courses');
    }

    /** ---- PADEL: PAIRS ---- */

    public function padelPairs(): void
    {
        $pairs   = (new PadelSportPairModel())->listForClub(false);
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('admin/racket/padel_pairs', [
            'title'   => 'Padel — Pary debla',
            'pairs'   => $pairs,
            'members' => $members,
        ]);
    }

    public function padelPairsStore(): void
    {
        Csrf::verify();
        $a = (int)($_POST['member_a_id'] ?? 0);
        $b = (int)($_POST['member_b_id'] ?? 0);
        if ($a <= 0 || $b <= 0 || $a === $b) {
            Session::flash('error', 'Wybierz dwóch różnych zawodników.');
            $this->redirect('club/sport/padel/pairs');
        }
        $name   = trim((string)($_POST['pair_name'] ?? '')) ?: null;
        $points = max(0, (int)($_POST['ranking_points'] ?? 0));

        $newId = (new PadelSportPairModel())->createPair($a, $b, $name, $points);
        if ($newId <= 0) {
            Session::flash('warning', 'Para już istnieje (lub niepoprawni członkowie).');
        } else {
            Session::flash('success', 'Para dodana.');
        }
        $this->redirect('club/sport/padel/pairs');
    }

    public function padelPairsToggle(string $id): void
    {
        Csrf::verify();
        $model = new PadelSportPairModel();
        $pair  = $model->findById((int)$id);
        if ($pair) {
            $model->update((int)$id, ['active' => $pair['active'] ? 0 : 1]);
        }
        $this->redirect('club/sport/padel/pairs');
    }

    public function padelPairsDelete(string $id): void
    {
        Csrf::verify();
        (new PadelSportPairModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('club/sport/padel/pairs');
    }

    /** ---- SCORECARDS: GOLF + ARCHERY VERIFY ---- */

    public function scorecards(string $key): void
    {
        $key = $this->guardKey($key);
        $data = [
            'title'    => $this->title($key) . ' — Weryfikacja scorecardów',
            'sportKey' => $key,
        ];
        if ($key === 'golf') {
            $data['pending']  = (new GolfScorecardModel())->listForClub(false);
            $data['verified'] = (new GolfScorecardModel())->listForClub(true);
        } else {
            $data['pending']  = (new ArcheryScorecardModel())->listForClub(false);
            $data['verified'] = (new ArcheryScorecardModel())->listForClub(true);
        }
        $this->render('admin/racket/scorecards', $data);
    }

    public function scorecardVerify(string $key, string $id): void
    {
        Csrf::verify();
        $key = $this->guardKey($key);
        $by  = (int)(Auth::id() ?? 0);
        if ($key === 'golf') {
            (new GolfScorecardModel())->verify((int)$id, $by);
        } else {
            (new ArcheryScorecardModel())->verify((int)$id, $by);
        }
        Session::flash('success', 'Scorecard zweryfikowany.');
        $this->redirect('club/sport/' . $key . '/scorecards');
    }

    public function scorecardDelete(string $key, string $id): void
    {
        Csrf::verify();
        $key = $this->guardKey($key);
        if ($key === 'golf') {
            (new GolfScorecardModel())->delete((int)$id);
        } else {
            (new ArcheryScorecardModel())->delete((int)$id);
        }
        Session::flash('success', 'Usunięto scorecard.');
        $this->redirect('club/sport/' . $key . '/scorecards');
    }

    /** ---- HELPERS ---- */

    private function guardKey(string $key): string
    {
        $key = strtolower(preg_replace('/[^a-z0-9_]/', '', $key) ?? '');
        if (!in_array($key, ['golf', 'archery'], true)) {
            http_response_code(404);
            echo 'Sport nieznany.';
            exit;
        }
        return $key;
    }

    private function title(string $key): string
    {
        return ['golf' => 'Golf', 'archery' => 'Łucznictwo'][$key] ?? ucfirst($key);
    }
}
