<?php

namespace App\Sports\Equestrian\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Equestrian\Models\EquestrianHorseModel;

class HorsesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $horses = (new EquestrianHorseModel())->listForClub();
        $this->render('equestrian/horses/index', [
            'title'       => 'Konie — Jeździectwo',
            'horses'      => $horses,
            'disciplines' => EquestrianHorseModel::$DISCIPLINES,
            'sexOptions'  => EquestrianHorseModel::$SEX,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Session::flash('error', 'Podaj imię konia.');
            $this->redirect('equestrian/horses');
        }

        $sex = array_key_exists($_POST['sex'] ?? '', EquestrianHorseModel::$SEX)
                    ? $_POST['sex'] : 'gelding';

        (new EquestrianHorseModel())->insert([
            'name'        => $name,
            'breed'       => trim($_POST['breed'] ?? '') ?: null,
            'birth_year'  => !empty($_POST['birth_year']) ? (int)$_POST['birth_year'] : null,
            'color'       => trim($_POST['color'] ?? '') ?: null,
            'sex'         => $sex,
            'passport_no' => trim($_POST['passport_no'] ?? '') ?: null,
            'owner_name'  => trim($_POST['owner_name'] ?? '') ?: null,
            'status'      => 'active',
            'notes'       => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Koń dodany.');
        $this->redirect('equestrian/horses');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new EquestrianHorseModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('equestrian/horses');
    }
}
