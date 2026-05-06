<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\SportContext;
use App\Models\ClubSportModel;
use App\Models\SportModel;
use App\Models\SubscriptionModel;

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

        // Strzelectwo: nie aktywujemy w ClubDesk — kierujemy do shootero.pl.
        // Defensywne: w starszych instalacjach klucz 'shooting' moze nadal
        // istniec w katalogu sports zanim C3 oznaczy go deprecated.
        $sport = (new SportModel())->findById($sportId);
        if ($sport !== null && ($sport['key'] ?? '') === 'shooting') {
            Session::flash('info',
                'Strzelectwo obslugujemy przez shootero.pl - przejdz na https://shootero.pl, aby utworzyc sekcje strzelecka.'
            );
            $this->redirect('sports');
            return;
        }

        $clubId = $this->currentClub();

        $subscription = new SubscriptionModel();
        if ($subscription->isOverSportLimit($clubId)) {
            $info  = $subscription->sportLimitInfo($clubId);
            $limit = $info['limit'] ?? '?';
            // Q.2.3 — proponuj dokup addona zamiast skakać na droższy plan
            Session::flash('error', sprintf(
                'Osiągnięto limit sekcji sportowych (%d/%d). Dokup +1 sekcję za 25 zł/m-c lub +5 za 99 zł/m-c, '
              . 'albo rozważ upgrade planu. Przejdź: /club/subscription/addons',
                $info['used'],
                $limit
            ));
            $this->redirect('sports');
            return;
        }

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

    /**
     * W.2 — formularz upload logo dla sekcji sportowej (3 sloty).
     */
    public function editLogos(string $id): void
    {
        $clubSport = (new ClubSportModel())->findWithSport((int)$id);
        if (!$clubSport) {
            Session::flash('error', 'Nie znaleziono sekcji.');
            $this->redirect('sports');
        }
        $this->render('sports/logos', [
            'title'     => 'Logo sekcji: ' . ($clubSport['sport_name'] ?? '—'),
            'clubSport' => $clubSport,
        ]);
    }

    public function saveLogos(string $id): void
    {
        Csrf::verify();
        $idInt     = (int)$id;
        $clubSport = (new ClubSportModel())->findWithSport($idInt);
        if (!$clubSport) {
            Session::flash('error', 'Nie znaleziono sekcji.');
            $this->redirect('sports');
        }

        $clubId = (int)$clubSport['club_id'];
        $data   = [];
        foreach (['main', 'alt', 'dark'] as $variant) {
            $field = 'logo_' . $variant;
            if (!empty($_FILES[$field]['tmp_name'])) {
                $path = $this->saveSportLogo($_FILES[$field], $clubId, $idInt, $variant);
                if ($path !== null) $data["logo_{$variant}_path"] = $path;
            }
            if (!empty($_POST['reset_' . $variant])) {
                $data["logo_{$variant}_path"] = null;
            }
        }

        if (!empty($data)) {
            (new ClubSportModel())->update($idInt, $data);
            Session::flash('success', 'Logo sekcji zaktualizowane.');
        } else {
            Session::flash('info', 'Brak zmian.');
        }
        $this->redirect('sports/' . $idInt . '/logos');
    }

    private function saveSportLogo(array $file, int $clubId, int $clubSportId, string $variant): ?string
    {
        return \App\Helpers\LogoUploader::save(
            $file,
            ROOT_PATH . "/public/uploads/clubs/{$clubId}/sports/{$clubSportId}",
            "uploads/clubs/{$clubId}/sports/{$clubSportId}",
            $variant
        );
    }
}
