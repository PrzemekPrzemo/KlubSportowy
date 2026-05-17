<?php

namespace App\Sports\Equestrian\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Support\Models\EquestrianHorseModel;

/**
 * FEI Horses registry — nowy schema sport_equestrian_horses (z 106).
 * Oddzielne od istniejacych equestrian_horses (zachowanych bez zmian).
 */
class FeiHorsesController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('equestrian');
    }

    public function index(): void
    {
        $model = new EquestrianHorseModel();
        $this->render('equestrian/fei_horses/index', [
            'title'   => 'Rejestr koni FEI — Jeździectwo',
            'horses'  => $model->listForClub(),
            'members' => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            Session::flash('error', 'Podaj imię konia.');
            $this->redirect('equestrian/fei-horses');
        }
        (new EquestrianHorseModel())->insert([
            'name'            => $name,
            'breed'           => trim((string)($_POST['breed'] ?? '')) ?: null,
            'birth_year'      => isset($_POST['birth_year']) && $_POST['birth_year'] !== ''
                                    ? (int)$_POST['birth_year'] : null,
            'fei_id'          => trim((string)($_POST['fei_id'] ?? '')) ?: null,
            'owner_member_id' => !empty($_POST['owner_member_id']) ? (int)$_POST['owner_member_id'] : null,
            'notes'           => trim((string)($_POST['notes'] ?? '')) ?: null,
        ]);
        Session::flash('success', 'Koń dodany do rejestru.');
        $this->redirect('equestrian/fei-horses');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new EquestrianHorseModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('equestrian/fei-horses');
    }
}
