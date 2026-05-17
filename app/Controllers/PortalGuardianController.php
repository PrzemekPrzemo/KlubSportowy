<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\GuardianAuth;
use App\Helpers\Session;
use App\Models\ClubModel;
use App\Models\GuardianMemberModel;
use App\Models\GuardianMinorConsentModel;
use App\Models\GuardianModel;
use App\Models\MemberModel;

/**
 * Portal opiekuna (rodzica) — RODO art. 8 portal.
 *
 * Bezpieczenstwo:
 *   - kazdy endpoint wymaga GuardianAuth
 *   - kazdy memberId WERYFIKUJEMY przez GuardianMemberModel::isLinked()
 *     z club_id z sesji (defense-in-depth)
 *   - kazdy CSRF token na POST
 */
class PortalGuardianController extends BaseController
{
    private function requireGuardian(): array
    {
        GuardianAuth::requireLogin();
        $g = GuardianAuth::current();
        if (!$g || empty($g['active']) || empty($g['portal_password'])) {
            GuardianAuth::logout();
            Session::flash('error', 'Sesja wygasla.');
            $this->redirect('guardian/login');
        }
        return $g;
    }

    /**
     * Zwraca rekord membera ALE TYLKO gdy opiekun ma do niego prawo.
     * 403 inaczej.
     */
    private function requireChildAccess(int $memberId): array
    {
        $guardian = $this->requireGuardian();
        $clubId   = (int)$guardian['club_id'];

        $gmModel = new GuardianMemberModel();
        if (!$gmModel->isLinked((int)$guardian['id'], $memberId, $clubId)) {
            http_response_code(403);
            echo 'Brak uprawnien do tego dziecka.';
            exit;
        }

        $stmt = Database::pdo()->prepare(
            "SELECT * FROM members WHERE id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([$memberId, $clubId]);
        $member = $stmt->fetch();
        if (!$member) {
            http_response_code(404);
            echo 'Dziecko nie znalezione.';
            exit;
        }
        return ['guardian' => $guardian, 'member' => $member, 'clubId' => $clubId];
    }

    public function dashboard(): void
    {
        $guardian = $this->requireGuardian();
        $clubId   = (int)$guardian['club_id'];

        $children = (new GuardianMemberModel())->forGuardian((int)$guardian['id'], $clubId);

        $childrenSummary = [];
        foreach ($children as $ch) {
            $memberId = (int)$ch['member_id'];
            $unpaid   = $this->countUnpaidDues($memberId, $clubId);
            $childrenSummary[] = [
                'link'         => $ch,
                'unpaid_count' => $unpaid['count'],
                'unpaid_total' => $unpaid['total'],
            ];
        }

        $club = (new ClubModel())->withoutScope()->findById($clubId);

        $this->renderGuardianLayout('portal/guardian/dashboard', [
            'title'    => 'Portal opiekuna',
            'guardian' => $guardian,
            'children' => $childrenSummary,
            'club'     => $club,
        ]);
    }

    public function children(): void
    {
        $guardian = $this->requireGuardian();
        $clubId   = (int)$guardian['club_id'];
        $children = (new GuardianMemberModel())->forGuardian((int)$guardian['id'], $clubId);

        $this->renderGuardianLayout('portal/guardian/children', [
            'title'    => 'Moi podopieczni',
            'guardian' => $guardian,
            'children' => $children,
        ]);
    }

    public function childProfile(int $memberId): void
    {
        $ctx = $this->requireChildAccess($memberId);

        $member = (new MemberModel())->withoutScope()->findById($memberId);
        if (!$member) {
            http_response_code(404);
            echo 'Nie znaleziono.';
            exit;
        }

        $medical = [];
        try {
            $stmt = Database::pdo()->prepare(
                "SELECT * FROM member_medical_exams
                 WHERE member_id = ? AND club_id = ?
                 ORDER BY valid_until DESC LIMIT 5"
            );
            $stmt->execute([$memberId, $ctx['clubId']]);
            $medical = $stmt->fetchAll();
        } catch (\Throwable) {}

        $this->renderGuardianLayout('portal/guardian/child_profile', [
            'title'    => 'Profil ' . ($member['first_name'] ?? ''),
            'guardian' => $ctx['guardian'],
            'member'   => $member,
            'medical'  => $medical,
        ]);
    }

    public function consents(int $memberId): void
    {
        $ctx = $this->requireChildAccess($memberId);

        $consents = (new GuardianMinorConsentModel())->consentsForMember(
            (int)$ctx['guardian']['id'],
            $memberId,
            $ctx['clubId']
        );

        $this->renderGuardianLayout('portal/guardian/consents', [
            'title'    => 'Zgody RODO',
            'guardian' => $ctx['guardian'],
            'member'   => $ctx['member'],
            'consents' => $consents,
            'types'    => GuardianMinorConsentModel::TYPES,
        ]);
    }

    public function updateConsents(int $memberId): void
    {
        Csrf::verify();
        $ctx = $this->requireChildAccess($memberId);

        $link = (new GuardianMemberModel())->findLink(
            (int)$ctx['guardian']['id'],
            $memberId,
            $ctx['clubId']
        );
        if (!$link || empty($link['can_consent'])) {
            Session::flash('error', 'Brak uprawnien do zarzadzania zgodami tego dziecka.');
            $this->redirect('portal/guardian/child/' . $memberId);
        }

        $model = new GuardianMinorConsentModel();
        $ip    = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua    = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $submitted = $_POST['consents'] ?? [];
        if (!is_array($submitted)) $submitted = [];

        foreach (GuardianMinorConsentModel::TYPES as $type) {
            $granted = !empty($submitted[$type]);
            if ($granted) {
                $model->grantConsent(
                    (int)$ctx['guardian']['id'],
                    $memberId,
                    $ctx['clubId'],
                    $type,
                    $ip,
                    $ua
                );
            } else {
                $model->revokeConsent(
                    (int)$ctx['guardian']['id'],
                    $memberId,
                    $ctx['clubId'],
                    $type,
                    $ip,
                    $ua
                );
            }
        }

        Session::flash('success', 'Zgody zaktualizowane.');
        $this->redirect('portal/guardian/child/' . $memberId . '/consents');
    }

    public function childPayments(int $memberId): void
    {
        $ctx = $this->requireChildAccess($memberId);

        $unpaid = [];
        $paid   = [];
        try {
            $stmt = Database::pdo()->prepare(
                "SELECT * FROM payment_dues
                 WHERE member_id = ? AND club_id = ?
                   AND COALESCE(status,'') <> 'paid'
                 ORDER BY due_date ASC"
            );
            $stmt->execute([$memberId, $ctx['clubId']]);
            $unpaid = $stmt->fetchAll();
        } catch (\Throwable) {}

        try {
            $stmt = Database::pdo()->prepare(
                "SELECT * FROM payments
                 WHERE member_id = ? AND club_id = ?
                 ORDER BY paid_date DESC LIMIT 20"
            );
            $stmt->execute([$memberId, $ctx['clubId']]);
            $paid = $stmt->fetchAll();
        } catch (\Throwable) {}

        $this->renderGuardianLayout('portal/guardian/child_payments', [
            'title'    => 'Platnosci',
            'guardian' => $ctx['guardian'],
            'member'   => $ctx['member'],
            'unpaid'   => $unpaid,
            'paid'     => $paid,
        ]);
    }

    public function payForChild(int $memberId): void
    {
        Csrf::verify();
        $ctx  = $this->requireChildAccess($memberId);
        $link = (new GuardianMemberModel())->findLink(
            (int)$ctx['guardian']['id'],
            $memberId,
            $ctx['clubId']
        );
        if (!$link || empty($link['can_pay'])) {
            Session::flash('error', 'Brak uprawnien do platnosci w imieniu tego dziecka.');
            $this->redirect('portal/guardian/child/' . $memberId . '/payments');
        }

        // Faktyczna integracja z GatewayFactory wykracza poza scope MVP —
        // tymczasowy redirect z info dla uzytkownika.
        Session::flash('info', 'Przekierowywanie do bramki platnosci. Funkcja w przygotowaniu — skontaktuj sie z klubem aby dokonac platnosci.');
        $this->redirect('portal/guardian/child/' . $memberId . '/payments');
    }

    public function profile(): void
    {
        $guardian = $this->requireGuardian();
        $this->renderGuardianLayout('portal/guardian/profile', [
            'title'    => 'Moj profil',
            'guardian' => $guardian,
        ]);
    }

    public function updateProfile(): void
    {
        Csrf::verify();
        $guardian = $this->requireGuardian();

        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName  = trim((string)($_POST['last_name']  ?? ''));
        $phone     = GuardianModel::sanitizePhone((string)($_POST['phone'] ?? ''));
        $locale    = (string)($_POST['preferred_locale'] ?? '');

        $stmt = Database::pdo()->prepare(
            "UPDATE guardians SET first_name = ?, last_name = ?, phone = ?, preferred_locale = ?
             WHERE id = ? AND club_id = ?"
        );
        $localeStore = in_array($locale, ['pl', 'en'], true) ? $locale : null;
        $stmt->execute([
            $firstName !== '' ? $firstName : null,
            $lastName  !== '' ? $lastName  : null,
            $phone,
            $localeStore,
            (int)$guardian['id'],
            (int)$guardian['club_id'],
        ]);

        Session::flash('success', 'Profil zaktualizowany.');
        $this->redirect('portal/guardian/profile');
    }

    public function changePassword(): void
    {
        Csrf::verify();
        $guardian = $this->requireGuardian();

        $current = (string)($_POST['current_password'] ?? '');
        $new     = (string)($_POST['new_password']     ?? '');
        $confirm = (string)($_POST['new_password_confirm'] ?? '');

        $model = new GuardianModel();
        if (!$model->verifyPassword($guardian, $current)) {
            Session::flash('error', 'Aktualne haslo jest nieprawidlowe.');
            $this->redirect('portal/guardian/profile');
        }
        if (strlen($new) < 8) {
            Session::flash('error', 'Nowe haslo musi miec co najmniej 8 znakow.');
            $this->redirect('portal/guardian/profile');
        }
        if ($new !== $confirm) {
            Session::flash('error', 'Hasla nie sa identyczne.');
            $this->redirect('portal/guardian/profile');
        }
        $model->setPassword((int)$guardian['id'], $new);
        Session::flash('success', 'Haslo zmienione.');
        $this->redirect('portal/guardian/profile');
    }

    public function help(): void
    {
        $this->redirect('help/parent');
    }

    private function countUnpaidDues(int $memberId, int $clubId): array
    {
        try {
            $stmt = Database::pdo()->prepare(
                "SELECT COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS tot
                 FROM payment_dues
                 WHERE member_id = ? AND club_id = ?
                   AND COALESCE(status,'') <> 'paid'"
            );
            $stmt->execute([$memberId, $clubId]);
            $row = $stmt->fetch();
            return [
                'count' => (int)($row['cnt'] ?? 0),
                'total' => (float)($row['tot'] ?? 0),
            ];
        } catch (\Throwable) {
            return ['count' => 0, 'total' => 0.0];
        }
    }

    private function renderGuardianLayout(string $template, array $data): void
    {
        $data['flashSuccess'] = Session::getFlash('success');
        $data['flashError']   = Session::getFlash('error');
        $data['flashInfo']    = Session::getFlash('info');
        $data['flashWarning'] = Session::getFlash('warning');
        $data['guardian']     = $data['guardian'] ?? GuardianAuth::current();
        $this->view->setLayout('guardian');
        $this->view->render($template, $data);
    }
}
