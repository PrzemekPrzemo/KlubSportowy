<?php

namespace App\Sports\Shooting\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Shooting\Models\WeaponModel;

class WeaponsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $weapons = (new WeaponModel())->listForClub();
        $this->render('shooting/weapons/index', [
            'title'   => 'Broń klubowa',
            'weapons' => $weapons,
        ]);
    }

    public function create(): void
    {
        $this->render('shooting/weapons/form', [
            'title'  => 'Nowa broń',
            'weapon' => null,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        (new WeaponModel())->insert($data);
        Session::flash('success', 'Broń dodana do ewidencji.');
        $this->redirect('shooting/weapons');
    }

    public function edit(string $id): void
    {
        $weapon = (new WeaponModel())->findById((int)$id);
        if (!$weapon) {
            Session::flash('error', 'Nie znaleziono.');
            $this->redirect('shooting/weapons');
        }
        $this->render('shooting/weapons/form', [
            'title'  => 'Edycja broni',
            'weapon' => $weapon,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        (new WeaponModel())->update((int)$id, $data);
        Session::flash('success', 'Zapisano.');
        $this->redirect('shooting/weapons');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new WeaponModel())->delete((int)$id);
        Session::flash('success', 'Broń usunięta.');
        $this->redirect('shooting/weapons');
    }

    public function show(string $id): void
    {
        $model   = new WeaponModel();
        $weapon  = $model->findById((int)$id);
        if (!$weapon) {
            Session::flash('error', 'Nie znaleziono.');
            $this->redirect('shooting/weapons');
        }
        $history = $model->historyForWeapon((int)$id);
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $this->render('shooting/weapons/show', [
            'title'   => ($weapon['brand'] ?? '') . ' ' . ($weapon['model'] ?? ''),
            'weapon'  => $weapon,
            'history' => $history,
            'members' => $members,
        ]);
    }

    public function assign(string $id): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        $purpose  = trim($_POST['purpose'] ?? '') ?: null;
        if ($memberId > 0) {
            (new WeaponModel())->assignTo((int)$id, $memberId, $purpose, Auth::id());
            Session::flash('success', 'Broń wypożyczona.');
        }
        $this->redirect('shooting/weapons/' . $id);
    }

    public function returnWeapon(string $id): void
    {
        Csrf::verify();
        (new WeaponModel())->returnWeapon((int)$id);
        Session::flash('success', 'Broń zwrócona.');
        $this->redirect('shooting/weapons/' . $id);
    }

    private function parsePost(): ?array
    {
        $data = [
            'category'        => in_array($_POST['category'] ?? '', ['pistolet','karabin','strzelba','pneumatyczna','inna'], true) ? $_POST['category'] : 'pistolet',
            'brand'           => trim($_POST['brand'] ?? '') ?: null,
            'model'           => trim($_POST['model'] ?? '') ?: null,
            'caliber'         => trim($_POST['caliber'] ?? '') ?: null,
            'serial_number'   => trim($_POST['serial_number'] ?? ''),
            'production_year' => !empty($_POST['production_year']) ? (int)$_POST['production_year'] : null,
            'condition_state' => in_array($_POST['condition_state'] ?? '', ['nowa','dobra','uzytkowa','do_serwisu','wycofana'], true) ? $_POST['condition_state'] : 'dobra',
            'purchase_date'   => trim($_POST['purchase_date'] ?? '') ?: null,
            'purchase_price'  => !empty($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : null,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
        ];
        if ($data['serial_number'] === '') {
            Session::flash('error', 'Numer seryjny jest wymagany.');
            $this->redirect('shooting/weapons/create');
            return null;
        }
        return $data;
    }
}
