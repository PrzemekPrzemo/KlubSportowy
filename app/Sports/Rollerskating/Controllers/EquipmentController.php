<?php

namespace App\Sports\Rollerskating\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Rollerskating\Models\RollerskatingEquipmentModel;

class EquipmentController extends BaseController
{
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }

    public function index(): void
    {
        $items = (new RollerskatingEquipmentModel())->listForClub();
        $this->render('rollerskating/equipment/index', ['title' => 'Sprzęt wrotkarski', 'items' => $items]);
    }

    public function create(): void
    {
        $members = (new \App\Models\MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('rollerskating/equipment/form', ['title' => 'Nowy sprzęt', 'item' => null, 'members' => $members]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = [
            'member_id'       => !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null,
            'type'            => in_array($_POST['type'] ?? '', ['wrotki','ochraniacze','kask','buty','kombinezon','inne'], true) ? $_POST['type'] : 'wrotki',
            'brand'           => trim($_POST['brand'] ?? '') ?: null,
            'model'           => trim($_POST['model'] ?? '') ?: null,
            'size'            => trim($_POST['size'] ?? '') ?: null,
            'condition_state' => in_array($_POST['condition_state'] ?? '', ['nowy','dobry','uzytkowy','do_serwisu','wycofany'], true) ? $_POST['condition_state'] : 'dobry',
            'purchase_date'   => trim($_POST['purchase_date'] ?? '') ?: null,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
        ];
        (new RollerskatingEquipmentModel())->insert($data);
        Session::flash('success', 'Sprzęt dodany.');
        $this->redirect('rollerskating/equipment');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new RollerskatingEquipmentModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('rollerskating/equipment');
    }
}
