<?php

namespace App\Sports\Equestrian\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Equestrian\Models\EquestrianHorseOwnerModel;

/**
 * CRUD dla wlascicieli koni (zewnetrzni i wewnetrzni). Wymagany dla pelnej
 * domeny PZJ — kazdy kon ma jednego wlasciciela ktory moze byc zawodnikiem
 * klubu (member_id) lub osoba zewnetrzna (czlonek innego klubu, hodowca,
 * stowarzyszenie).
 */
class OwnersController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $owners = (new EquestrianHorseOwnerModel())->listForClub();
        $this->render('equestrian/owners/index', [
            'title'  => 'Właściciele koni',
            'owners' => $owners,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $fullName = trim($_POST['full_name'] ?? '');
        if ($fullName === '') {
            Session::flash('error', 'Podaj imie i nazwisko wlasciciela.');
            $this->redirect('equestrian/owners');
        }

        (new EquestrianHorseOwnerModel())->insert([
            'full_name' => $fullName,
            'member_id' => !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null,
            'address'   => trim($_POST['address']  ?? '') ?: null,
            'city'      => trim($_POST['city']     ?? '') ?: null,
            'phone'     => trim($_POST['phone']    ?? '') ?: null,
            'email'     => trim($_POST['email']    ?? '') ?: null,
            'tax_id'    => trim($_POST['tax_id']   ?? '') ?: null,
            'notes'     => trim($_POST['notes']    ?? '') ?: null,
        ]);
        Session::flash('success', 'Właściciel dodany.');
        $this->redirect('equestrian/owners');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        $model = new EquestrianHorseOwnerModel();
        try {
            $model->delete((int)$id);
            Session::flash('success', 'Usunięto właściciela.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Nie można usunąć — ten właściciel jest powiązany z końmi.');
        }
        $this->redirect('equestrian/owners');
    }
}
