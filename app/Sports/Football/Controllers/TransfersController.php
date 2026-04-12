<?php

namespace App\Sports\Football\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Football\Models\FootballTransferModel;

class TransfersController extends BaseController
{
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }

    public function index(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = (new FootballTransferModel())->listForClub($page, 20);
        $this->render('football/transfers/index', ['title' => 'Transfery', 'pagination' => $pagination]);
    }

    public function create(): void
    {
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('football/transfers/form', ['title' => 'Nowy transfer', 'transfer' => null, 'members' => $members]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = [
            'member_id'      => (int)($_POST['member_id'] ?? 0),
            'direction'      => in_array($_POST['direction'] ?? '', ['przychodzacy','odchodzacy','wypozyczenie'], true) ? $_POST['direction'] : 'przychodzacy',
            'from_club'      => trim($_POST['from_club'] ?? '') ?: null,
            'to_club'        => trim($_POST['to_club'] ?? '') ?: null,
            'transfer_date'  => trim($_POST['transfer_date'] ?? ''),
            'fee'            => !empty($_POST['fee']) ? (float)$_POST['fee'] : null,
            'contract_until' => trim($_POST['contract_until'] ?? '') ?: null,
            'notes'          => trim($_POST['notes'] ?? '') ?: null,
            'created_by'     => Auth::id(),
        ];
        if ($data['member_id'] <= 0 || $data['transfer_date'] === '') {
            Session::flash('error', 'Uzupełnij wymagane pola.');
            $this->redirect('football/transfers/create');
        }
        (new FootballTransferModel())->insert($data);
        Session::flash('success', 'Transfer zarejestrowany.');
        $this->redirect('football/transfers');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new FootballTransferModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('football/transfers');
    }
}
