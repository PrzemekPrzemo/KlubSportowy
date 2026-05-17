<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\GuardianMemberModel;
use App\Models\GuardianModel;
use App\Models\MemberModel;

/**
 * Admin klubu zarzadza opiekunami dziecka.
 * Dostepne dla: zarzad, admin, ksiegowy + super admin (przez requireRole).
 */
class ClubGuardiansController extends BaseController
{
    private function guardAdmin(): int
    {
        $this->requireLogin();
        if (!Auth::isSuperAdmin()) {
            $this->requireRole(['zarzad', 'admin', 'ksiegowy']);
        }
        $clubId = ClubContext::current();
        if ($clubId === null) {
            Session::flash('warning', 'Wybierz klub.');
            $this->redirect('club-select');
        }
        return (int)$clubId;
    }

    public function index(): void
    {
        $clubId    = $this->guardAdmin();
        $guardians = (new GuardianModel())->listForClub($clubId, 1, 200);

        $this->render('club/guardians/all', [
            'title'     => 'Opiekunowie klubu',
            'guardians' => $guardians,
        ]);
    }

    public function forMember(int $memberId): void
    {
        $clubId = $this->guardAdmin();

        $member = (new MemberModel())->findById($memberId); // scoped
        if (!$member) {
            http_response_code(404);
            echo 'Czlonek nie znaleziony.';
            exit;
        }

        $guardians = (new GuardianMemberModel())->forMember($memberId, $clubId);

        $this->render('club/guardians/index', [
            'title'     => 'Opiekunowie: ' . ($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''),
            'member'    => $member,
            'guardians' => $guardians,
        ]);
    }

    public function invite(int $memberId): void
    {
        Csrf::verify();
        $clubId = $this->guardAdmin();

        $member = (new MemberModel())->findById($memberId);
        if (!$member) {
            http_response_code(404);
            echo 'Czlonek nie znaleziony.';
            exit;
        }

        $email     = strtolower(trim((string)($_POST['email'] ?? '')));
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName  = trim((string)($_POST['last_name']  ?? ''));
        $phone     = GuardianModel::sanitizePhone((string)($_POST['phone'] ?? ''));
        $relation  = (string)($_POST['relationship'] ?? 'parent');
        $primary   = !empty($_POST['primary_guardian']);
        $canPay    = isset($_POST['can_pay']);
        $canCons   = isset($_POST['can_consent']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Nieprawidlowy e-mail opiekuna.');
            $this->redirect('club/members/' . $memberId . '/guardians');
        }

        try {
            $gm       = new GuardianModel();
            $invited  = $gm->invite($clubId, $email, $firstName ?: null, $lastName ?: null, $phone);
            $guardian = $invited['guardian'];
            $token    = $invited['token'];

            (new GuardianMemberModel())->linkGuardianToMember(
                (int)$guardian['id'],
                $memberId,
                $clubId,
                $relation,
                $primary,
                $canPay,
                $canCons,
                Auth::id()
            );

            GuardianAuthController::sendInvitation(
                $clubId,
                $guardian,
                $token,
                $member
            );

            Session::flash('success', 'Zaproszenie wyslane do ' . $email);
        } catch (\Throwable $e) {
            Session::flash('error', 'Nie udalo sie wyslac zaproszenia: ' . $e->getMessage());
        }

        $this->redirect('club/members/' . $memberId . '/guardians');
    }

    public function remove(int $guardianMemberId): void
    {
        Csrf::verify();
        $clubId = $this->guardAdmin();

        $stmt = Database::pdo()->prepare(
            "SELECT * FROM guardian_members WHERE id = ? AND club_id = ?"
        );
        $stmt->execute([$guardianMemberId, $clubId]);
        $link = $stmt->fetch();
        if (!$link) {
            http_response_code(404);
            echo 'Link nie znaleziony.';
            exit;
        }

        (new GuardianMemberModel())->unlink($guardianMemberId, $clubId);
        Session::flash('success', 'Opiekun odepiety od dziecka.');
        $this->redirect('club/members/' . (int)$link['member_id'] . '/guardians');
    }
}
