<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\MemberAuth;
use App\Helpers\RateLimiter;
use App\Helpers\Session;
use App\Models\EventModel;
use App\Models\MedicalExamModel;
use App\Models\MemberIdentityModel;
use App\Models\MemberLicenseModel;
use App\Models\MemberModel;
use App\Models\PaymentModel;
use App\Models\SportHistoryModel;
use App\Models\TrainingModel;

class MemberPortalController extends BaseController
{
    public function showLogin(): void
    {
        if (MemberAuth::check()) {
            if (MemberAuth::isMultiClub() && MemberAuth::clubId() === null) {
                $this->redirect('portal/club-select');
            }
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

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Rate limiting
        if (!RateLimiter::check($ip, 'portal_login')) {
            Session::flash('error', 'Zbyt wiele prób logowania. Spróbuj ponownie za kilka minut.');
            $this->redirect('portal/login');
        }

        if ($email === '' || $password === '') {
            Session::flash('error', 'Podaj e-mail i hasło.');
            $this->redirect('portal/login');
        }

        $identityModel = new MemberIdentityModel();

        // Try unified identity login first
        $identity = $identityModel->findByEmail($email);
        if ($identity && $identityModel->verifyPassword($identity, $password)) {
            RateLimiter::reset($ip, 'portal_login');
            $identityModel->touchLogin((int)$identity['id']);
            MemberAuth::loginIdentity($identity);

            // Check multi-club
            $clubs = $identityModel->clubsForIdentity((int)$identity['id']);
            if (count($clubs) > 1) {
                $this->redirect('portal/club-select');
            }
            $this->redirect('portal/dashboard');
        }

        // Fallback: legacy member login (direct members table)
        $db = Database::pdo();
        $stmt = $db->prepare("SELECT * FROM members WHERE email = ? AND status = 'aktywny' LIMIT 1");
        $stmt->execute([$email]);
        $member = $stmt->fetch();

        if (!$member || !MemberAuth::verifyPassword($member, $password)) {
            RateLimiter::hit($ip, 'portal_login');
            Session::flash('error', 'Nieprawidłowy e-mail lub hasło.');
            $this->redirect('portal/login');
        }

        RateLimiter::reset($ip, 'portal_login');
        MemberAuth::login($member);
        $db->prepare("UPDATE members SET portal_last_login = NOW() WHERE id = ?")->execute([$member['id']]);

        $this->redirect('portal/dashboard');
    }

    public function logout(): void
    {
        MemberAuth::logout();
        $this->redirect('portal/login');
    }

    /**
     * Show club selection page for multi-club identities.
     */
    public function showClubSelect(): void
    {
        MemberAuth::requireLogin();

        $clubs = MemberAuth::currentClubs();

        // Enrich with sport badges
        $db = Database::pdo();
        foreach ($clubs as &$club) {
            $stmt = $db->prepare(
                "SELECT s.name FROM club_sports cs
                 JOIN sports s ON s.id = cs.sport_id
                 WHERE cs.club_id = ? AND cs.is_active = 1
                 ORDER BY s.name LIMIT 5"
            );
            $stmt->execute([(int)$club['id']]);
            $club['sport_badges'] = array_column($stmt->fetchAll(), 'name');
        }
        unset($club);

        $this->view->setLayout('portal_auth');
        $this->view->render('portal/club_select', [
            'title'        => 'Wybierz klub',
            'clubs'        => $clubs,
            'flashError'   => Session::getFlash('error'),
            'flashSuccess' => Session::getFlash('success'),
            'appName'      => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    /**
     * POST: Select a club from multi-club list.
     */
    public function selectClub(string $clubId): void
    {
        Csrf::verify();
        MemberAuth::requireLogin();

        $cid = (int)$clubId;
        if (!MemberAuth::selectClub($cid)) {
            Session::flash('error', 'Nie masz członkostwa w wybranym klubie.');
            $this->redirect('portal/club-select');
        }

        $this->redirect('portal/dashboard');
    }

    public function dashboard(): void
    {
        MemberAuth::requireLogin();

        // If multi-club and no club selected yet, redirect
        if (MemberAuth::isMultiClub() && MemberAuth::clubId() === null) {
            $this->redirect('portal/club-select');
        }

        $member = MemberAuth::member();

        // Payments
        $pm = new PaymentModel();
        $payments = $pm->listForClub((int)$member['id'], (int)date('Y'), 1, 10);
        $totalThisYear = array_sum(array_map(fn($p) => (float)$p['amount'], $payments['data'] ?? []));

        // Medical
        $medical = (new MedicalExamModel())->latestForMember((int)$member['id']);

        // Licenses
        $licenses = (new MemberLicenseModel())
            ->listForClub(null, null, 1, 5)['data'] ?? [];
        $licenses = array_filter($licenses, fn($l) => (int)$l['member_id'] === (int)$member['id']);

        // Upcoming events and trainings
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

    public function sportHistory(): void
    {
        MemberAuth::requireLogin();
        $member  = MemberAuth::member();
        $history = (new SportHistoryModel())->timelineForMember((int)$member['id']);
        $this->view->setLayout('portal');
        $this->view->render('portal/sport_history', [
            'title'   => 'Moja historia sportowa',
            'member'  => $member,
            'history' => $history,
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }
}
