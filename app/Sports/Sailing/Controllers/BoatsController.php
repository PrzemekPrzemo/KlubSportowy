<?php

namespace App\Sports\Sailing\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Sailing\Models\SailingBoatModel;

class BoatsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $boatModel = new SailingBoatModel();
        $boats     = $boatModel->listForClub();
        $crews     = [];
        foreach ($boats as $b) { $crews[$b['id']] = $boatModel->crewForBoat((int)$b['id']); }
        $expiring  = $boatModel->expiringInsurance(30);

        $this->render('sailing/boats/index', [
            'title'    => 'Łodzie i jachty — Żeglarstwo',
            'boats'    => $boats,
            'crews'    => $crews,
            'expiring' => $expiring,
            'members'  => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $name = trim($_POST['name'] ?? '');
        if (!$name) { Session::flash('error', 'Podaj nazwę.'); $this->redirect('sailing/boats'); }

        (new SailingBoatModel())->insert([
            'name'                => $name,
            'registration_number' => trim($_POST['registration_number'] ?? '') ?: null,
            'class'               => trim($_POST['class'] ?? '') ?: null,
            'length_m'            => !empty($_POST['length_m']) ? (float)$_POST['length_m'] : null,
            'year_built'          => !empty($_POST['year_built']) ? (int)$_POST['year_built'] : null,
            'hull_material'       => trim($_POST['hull_material'] ?? '') ?: null,
            'insurance_expiry'    => trim($_POST['insurance_expiry'] ?? '') ?: null,
            'next_inspection'     => trim($_POST['next_inspection'] ?? '') ?: null,
            'owner_member_id'     => !empty($_POST['owner_member_id']) ? (int)$_POST['owner_member_id'] : null,
            'notes'               => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Łódź dodana.');
        $this->redirect('sailing/boats');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new SailingBoatModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('sailing/boats');
    }

    public function addCrew(string $id): void
    {
        Csrf::verify();
        $boatId   = (int)$id;
        $memberId = (int)($_POST['member_id'] ?? 0);
        if (!$memberId) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('sailing/boats'); }

        $role      = in_array($_POST['role'] ?? '', ['skipper','crew','navigator','tactician','trimmer'], true) ? $_POST['role'] : 'crew';
        $permanent = !empty($_POST['is_permanent']);
        (new SailingBoatModel())->addCrew($boatId, $memberId, $role, $permanent);
        Session::flash('success', 'Dodano do załogi.');
        $this->redirect('sailing/boats');
    }

    public function removeCrew(string $id): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        (new SailingBoatModel())->removeCrew((int)$id, $memberId);
        Session::flash('success', 'Usunięto z załogi.');
        $this->redirect('sailing/boats');
    }
}
