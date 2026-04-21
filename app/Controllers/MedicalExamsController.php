<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MedicalExamModel;
use App\Models\MemberModel;

class MedicalExamsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSensitiveAccess();
    }

    public function index(): void
    {
        $memberId = isset($_GET['member']) ? (int)$_GET['member'] : null;
        $page     = max(1, (int)($_GET['page'] ?? 1));
        \App\Models\SensitiveAccessLogModel::log('medical', 'list', $memberId);
        $pagination = (new MedicalExamModel())->listForClub($memberId, $page, 25);
        $expiring   = (new MedicalExamModel())->expiringSoon(60);

        $this->render('medical/index', [
            'title'      => 'Badania lekarskie',
            'pagination' => $pagination,
            'expiring'   => $expiring,
        ]);
    }

    public function create(): void
    {
        $memberId = isset($_GET['member']) ? (int)$_GET['member'] : null;
        $members  = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $this->render('medical/form', [
            'title'           => 'Nowe badanie',
            'exam'            => null,
            'members'         => $members,
            'preselectMember' => $memberId,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        $data['created_by'] = Auth::id();
        (new MedicalExamModel())->insert($data);
        Session::flash('success', 'Badanie zapisane.');
        $this->redirect('medical');
    }

    public function edit(string $id): void
    {
        $exam = (new MedicalExamModel())->findById((int)$id);
        if (!$exam) {
            Session::flash('error', 'Nie znaleziono badania.');
            $this->redirect('medical');
        }
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('medical/form', [
            'title'   => 'Edycja badania',
            'exam'    => $exam,
            'members' => $members,
            'preselectMember' => (int)$exam['member_id'],
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        (new MedicalExamModel())->update((int)$id, $data);
        Session::flash('success', 'Zapisano.');
        $this->redirect('medical');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new MedicalExamModel())->delete((int)$id);
        Session::flash('success', 'Badanie usunięte.');
        $this->redirect('medical');
    }

    private function parsePost(): ?array
    {
        $data = [
            'member_id'    => (int)($_POST['member_id'] ?? 0),
            'exam_type'    => trim($_POST['exam_type'] ?? 'ogólne badanie sportowe'),
            'exam_date'    => trim($_POST['exam_date'] ?? ''),
            'valid_until'  => trim($_POST['valid_until'] ?? ''),
            'doctor_name'  => trim($_POST['doctor_name'] ?? '') ?: null,
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ];
        if ($data['member_id'] <= 0 || $data['exam_date'] === '' || $data['valid_until'] === '') {
            Session::flash('error', 'Uzupełnij wymagane pola.');
            $this->redirect('medical/create');
            return null;
        }
        return $data;
    }
}
