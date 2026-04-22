<?php

namespace App\Sports\Kayaking\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Kayaking\Models\KayakBoatModel;

class BoatsController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('kayaking');
    }

    public function index(): void
    {
        $this->render('kayaking/boats/index', [
            'title'   => 'Łodzie kajakowe',
            'boats'   => (new KayakBoatModel())->listForClub(),
            'types'   => KayakBoatModel::$BOAT_TYPES,
            'states'  => KayakBoatModel::$STATES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') { Session::flash('error', 'Podaj nazwę łodzi.'); $this->redirect('kayaking/boats'); }
        $type  = array_key_exists($_POST['boat_type'] ?? '', KayakBoatModel::$BOAT_TYPES) ? $_POST['boat_type'] : 'K1';
        $state = array_key_exists($_POST['state']     ?? '', KayakBoatModel::$STATES)     ? $_POST['state']     : 'dobra';

        (new KayakBoatModel())->insert([
            'boat_type'     => $type,
            'name'          => $name,
            'hull_material' => trim($_POST['hull_material'] ?? '') ?: null,
            'year_built'    => !empty($_POST['year_built']) ? (int)$_POST['year_built'] : null,
            'purchase_date' => trim($_POST['purchase_date'] ?? '') ?: null,
            'state'         => $state,
            'location'      => trim($_POST['location'] ?? '') ?: null,
            'notes'         => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Łódź dodana.');
        $this->redirect('kayaking/boats');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new KayakBoatModel())->delete((int)$id);
        Session::flash('success', 'Łódź usunięta.');
        $this->redirect('kayaking/boats');
    }
}
