<?php

namespace App\Sports\Climbing\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Support\Models\ClimbingRouteLibraryModel;

/**
 * IFSC route library (lead / bouldering / speed) z gradami YDS+French.
 * Tabela: sport_climbing_routes (z 106_scoring_niche_full.sql).
 */
class LibraryController extends BaseController
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
        $discipline = $_GET['discipline'] ?? null;
        if ($discipline !== null && !array_key_exists($discipline, ClimbingRouteLibraryModel::$DISCIPLINES)) {
            $discipline = null;
        }
        $includeRetired = !empty($_GET['retired']);
        $model = new ClimbingRouteLibraryModel();
        $this->render('climbing/library/index', [
            'title'           => 'Biblioteka dróg (IFSC) — Wspinaczka',
            'routes'          => $model->listForClub($discipline, !$includeRetired),
            'disciplines'     => ClimbingRouteLibraryModel::$DISCIPLINES,
            'filterDiscipline' => $discipline,
            'includeRetired'  => $includeRetired,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $name = trim((string)($_POST['route_name'] ?? ''));
        if ($name === '') {
            Session::flash('error', 'Podaj nazwę drogi.');
            $this->redirect('climbing/library');
        }
        $disc = array_key_exists($_POST['discipline'] ?? '', ClimbingRouteLibraryModel::$DISCIPLINES)
            ? $_POST['discipline'] : 'lead';

        (new ClimbingRouteLibraryModel())->insert([
            'route_name'   => $name,
            'location_name'=> trim((string)($_POST['location_name'] ?? '')) ?: null,
            'discipline'   => $disc,
            'grade_yds'    => trim((string)($_POST['grade_yds'] ?? '')) ?: null,
            'grade_french' => trim((string)($_POST['grade_french'] ?? '')) ?: null,
            'setter'       => trim((string)($_POST['setter'] ?? '')) ?: null,
            'set_date'     => trim((string)($_POST['set_date'] ?? '')) ?: null,
        ]);
        Session::flash('success', 'Droga dodana do biblioteki.');
        $this->redirect('climbing/library');
    }

    public function retire(string $id): void
    {
        Csrf::verify();
        (new ClimbingRouteLibraryModel())->retire((int)$id);
        Session::flash('success', 'Droga oznaczona jako zdjęta.');
        $this->redirect('climbing/library');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new ClimbingRouteLibraryModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('climbing/library');
    }
}
