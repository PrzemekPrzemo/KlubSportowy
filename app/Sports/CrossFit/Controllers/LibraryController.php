<?php

namespace App\Sports\CrossFit\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Support\Models\CrossFitWodLibraryModel;

/**
 * WOD library — globalne benchmarki (Murph, Cindy, Helen, Fran) + klubowe.
 * Tabela: sport_crossfit_wods (z 106_scoring_niche_full.sql).
 */
class LibraryController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('crossfit');
    }

    public function index(): void
    {
        $model = new CrossFitWodLibraryModel();
        $this->render('crossfit/library/index', [
            'title' => 'Biblioteka WOD-ów — CrossFit',
            'wods'  => $model->listAvailable(),
            'types' => CrossFitWodLibraryModel::$TYPES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $created = (new CrossFitWodLibraryModel())->createClubWod($_POST);
        if ($created === 0) {
            Session::flash('error', 'Podaj nazwę WOD-a.');
        } else {
            Session::flash('success', 'WOD klubowy dodany.');
        }
        $this->redirect('crossfit/library');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        $ok = (new CrossFitWodLibraryModel())->deleteClubWod((int)$id);
        if (!$ok) {
            Session::flash('error', 'Nie można usunąć (WOD globalny lub brak uprawnień).');
        } else {
            Session::flash('success', 'Usunięto.');
        }
        $this->redirect('crossfit/library');
    }
}
