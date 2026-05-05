<?php

namespace App\Sports\AlpineSki\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\SportResultsCrudTrait;
use App\Models\MemberModel;
use App\Sports\AlpineSki\Models\AlpineSkiResultModel;

class ResultsController extends BaseController
{
    use RequiresActiveSport;
    use SportResultsCrudTrait;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('alpineski');
    }

    protected function crudConfig(): array
    {
        return [
            'model'         => new AlpineSkiResultModel(),
            'table'         => 'alpine_ski_results',
            'index_route'   => 'alpineski/results',
            'view_prefix'   => 'alpineski/results',
            'title_show'    => 'Szczegóły wyniku — Narciarstwo alpejskie',
            'title_edit'    => 'Edytuj wynik — Narciarstwo alpejskie',
            'extra_selects' => [
                'discipline' => ['label' => 'Konkurencja', 'options' => AlpineSkiResultModel::$DISCIPLINES],
            ],
        ];
    }

    public function index(): void
    {
        $disc = $_GET['discipline'] ?? null;
        $model = new AlpineSkiResultModel();
        $this->render('alpineski/results/index', [
            'title'       => 'Wyniki — Narciarstwo alpejskie',
            'results'     => $model->listForClub(null, $disc),
            'members'     => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'disciplines' => AlpineSkiResultModel::$DISCIPLINES,
            'discFilter'  => $disc,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('alpineski/results'); }
        $disc = array_key_exists($_POST['discipline'] ?? '', AlpineSkiResultModel::$DISCIPLINES) ? $_POST['discipline'] : 'slalom';

        // Auto-calc total from runs (slalom/gigant → sum; zjazd/SG → run1 only)
        $r1 = !empty($_POST['run1_ms']) ? (int)$_POST['run1_ms'] : null;
        $r2 = !empty($_POST['run2_ms']) ? (int)$_POST['run2_ms'] : null;
        $total = null;
        if (in_array($disc, ['slalom', 'slalom_gigant', 'kombinacja', 'kombinacja_alpejska']) && $r1 !== null && $r2 !== null) {
            $total = $r1 + $r2;
        } elseif ($r1 !== null && $r2 === null) {
            $total = $r1;
        }

        (new AlpineSkiResultModel())->insert([
            'member_id'  => $memberId,
            'discipline' => $disc,
            'event_name' => trim($_POST['event_name'] ?? '') ?: 'Zawody',
            'event_date' => trim($_POST['event_date'] ?? '') ?: date('Y-m-d'),
            'venue'      => trim($_POST['venue'] ?? '') ?: null,
            'category'   => trim($_POST['category'] ?? '') ?: null,
            'run1_ms'    => $r1,
            'run2_ms'    => $r2,
            'total_ms'   => $total,
            'place'      => !empty($_POST['place']) ? (int)$_POST['place'] : null,
            'fis_points' => !empty($_POST['fis_points']) ? (float)$_POST['fis_points'] : null,
            'dnf'        => isset($_POST['dnf']) ? 1 : 0,
            'dns'        => isset($_POST['dns']) ? 1 : 0,
            'notes'      => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('alpineski/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new AlpineSkiResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('alpineski/results');
    }
}
