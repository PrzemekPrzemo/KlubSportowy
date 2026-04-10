<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Models\MemberSportModel;
use App\Models\SportModel;

class MembersController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $q      = trim($_GET['q'] ?? '');
        $status = $_GET['status'] ?? '';
        $sport  = isset($_GET['sport']) ? (int)$_GET['sport'] : null;
        $page   = max(1, (int)($_GET['page'] ?? 1));

        $pagination = (new MemberModel())->search($q, $status ?: null, $sport, $page, 25);
        $clubSports = (new SportModel())->listForClub($this->currentClub());

        $this->render('members/index', [
            'title'       => 'Zawodnicy',
            'pagination'  => $pagination,
            'q'           => $q,
            'status'      => $status,
            'sportFilter' => $sport,
            'clubSports'  => $clubSports,
        ]);
    }

    public function create(): void
    {
        $clubSports = (new SportModel())->listForClub($this->currentClub());
        $next       = (new MemberModel())->nextMemberNumber($this->currentClub());
        $this->render('members/form', [
            'title'      => 'Nowy zawodnik',
            'member'     => null,
            'sports'     => [],
            'clubSports' => $clubSports,
            'nextNumber' => $next,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = $this->parseMemberPost();
        if ($data === null) return;

        $model = new MemberModel();
        $id    = $model->insert($data);

        $this->syncSports($id);

        Session::flash('success', 'Zawodnik dodany.');
        $this->redirect('members/' . $id);
    }

    public function show(string $id): void
    {
        $member = (new MemberModel())->withSports((int)$id);
        if (empty($member)) {
            Session::flash('error', 'Nie znaleziono zawodnika.');
            $this->redirect('members');
        }
        $this->render('members/show', [
            'title'  => $member['first_name'] . ' ' . $member['last_name'],
            'member' => $member,
        ]);
    }

    public function edit(string $id): void
    {
        $member = (new MemberModel())->withSports((int)$id);
        if (empty($member)) {
            Session::flash('error', 'Nie znaleziono zawodnika.');
            $this->redirect('members');
        }
        $clubSports = (new SportModel())->listForClub($this->currentClub());
        $this->render('members/form', [
            'title'      => 'Edycja zawodnika',
            'member'     => $member,
            'sports'     => $member['sports'] ?? [],
            'clubSports' => $clubSports,
            'nextNumber' => $member['member_number'],
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $data = $this->parseMemberPost(false);
        if ($data === null) return;

        $model = new MemberModel();
        $model->update((int)$id, $data);
        $this->syncSports((int)$id);

        Session::flash('success', 'Zapisano zmiany.');
        $this->redirect('members/' . $id);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new MemberModel())->delete((int)$id);
        Session::flash('success', 'Zawodnik usunięty.');
        $this->redirect('members');
    }

    private function parseMemberPost(bool $forCreate = true): ?array
    {
        $data = [
            'member_number'   => trim($_POST['member_number'] ?? ''),
            'first_name'      => trim($_POST['first_name'] ?? ''),
            'last_name'       => trim($_POST['last_name'] ?? ''),
            'pesel'           => trim($_POST['pesel'] ?? '') ?: null,
            'birth_date'      => trim($_POST['birth_date'] ?? '') ?: null,
            'gender'          => in_array($_POST['gender'] ?? '', ['M','K'], true) ? $_POST['gender'] : null,
            'email'           => trim($_POST['email'] ?? '') ?: null,
            'phone'           => trim($_POST['phone'] ?? '') ?: null,
            'address_street'  => trim($_POST['address_street'] ?? '') ?: null,
            'address_city'    => trim($_POST['address_city'] ?? '') ?: null,
            'address_postal'  => trim($_POST['address_postal'] ?? '') ?: null,
            'join_date'       => trim($_POST['join_date'] ?? '') ?: date('Y-m-d'),
            'status'          => in_array($_POST['status'] ?? '', ['aktywny','zawieszony','wykreslony','urlop'], true)
                ? $_POST['status'] : 'aktywny',
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
        ];

        if ($data['first_name'] === '' || $data['last_name'] === '') {
            Session::flash('error', 'Imię i nazwisko są wymagane.');
            $this->redirect($forCreate ? 'members/create' : 'members');
            return null;
        }

        if ($forCreate && $data['member_number'] === '') {
            $data['member_number'] = (new MemberModel())->nextMemberNumber($this->currentClub());
        }

        if ($forCreate) {
            $data['created_by'] = \App\Helpers\Auth::id();
        }

        return $data;
    }

    private function syncSports(int $memberId): void
    {
        $ids = $_POST['club_sport_ids'] ?? [];
        if (!is_array($ids)) return;

        $ms  = new MemberSportModel();
        $db  = \App\Helpers\Database::pdo();
        $stmt = $db->prepare("DELETE FROM member_sports WHERE member_id = ?");
        $stmt->execute([$memberId]);

        foreach ($ids as $csId) {
            $csId = (int)$csId;
            if ($csId > 0) {
                $ms->assign($memberId, $csId);
            }
        }
    }
}
