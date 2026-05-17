<?php

namespace App\Sports\Climbing\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Support\Models\ClimbingAttemptModel;
use App\Sports\Support\Models\ClimbingRouteLibraryModel;

/**
 * Próby na drogach (top/zone/flash/onsight/failed).
 * Tabela: sport_climbing_attempts (z 106_scoring_niche_full.sql).
 */
class AttemptsController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('climbing');
    }

    public function index(): void
    {
        $memberId = !empty($_GET['member_id']) ? (int)$_GET['member_id'] : null;
        $model = new ClimbingAttemptModel();
        $this->render('climbing/attempts/index', [
            'title'    => 'Próby na drogach — Wspinaczka',
            'attempts' => $model->listForClub($memberId),
            'routes'   => (new ClimbingRouteLibraryModel())->listForClub(null, true),
            'members'  => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'results'  => ClimbingAttemptModel::$RESULTS,
            'filterMemberId' => $memberId,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        $routeId  = (int)($_POST['route_id']  ?? 0);
        if ($memberId <= 0 || $routeId <= 0) {
            Session::flash('error', 'Wybierz zawodnika i drogę.');
            $this->redirect('climbing/attempts');
        }
        $result = array_key_exists($_POST['result'] ?? '', ClimbingAttemptModel::$RESULTS)
            ? $_POST['result'] : 'failed';

        (new ClimbingAttemptModel())->insert([
            'member_id'      => $memberId,
            'route_id'       => $routeId,
            'attempt_date'   => trim((string)($_POST['attempt_date'] ?? '')) ?: date('Y-m-d'),
            'result'         => $result,
            'attempts_count' => max(1, (int)($_POST['attempts_count'] ?? 1)),
            'notes'          => trim((string)($_POST['notes'] ?? '')) ?: null,
        ]);
        Session::flash('success', 'Próba zapisana.');
        $this->redirect('climbing/attempts');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new ClimbingAttemptModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('climbing/attempts');
    }
}
