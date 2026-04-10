<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\UserModel;

class ClubSelectorController extends BaseController
{
    public function show(): void
    {
        $this->requireLogin();
        $clubs = (new UserModel())->getClubsForUser((int)Auth::id());

        $this->view->setLayout('auth');
        $this->view->render('auth/club_select', [
            'title'   => 'Wybierz klub',
            'clubs'   => $clubs,
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function select(string $id): void
    {
        $this->requireLogin();
        Csrf::verify();
        $clubId = (int)$id;

        $clubs = (new UserModel())->getClubsForUser((int)Auth::id());
        foreach ($clubs as $c) {
            if ((int)$c['club_id'] === $clubId) {
                Auth::setClub($clubId, $c['role']);
                $this->redirect('dashboard');
            }
        }
        Session::flash('error', 'Nie masz dostępu do tego klubu.');
        $this->redirect('club-select');
    }
}
