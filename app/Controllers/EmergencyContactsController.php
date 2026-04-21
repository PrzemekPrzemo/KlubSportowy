<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\EmergencyContactModel;
use App\Models\MemberModel;

class EmergencyContactsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function member(string $memberId): void
    {
        $mid    = (int)$memberId;
        $member = (new MemberModel())->findById($mid);
        if (!$member) {
            Session::flash('error', 'Nie znaleziono zawodnika.');
            $this->redirect('members');
        }

        $model = new EmergencyContactModel();
        $this->render('members/emergency_contacts', [
            'title'        => 'Kontakty awaryjne — ' . $member['last_name'] . ' ' . $member['first_name'],
            'member'       => $member,
            'contacts'     => $model->listForMember($mid),
            'relationships'=> EmergencyContactModel::$RELATIONSHIPS,
        ]);
    }

    public function store(string $memberId): void
    {
        Csrf::verify();
        $mid  = (int)$memberId;
        $name = trim($_POST['contact_name'] ?? '');
        $phone= trim($_POST['phone'] ?? '');

        if ($name === '' || $phone === '') {
            Session::flash('error', 'Imię i telefon są wymagane.');
            $this->redirect('members/' . $mid . '/emergency-contacts');
        }

        $rel = array_key_exists($_POST['relationship'] ?? '', EmergencyContactModel::$RELATIONSHIPS)
            ? $_POST['relationship'] : 'rodzic';

        $model = new EmergencyContactModel();
        $id = $model->insert([
            'member_id'    => $mid,
            'contact_name' => $name,
            'relationship' => $rel,
            'phone'        => $phone,
            'phone_alt'    => trim($_POST['phone_alt'] ?? '') ?: null,
            'email'        => trim($_POST['email'] ?? '') ?: null,
            'is_primary'   => isset($_POST['is_primary']) ? 1 : 0,
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ]);

        if (isset($_POST['is_primary'])) {
            $model->setPrimary($mid, (int)$id);
        }

        Session::flash('success', 'Kontakt awaryjny dodany.');
        $this->redirect('members/' . $mid . '/emergency-contacts');
    }

    public function makePrimary(string $memberId, string $id): void
    {
        Csrf::verify();
        (new EmergencyContactModel())->setPrimary((int)$memberId, (int)$id);
        Session::flash('success', 'Ustawiono jako główny kontakt.');
        $this->redirect('members/' . (int)$memberId . '/emergency-contacts');
    }

    public function delete(string $memberId, string $id): void
    {
        Csrf::verify();
        (new EmergencyContactModel())->delete((int)$id);
        Session::flash('success', 'Kontakt usunięty.');
        $this->redirect('members/' . (int)$memberId . '/emergency-contacts');
    }
}
