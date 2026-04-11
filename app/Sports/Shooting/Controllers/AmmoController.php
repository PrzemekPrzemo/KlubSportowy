<?php

namespace App\Sports\Shooting\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Shooting\Models\AmmoStockModel;

class AmmoController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $stock = (new AmmoStockModel())->listForClub();
        $this->render('shooting/ammo/index', [
            'title' => 'Amunicja — magazyn',
            'stock' => $stock,
        ]);
    }

    public function create(): void
    {
        $this->render('shooting/ammo/form', [
            'title' => 'Nowa pozycja magazynowa',
            'ammo'  => null,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = [
            'caliber'    => trim($_POST['caliber'] ?? ''),
            'type'       => trim($_POST['type'] ?? '') ?: null,
            'brand'      => trim($_POST['brand'] ?? '') ?: null,
            'quantity'   => (int)($_POST['quantity'] ?? 0),
            'unit_price' => !empty($_POST['unit_price']) ? (float)$_POST['unit_price'] : null,
            'min_stock'  => !empty($_POST['min_stock']) ? (int)$_POST['min_stock'] : null,
            'notes'      => trim($_POST['notes'] ?? '') ?: null,
        ];
        if ($data['caliber'] === '') {
            Session::flash('error', 'Kaliber jest wymagany.');
            $this->redirect('shooting/ammo/create');
        }
        (new AmmoStockModel())->insert($data);
        Session::flash('success', 'Pozycja dodana.');
        $this->redirect('shooting/ammo');
    }

    public function show(string $id): void
    {
        $model = new AmmoStockModel();
        $ammo  = $model->findById((int)$id);
        if (!$ammo) {
            Session::flash('error', 'Nie znaleziono.');
            $this->redirect('shooting/ammo');
        }
        $txs     = $model->transactionsForAmmo((int)$id, 50);
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $this->render('shooting/ammo/show', [
            'title'    => 'Magazyn: ' . ($ammo['caliber'] ?? ''),
            'ammo'     => $ammo,
            'txs'      => $txs,
            'members'  => $members,
        ]);
    }

    public function adjust(string $id): void
    {
        Csrf::verify();
        $direction = in_array($_POST['direction'] ?? '', ['przyjecie','wydanie','korekta'], true) ? $_POST['direction'] : 'przyjecie';
        $quantity  = (int)($_POST['quantity'] ?? 0);
        $memberId  = !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null;
        $notes     = trim($_POST['notes'] ?? '') ?: null;
        if ($quantity <= 0) {
            Session::flash('error', 'Podaj ilość.');
            $this->redirect('shooting/ammo/' . $id);
        }
        (new AmmoStockModel())->adjust((int)$id, $quantity, $direction, $memberId, $notes, Auth::id());
        Session::flash('success', 'Ruch magazynowy zapisany.');
        $this->redirect('shooting/ammo/' . $id);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new AmmoStockModel())->delete((int)$id);
        Session::flash('success', 'Pozycja usunięta.');
        $this->redirect('shooting/ammo');
    }
}
