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

    /** Ustawia lub resetuje hasło portalu zawodnika. */
    public function setPortalPassword(string $id): void
    {
        Csrf::verify();
        $password = $_POST['portal_password'] ?? '';
        if (strlen($password) < 8) {
            Session::flash('error', 'Hasło musi mieć co najmniej 8 znaków.');
            $this->redirect('members/' . $id);
        }
        \App\Helpers\MemberAuth::setPassword((int)$id, $password);
        Session::flash('success', 'Hasło portalu zawodnika ustawione.');
        $this->redirect('members/' . $id);
    }

    private function parseMemberPost(bool $forCreate = true): ?array
    {
        $redirect = $forCreate ? 'members/create' : 'members';

        $v = \App\Helpers\Validator::make($_POST, [
            'first_name'  => 'required|min:2|max:60',
            'last_name'   => 'required|min:2|max:60',
            'email'       => 'email|max:120',
            'pesel'       => 'pesel',
            'birth_date'  => 'date',
            'phone'       => 'phone',
            'join_date'   => 'required|date',
            'status'      => 'required|in:aktywny,zawieszony,wykreslony,urlop',
        ]);

        if ($v->fails()) {
            Session::flash('error', $v->firstError());
            Session::flash('_old_input', $_POST);
            $this->redirect($redirect);
            return null;
        }

        $clean = $v->validated();
        $data = [
            'member_number'   => trim($_POST['member_number'] ?? ''),
            'first_name'      => $clean['first_name'],
            'last_name'       => $clean['last_name'],
            'pesel'           => $clean['pesel'],
            'birth_date'      => $clean['birth_date'],
            'gender'          => in_array($_POST['gender'] ?? '', ['M','K'], true) ? $_POST['gender'] : null,
            'email'           => $clean['email'],
            'phone'           => $clean['phone'],
            'address_street'  => trim($_POST['address_street'] ?? '') ?: null,
            'address_city'    => trim($_POST['address_city'] ?? '') ?: null,
            'address_postal'  => trim($_POST['address_postal'] ?? '') ?: null,
            'join_date'       => $clean['join_date'],
            'status'          => $clean['status'],
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
        ];

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
