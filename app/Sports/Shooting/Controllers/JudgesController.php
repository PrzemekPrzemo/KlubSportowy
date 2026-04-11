<?php

namespace App\Sports\Shooting\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Shooting\Models\JudgeLicenseModel;

class JudgesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $judges = (new JudgeLicenseModel())->listForClub();
        $this->render('shooting/judges/index', [
            'title'  => 'Sędziowie PZSS',
            'judges' => $judges,
        ]);
    }

    public function create(): void
    {
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('shooting/judges/form', [
            'title'   => 'Nowa licencja sędziowska',
            'judge'   => null,
            'members' => $members,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        (new JudgeLicenseModel())->insert($data);
        Session::flash('success', 'Licencja sędziowska dodana.');
        $this->redirect('shooting/judges');
    }

    public function edit(string $id): void
    {
        $judge = (new JudgeLicenseModel())->findById((int)$id);
        if (!$judge) {
            Session::flash('error', 'Nie znaleziono.');
            $this->redirect('shooting/judges');
        }
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('shooting/judges/form', [
            'title'   => 'Edycja licencji sędziowskiej',
            'judge'   => $judge,
            'members' => $members,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        (new JudgeLicenseModel())->update((int)$id, $data);
        Session::flash('success', 'Zapisano.');
        $this->redirect('shooting/judges');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new JudgeLicenseModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('shooting/judges');
    }

    private function parsePost(): ?array
    {
        $data = [
            'member_id'      => (int)($_POST['member_id'] ?? 0),
            'class'          => in_array($_POST['class'] ?? '', ['III','II','I','P'], true) ? $_POST['class'] : 'III',
            'license_number' => trim($_POST['license_number'] ?? ''),
            'disciplines'    => trim($_POST['disciplines'] ?? '') ?: null,
            'issue_date'     => trim($_POST['issue_date'] ?? ''),
            'valid_until'    => trim($_POST['valid_until'] ?? ''),
            'status'         => in_array($_POST['status'] ?? '', ['aktywna','wygasla','zawieszona'], true) ? $_POST['status'] : 'aktywna',
            'fee_paid'       => !empty($_POST['fee_paid']) ? (float)$_POST['fee_paid'] : null,
            'notes'          => trim($_POST['notes'] ?? '') ?: null,
        ];
        if ($data['member_id'] <= 0 || $data['license_number'] === '' || $data['issue_date'] === '' || $data['valid_until'] === '') {
            Session::flash('error', 'Uzupełnij wymagane pola.');
            $this->redirect('shooting/judges/create');
            return null;
        }
        return $data;
    }
}
