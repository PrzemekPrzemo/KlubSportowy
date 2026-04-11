<?php

namespace App\Sports\Shooting\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\FederationModel;
use App\Models\MemberLicenseModel;
use App\Models\MemberModel;
use App\Models\SportModel;

class PzssLicensesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    private function pzssIds(): array
    {
        $pzss = (new FederationModel())->findByCode('PZSS');
        $sport = (new SportModel())->findByKey('shooting');
        return [
            'federation_id' => $pzss['id'] ?? null,
            'sport_id'      => $sport['id'] ?? null,
        ];
    }

    public function index(): void
    {
        $ids  = $this->pzssIds();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $type = $_GET['type'] ?? '';
        $pagination = (new MemberLicenseModel())->listForClub($ids['sport_id'], $type ?: null, $page, 25);
        $expiring   = (new MemberLicenseModel())->expiringSoon(60, $ids['sport_id']);

        $this->render('shooting/licenses/index', [
            'title'      => 'Licencje PZSS',
            'pagination' => $pagination,
            'expiring'   => $expiring,
            'filterType' => $type,
        ]);
    }

    public function create(): void
    {
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('shooting/licenses/form', [
            'title'   => 'Nowa licencja PZSS',
            'license' => null,
            'members' => $members,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        (new MemberLicenseModel())->insert($data);
        Session::flash('success', 'Licencja dodana.');
        $this->redirect('shooting/licenses');
    }

    public function edit(string $id): void
    {
        $license = (new MemberLicenseModel())->findById((int)$id);
        if (!$license) {
            Session::flash('error', 'Nie znaleziono.');
            $this->redirect('shooting/licenses');
        }
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('shooting/licenses/form', [
            'title'   => 'Edycja licencji',
            'license' => $license,
            'members' => $members,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        (new MemberLicenseModel())->update((int)$id, $data);
        Session::flash('success', 'Zapisano.');
        $this->redirect('shooting/licenses');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new MemberLicenseModel())->delete((int)$id);
        Session::flash('success', 'Licencja usunięta.');
        $this->redirect('shooting/licenses');
    }

    private function parsePost(): ?array
    {
        $ids = $this->pzssIds();
        $data = [
            'member_id'      => (int)($_POST['member_id'] ?? 0),
            'sport_id'       => $ids['sport_id'],
            'federation_id'  => $ids['federation_id'],
            'license_type'   => in_array($_POST['license_type'] ?? '', ['zawodnicza','trenerska','sedziowska','klubowa','patent'], true) ? $_POST['license_type'] : 'zawodnicza',
            'license_number' => trim($_POST['license_number'] ?? ''),
            'issue_date'     => trim($_POST['issue_date'] ?? ''),
            'valid_until'    => trim($_POST['valid_until'] ?? ''),
            'qr_code'        => trim($_POST['qr_code'] ?? '') ?: null,
            'status'         => in_array($_POST['status'] ?? '', ['aktywna','wygasla','zawieszona'], true) ? $_POST['status'] : 'aktywna',
            'notes'          => trim($_POST['notes'] ?? '') ?: null,
        ];
        if ($data['member_id'] <= 0 || $data['license_number'] === '' || $data['issue_date'] === '' || $data['valid_until'] === '') {
            Session::flash('error', 'Uzupełnij wymagane pola.');
            $this->redirect('shooting/licenses/create');
            return null;
        }
        return $data;
    }
}
