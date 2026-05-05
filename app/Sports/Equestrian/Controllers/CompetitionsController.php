<?php

namespace App\Sports\Equestrian\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Equestrian\Models\EquestrianCompetitionClassModel;
use App\Sports\Equestrian\Models\EquestrianCompetitionModel;

/**
 * CRUD dla zawodow jezdzieckich + zarzadzanie klasami w zawodach.
 *
 * Hierarchia (Q.5):
 *   /equestrian/competitions             — lista zawodow klubu
 *   /equestrian/competitions/store        — nowe zawody
 *   /equestrian/competitions/:id          — szczegoly + klasy
 *   /equestrian/competitions/:id/class    — dodaj klase do zawodow
 *   /equestrian/competitions/:id/delete   — usun zawody (cascade na klasy)
 *   /equestrian/classes/:id/delete        — usun klase
 */
class CompetitionsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $statusFilter = $_GET['status'] ?? '';
        $competitions = (new EquestrianCompetitionModel())->listForClub($statusFilter ?: null);

        $this->render('equestrian/competitions/index', [
            'title'        => 'Zawody jeździeckie',
            'competitions' => $competitions,
            'statusFilter' => $statusFilter,
            'levels'       => EquestrianCompetitionModel::$LEVELS,
            'statuses'     => EquestrianCompetitionModel::$STATUS,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        $name = trim($_POST['name'] ?? '');
        $dateFrom = trim($_POST['date_from'] ?? '');
        if ($name === '' || $dateFrom === '') {
            Session::flash('error', 'Nazwa i data rozpoczecia sa wymagane.');
            $this->redirect('equestrian/competitions');
        }

        $level = array_key_exists($_POST['level'] ?? '', EquestrianCompetitionModel::$LEVELS)
                ? $_POST['level'] : 'klubowe';
        $status = array_key_exists($_POST['status'] ?? '', EquestrianCompetitionModel::$STATUS)
                ? $_POST['status'] : 'zaplanowane';

        (new EquestrianCompetitionModel())->insert([
            'name'       => $name,
            'date_from'  => $dateFrom,
            'date_to'    => trim($_POST['date_to'] ?? '') ?: null,
            'location'   => trim($_POST['location'] ?? '') ?: null,
            'level'      => $level,
            'status'     => $status,
            'host_club'  => trim($_POST['host_club'] ?? '') ?: null,
            'notes'      => trim($_POST['notes'] ?? '') ?: null,
            'created_by' => Auth::id(),
        ]);
        Session::flash('success', 'Zawody utworzone.');
        $this->redirect('equestrian/competitions');
    }

    public function show(string $id): void
    {
        $competition = (new EquestrianCompetitionModel())->findWithClasses((int)$id);
        if (!$competition) {
            Session::flash('error', 'Nie znaleziono zawodow.');
            $this->redirect('equestrian/competitions');
        }
        $this->render('equestrian/competitions/show', [
            'title'       => $competition['name'],
            'competition' => $competition,
            'levels'      => EquestrianCompetitionModel::$LEVELS,
            'statuses'    => EquestrianCompetitionModel::$STATUS,
            'disciplines' => EquestrianCompetitionClassModel::$DISCIPLINES,
            'classLevels' => EquestrianCompetitionClassModel::$LEVELS,
        ]);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new EquestrianCompetitionModel())->delete((int)$id);
        Session::flash('success', 'Usunieto zawody.');
        $this->redirect('equestrian/competitions');
    }

    /**
     * Dodaje nowa klase do istniejacych zawodow.
     */
    public function addClass(string $id): void
    {
        Csrf::verify();
        $competitionId = (int)$id;
        $comp = (new EquestrianCompetitionModel())->findById($competitionId);
        if (!$comp) {
            Session::flash('error', 'Zawody nie znalezione.');
            $this->redirect('equestrian/competitions');
        }

        $name = trim($_POST['name'] ?? '');
        $discipline = $_POST['discipline'] ?? '';
        if ($name === '' || !array_key_exists($discipline, EquestrianCompetitionClassModel::$DISCIPLINES)) {
            Session::flash('error', 'Nazwa klasy i dyscyplina sa wymagane.');
            $this->redirect('equestrian/competitions/' . $competitionId);
        }

        (new EquestrianCompetitionClassModel())->insert([
            'competition_id'  => $competitionId,
            'class_no'        => !empty($_POST['class_no']) ? (int)$_POST['class_no'] : null,
            'name'            => $name,
            'discipline'      => $discipline,
            'class_level'     => trim($_POST['class_level'] ?? '') ?: null,
            'fence_height_cm' => !empty($_POST['fence_height_cm']) ? (int)$_POST['fence_height_cm'] : null,
            'time_allowed_s'  => !empty($_POST['time_allowed_s']) ? (int)$_POST['time_allowed_s'] : null,
            'max_starters'    => !empty($_POST['max_starters']) ? (int)$_POST['max_starters'] : null,
            'prize_pool'      => !empty($_POST['prize_pool']) ? (float)$_POST['prize_pool'] : null,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Klasa dodana.');
        $this->redirect('equestrian/competitions/' . $competitionId);
    }

    public function deleteClass(string $id): void
    {
        Csrf::verify();
        $classModel = new EquestrianCompetitionClassModel();
        $cls = $classModel->findById((int)$id);
        if (!$cls) {
            Session::flash('error', 'Klasa nie znaleziona.');
            $this->redirect('equestrian/competitions');
        }
        $compId = (int)$cls['competition_id'];
        $classModel->delete((int)$id);
        Session::flash('success', 'Usunieto klase.');
        $this->redirect('equestrian/competitions/' . $compId);
    }
}
