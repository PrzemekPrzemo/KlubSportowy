<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\MemberAuth;
use App\Helpers\Session;
use App\Models\EventModel;
use App\Models\MedicalExamModel;
use App\Models\MemberLicenseModel;
use App\Models\MemberModel;
use App\Models\PaymentModel;
use App\Models\TrainingModel;

class MemberPortalController extends BaseController
{
    public function showLogin(): void
    {
        if (MemberAuth::check()) {
            $this->redirect('portal/dashboard');
        }
        $this->view->setLayout('portal_auth');
        $this->view->render('portal/login', [
            'title'        => 'Portal zawodnika',
            'flashError'   => Session::getFlash('error'),
            'flashSuccess' => Session::getFlash('success'),
            'appName'      => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function login(): void
    {
        Csrf::verify();
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($email === '' || $password === '') {
            Session::flash('error', 'Podaj e-mail i hasło.');
            $this->redirect('portal/login');
        }

        $db = Database::pdo();
        $stmt = $db->prepare("SELECT * FROM members WHERE email = ? AND status = 'aktywny' LIMIT 1");
        $stmt->execute([$email]);
        $member = $stmt->fetch();

        if (!$member || !MemberAuth::verifyPassword($member, $password)) {
            Session::flash('error', 'Nieprawidłowy e-mail lub hasło.');
            $this->redirect('portal/login');
        }

        MemberAuth::login($member);
        // Aktualizuj portal_last_login
        $db->prepare("UPDATE members SET portal_last_login = NOW() WHERE id = ?")->execute([$member['id']]);

        $this->redirect('portal/dashboard');
    }

    public function logout(): void
    {
        MemberAuth::logout();
        $this->redirect('portal/login');
    }

    public function dashboard(): void
    {
        MemberAuth::requireLogin();
        $member = MemberAuth::member();

        // Zaległości
        $pm = new PaymentModel();
        $payments = $pm->listForClub((int)$member['id'], (int)date('Y'), 1, 10);
        $totalThisYear = array_sum(array_map(fn($p) => (float)$p['amount'], $payments['data'] ?? []));

        // Badania
        $medical = (new MedicalExamModel())->latestForMember((int)$member['id']);

        // Licencje
        $licenses = (new MemberLicenseModel())
            ->listForClub(null, null, 1, 5)['data'] ?? [];
        $licenses = array_filter($licenses, fn($l) => (int)$l['member_id'] === (int)$member['id']);

        // Nadchodzące wydarzenia klubu
        $upcoming = (new EventModel())->upcomingForClub(5);
        $trainings = (new TrainingModel())->upcomingForClub(5);

        $this->view->setLayout('portal');
        $this->view->render('portal/dashboard', [
            'title'         => 'Witaj, ' . $member['first_name'],
            'member'        => $member,
            'payments'      => $payments['data'] ?? [],
            'totalThisYear' => $totalThisYear,
            'medical'       => $medical,
            'licenses'      => $licenses,
            'upcoming'      => $upcoming,
            'trainings'     => $trainings,
            'appName'       => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function profile(): void
    {
        MemberAuth::requireLogin();
        $this->view->setLayout('portal');
        $this->view->render('portal/profile', [
            'title'   => 'Mój profil',
            'member'  => MemberAuth::member(),
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function updateProfile(): void
    {
        MemberAuth::requireLogin();
        Csrf::verify();
        $id = (int)MemberAuth::id();
        $data = [
            'phone'          => trim($_POST['phone'] ?? '') ?: null,
            'address_street' => trim($_POST['address_street'] ?? '') ?: null,
            'address_city'   => trim($_POST['address_city'] ?? '') ?: null,
            'address_postal' => trim($_POST['address_postal'] ?? '') ?: null,
        ];
        (new MemberModel())->withoutScope()->update($id, $data);
        Session::flash('success', 'Dane zaktualizowane.');
        $this->redirect('portal/profile');
    }

    public function changePassword(): void
    {
        MemberAuth::requireLogin();
        Csrf::verify();
        $old  = $_POST['old_password'] ?? '';
        $new1 = $_POST['new_password'] ?? '';
        $new2 = $_POST['new_password2'] ?? '';

        if (strlen($new1) < 8) {
            Session::flash('error', 'Hasło musi mieć co najmniej 8 znaków.');
            $this->redirect('portal/profile');
        }
        if ($new1 !== $new2) {
            Session::flash('error', 'Hasła nie są identyczne.');
            $this->redirect('portal/profile');
        }

        $member = MemberAuth::member();
        if (!MemberAuth::verifyPassword($member, $old)) {
            Session::flash('error', 'Nieprawidłowe bieżące hasło.');
            $this->redirect('portal/profile');
        }

        MemberAuth::setPassword((int)$member['id'], $new1);
        Session::flash('success', 'Hasło zmienione.');
        $this->redirect('portal/profile');
    }

    public function fees(): void
    {
        MemberAuth::requireLogin();
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $pagination = (new PaymentModel())->listForClub((int)MemberAuth::id(), $year, 1, 100);
        $this->view->setLayout('portal');
        $this->view->render('portal/fees', [
            'title'    => 'Moje składki',
            'member'   => MemberAuth::member(),
            'payments' => $pagination['data'] ?? [],
            'year'     => $year,
            'appName'  => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function events(): void
    {
        MemberAuth::requireLogin();
        $upcoming = (new EventModel())->upcomingForClub(30);
        $this->view->setLayout('portal');
        $this->view->render('portal/events', [
            'title'    => 'Nadchodzące wydarzenia',
            'member'   => MemberAuth::member(),
            'upcoming' => $upcoming,
            'appName'  => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }
}
