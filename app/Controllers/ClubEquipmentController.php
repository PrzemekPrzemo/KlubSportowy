<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\ClubEquipmentModel;
use App\Models\MemberModel;
use App\Models\SportModel;

class ClubEquipmentController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $sport  = $_GET['sport'] ?? null;
        $state  = $_GET['state'] ?? null;
        $model  = new ClubEquipmentModel();

        $this->render('admin/equipment/index', [
            'title'       => 'Sprzęt klubowy',
            'items'       => $model->listForClub($sport, $state),
            'sports'      => (new SportModel())->listForClub($this->currentClub()),
            'members'     => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'states'      => ClubEquipmentModel::$STATES,
            'sportFilter' => $sport,
            'stateFilter' => $state,
        ]);
    }

    public function show(string $id): void
    {
        $model = new ClubEquipmentModel();
        $item  = $model->findWithAssignment((int)$id);
        if (!$item) {
            Session::flash('error', 'Nie znaleziono sprzętu.');
            $this->redirect('equipment');
        }
        $this->render('admin/equipment/item', [
            'title'   => $item['name'],
            'item'    => $item,
            'members' => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'states'  => ClubEquipmentModel::$STATES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $name     = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        if ($name === '' || $category === '') {
            Session::flash('error', 'Nazwa i kategoria są wymagane.');
            $this->redirect('equipment');
        }
        $state = array_key_exists($_POST['state'] ?? '', ClubEquipmentModel::$STATES)
            ? $_POST['state'] : 'dobry';

        (new ClubEquipmentModel())->insert([
            'sport_key'      => trim($_POST['sport_key'] ?? '') ?: null,
            'category'       => $category,
            'name'           => $name,
            'serial_number'  => trim($_POST['serial_number'] ?? '') ?: null,
            'brand'          => trim($_POST['brand'] ?? '') ?: null,
            'model'          => trim($_POST['model'] ?? '') ?: null,
            'size'           => trim($_POST['size'] ?? '') ?: null,
            'purchase_date'  => trim($_POST['purchase_date'] ?? '') ?: null,
            'purchase_price' => !empty($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : null,
            'state'          => $state,
            'location'       => trim($_POST['location'] ?? '') ?: null,
            'notes'          => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Sprzęt dodany.');
        $this->redirect('equipment');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new ClubEquipmentModel())->delete((int)$id);
        Session::flash('success', 'Sprzęt usunięty.');
        $this->redirect('equipment');
    }

    public function assign(string $id): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('equipment/' . (int)$id);
        }

        $result = (new ClubEquipmentModel())->assignToMember(
            (int)$id,
            $memberId,
            trim($_POST['issue_notes'] ?? '') ?: null,
            Auth::id()
        );
        if ($result === 0) {
            Session::flash('error', 'Sprzęt jest już przypisany.');
        } else {
            Session::flash('success', 'Sprzęt wydany.');
        }
        $this->redirect('equipment/' . (int)$id);
    }

    public function returnItem(string $id, string $assignmentId): void
    {
        Csrf::verify();
        (new ClubEquipmentModel())->returnFromMember(
            (int)$assignmentId,
            trim($_POST['return_notes'] ?? '') ?: null,
            Auth::id()
        );
        Session::flash('success', 'Sprzęt zwrócony.');
        $this->redirect('equipment/' . (int)$id);
    }
}
