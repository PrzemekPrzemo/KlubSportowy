<?php

namespace App\Sports\SkiJump\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\SportResultsCrudTrait;
use App\Models\MemberModel;
use App\Sports\SkiJump\Models\SkiJumpResultModel;

class ResultsController extends BaseController
{
    use RequiresActiveSport;
    use SportResultsCrudTrait;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('skijump');
    }

    protected function crudConfig(): array
    {
        return [
            'model'       => new SkiJumpResultModel(),
            'table'       => 'ski_jump_results',
            'index_route' => 'skijump/results',
            'view_prefix' => 'skijump/results',
            'title_show'  => 'Szczegóły wyniku — Skoki narciarskie',
            'title_edit'  => 'Edytuj wynik — Skoki narciarskie',
        ];
    }

    public function index(): void
    {
        $model = new SkiJumpResultModel();
        $this->render('skijump/results/index', [
            'title'   => 'Wyniki — Skoki narciarskie',
            'results' => $model->listForClub(),
            'members' => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('skijump/results'); }

        $j1p = !empty($_POST['jump1_points']) ? (float)$_POST['jump1_points'] : null;
        $j2p = !empty($_POST['jump2_points']) ? (float)$_POST['jump2_points'] : null;
        $total = null;
        if ($j1p !== null && $j2p !== null) $total = round($j1p + $j2p, 2);
        elseif ($j1p !== null)              $total = $j1p;

        (new SkiJumpResultModel())->insert([
            'member_id'    => $memberId,
            'event_name'   => trim($_POST['event_name'] ?? '') ?: 'Zawody',
            'event_date'   => trim($_POST['event_date'] ?? '') ?: date('Y-m-d'),
            'venue'        => trim($_POST['venue'] ?? '') ?: null,
            'hill_k'       => !empty($_POST['hill_k']) ? (int)$_POST['hill_k'] : null,
            'hill_size'    => trim($_POST['hill_size'] ?? '') ?: null,
            'category'     => trim($_POST['category'] ?? '') ?: null,
            'jump1_m'      => !empty($_POST['jump1_m']) ? (float)$_POST['jump1_m'] : null,
            'jump1_points' => $j1p,
            'jump2_m'      => !empty($_POST['jump2_m']) ? (float)$_POST['jump2_m'] : null,
            'jump2_points' => $j2p,
            'total_points' => $total,
            'place'        => !empty($_POST['place']) ? (int)$_POST['place'] : null,
            'fis_points'   => !empty($_POST['fis_points']) ? (float)$_POST['fis_points'] : null,
            'dnf'          => isset($_POST['dnf']) ? 1 : 0,
            'dns'          => isset($_POST['dns']) ? 1 : 0,
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('skijump/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new SkiJumpResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('skijump/results');
    }
}
