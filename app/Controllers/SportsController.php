<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\SportContext;
use App\Models\ClubSportModel;
use App\Models\SportModel;

class SportsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $clubId      = $this->currentClub();
        $current     = (new SportModel())->listForClub($clubId);
        $available   = (new SportModel())->listAvailableForClub($clubId);

        $this->render('sports/index', [
            'title'     => 'Sekcje sportowe',
            'current'   => $current,
            'available' => $available,
        ]);
    }

    public function enable(): void
    {
        Csrf::verify();
        $sportId = (int)($_POST['sport_id'] ?? 0);
        $name    = trim($_POST['name'] ?? '') ?: null;
        if ($sportId <= 0) {
            Session::flash('error', 'Nie wybrano sportu.');
            $this->redirect('sports');
        }

        $clubId = $this->currentClub();
        (new ClubSportModel())->addSportToClub($clubId, $sportId, $name);

        Session::flash('success', 'Sekcja sportowa została uruchomiona.');
        $this->redirect('sports');
    }

    public function disable(string $id): void
    {
        Csrf::verify();
        (new ClubSportModel())->deactivate((int)$id);
        Session::flash('success', 'Sekcja sportowa została zdezaktywowana.');
        $this->redirect('sports');
    }

    public function activate(string $id): void
    {
        Csrf::verify();
        $clubSport = (new ClubSportModel())->findWithSport((int)$id);
        if (!$clubSport) {
            Session::flash('error', 'Nie znaleziono sekcji.');
            $this->redirect('sports');
        }
        SportContext::set(
            (int)$clubSport['id'],
            (int)$clubSport['sport_id'],
            $clubSport['sport_key']
        );
        Session::flash('success', 'Aktywna sekcja: ' . $clubSport['sport_name']);
        $this->redirect('dashboard');
    }

    public function clearActive(): void
    {
        Csrf::verify();
        SportContext::clear();
        Session::flash('info', 'Wyczyszczono kontekst sekcji sportowej.');
        $this->redirect('dashboard');
    }
}
