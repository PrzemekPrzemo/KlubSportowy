<?php

namespace App\Sports\Sailing\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Support\Models\SailingMemberProfileModel;
use App\Sports\Support\Models\SailingRegattaRaceModel;

/**
 * Regaty multi-race z low-point scoring i drop-worst-N.
 * Tabela: sport_sailing_regatta_races (z 106_scoring_niche_full.sql).
 */
class RegattaController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('sailing');
    }

    public function index(): void
    {
        $tournamentId = !empty($_GET['tournament_id']) ? (int)$_GET['tournament_id'] : null;
        $boatClass    = !empty($_GET['boat_class']) ? (string)$_GET['boat_class'] : null;
        $dropWorst    = isset($_GET['drop_worst']) ? max(0, (int)$_GET['drop_worst']) : 1;

        $model = new SailingRegattaRaceModel();
        $this->render('sailing/regatta/index', [
            'title'        => 'Regaty (multi-race) — Żeglarstwo',
            'races'        => $model->listForClub($tournamentId, $boatClass),
            'standings'    => $model->regattaStandings($tournamentId, $boatClass, $dropWorst),
            'statuses'     => SailingRegattaRaceModel::$STATUSES,
            'members'      => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'boatClasses'  => SailingMemberProfileModel::$BOAT_CLASSES,
            'filterTournamentId' => $tournamentId,
            'filterBoatClass'    => $boatClass,
            'dropWorst'    => $dropWorst,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId   = (int)($_POST['member_id'] ?? 0);
        $raceNumber = (int)($_POST['race_number'] ?? 0);
        if ($memberId <= 0 || $raceNumber <= 0) {
            Session::flash('error', 'Wybierz zawodnika i podaj numer wyścigu.');
            $this->redirect('sailing/regatta');
        }
        $status = array_key_exists($_POST['status'] ?? '', SailingRegattaRaceModel::$STATUSES)
            ? $_POST['status'] : 'finished';

        $points = $_POST['points'] ?? '';
        $position = $_POST['position'] ?? '';

        (new SailingRegattaRaceModel())->insert([
            'tournament_id' => !empty($_POST['tournament_id']) ? (int)$_POST['tournament_id'] : null,
            'member_id'     => $memberId,
            'boat_class'    => trim((string)($_POST['boat_class'] ?? '')) ?: null,
            'race_number'   => $raceNumber,
            'position'      => $position !== '' ? (int)$position : null,
            'points'        => $points !== '' ? (float)$points : null,
            'status'        => $status,
            'weather_wind_knots'      => isset($_POST['weather_wind_knots']) && $_POST['weather_wind_knots'] !== ''
                                            ? (int)$_POST['weather_wind_knots'] : null,
            'weather_wave_height_cm'  => isset($_POST['weather_wave_height_cm']) && $_POST['weather_wave_height_cm'] !== ''
                                            ? (int)$_POST['weather_wave_height_cm'] : null,
            'race_date'     => trim((string)($_POST['race_date'] ?? '')) ?: date('Y-m-d'),
        ]);
        Session::flash('success', 'Wyścig zapisany.');
        $this->redirect('sailing/regatta');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new SailingRegattaRaceModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('sailing/regatta');
    }
}
