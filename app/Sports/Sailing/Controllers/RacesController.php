<?php

namespace App\Sports\Sailing\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Sailing\Models\SailingRaceModel;

class RacesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $year      = !empty($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $raceModel = new SailingRaceModel();

        $this->render('sailing/races/index', [
            'title'      => 'Regaty i wyniki — Żeglarstwo',
            'races'      => $raceModel->listForClub($year),
            'standings'  => $raceModel->seasonStandings($year),
            'filterYear' => $year,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $name = trim($_POST['name'] ?? '');
        if (!$name) { Session::flash('error', 'Podaj nazwę.'); $this->redirect('sailing/races'); }

        $type = in_array($_POST['race_type'] ?? '', ['regata','rejs','zawody','trening'], true) ? $_POST['race_type'] : 'regata';

        (new SailingRaceModel())->insert([
            'name'        => $name,
            'race_date'   => trim($_POST['race_date'] ?? '') ?: date('Y-m-d'),
            'race_type'   => $type,
            'distance_nm' => !empty($_POST['distance_nm']) ? (float)$_POST['distance_nm'] : null,
            'location'    => trim($_POST['location'] ?? '') ?: null,
            'notes'       => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Regata/rejs dodany/a.');
        $this->redirect('sailing/races');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new SailingRaceModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('sailing/races');
    }
}
