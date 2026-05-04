<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\SportModuleLoader;
use App\Models\MemberModel;
use App\Models\MemberSportLicenseModel;

class SportLicensesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $model    = new MemberSportLicenseModel();
        $sportKey = $_GET['sport'] ?? null;
        $licenses = $model->listForClub($sportKey);
        $expiring = $model->expiringSoon(30);
        $members  = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $sports   = SportModuleLoader::load();

        $this->render('licenses/index', [
            'title'       => 'Licencje sportowe',
            'licenses'    => $licenses,
            'expiring'    => $expiring,
            'members'     => $members,
            'sports'      => $sports,
            'filterSport' => $sportKey,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('sport-licenses');
        }

        $sportKey = trim($_POST['sport_key'] ?? '');
        if ($sportKey === '') {
            Session::flash('error', 'Wybierz sport.');
            $this->redirect('sport-licenses');
        }

        $status = in_array($_POST['status'] ?? '', ['active', 'expired', 'suspended'], true)
                    ? $_POST['status'] : 'active';

        (new MemberSportLicenseModel())->insert([
            'member_id'      => $memberId,
            'sport_key'      => $sportKey,
            'license_number' => trim($_POST['license_number'] ?? ''),
            'federation'     => trim($_POST['federation'] ?? '') ?: null,
            'license_class'  => trim($_POST['license_class'] ?? '') ?: null,
            'valid_from'     => trim($_POST['valid_from'] ?? '') ?: date('Y-m-d'),
            'valid_to'       => trim($_POST['valid_to'] ?? '') ?: null,
            'status'         => $status,
            'notes'          => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Licencja dodana.');
        $this->redirect('sport-licenses');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new MemberSportLicenseModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('sport-licenses');
    }
}
