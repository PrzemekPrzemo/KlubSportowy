<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\RateLimiter;
use App\Helpers\Session;
use App\Models\UserModel;

class AuthController extends BaseController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }
        $this->view->setLayout('auth');
        $this->view->render('auth/login', [
            'title' => 'Logowanie',
            'flashError'   => Session::getFlash('error'),
            'flashSuccess' => Session::getFlash('success'),
            'flashWarning' => Session::getFlash('warning'),
            'appName'      => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function login(): void
    {
        Csrf::verify();
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Rate limiting — check before processing
        if (!RateLimiter::check($ip, 'login')) {
            Session::flash('error', 'Zbyt wiele prób logowania. Spróbuj ponownie za kilka minut.');
            $this->redirect('auth/login');
        }

        if ($username === '' || $password === '') {
            Session::flash('error', 'Wprowadź login i hasło.');
            $this->redirect('auth/login');
        }

        $userModel = new UserModel();
        $user = $userModel->findByUsername($username)
             ?? $userModel->findByEmail($username);

        if ($user === null || !$user['is_active'] || !$userModel->verifyPassword($user, $password)) {
            RateLimiter::hit($ip, 'login');
            Session::flash('error', 'Nieprawidłowy login lub hasło.');
            $this->redirect('auth/login');
        }

        // 2FA check — jeśli włączone, przekieruj na verify przed właściwym login
        if (!empty($user['totp_enabled'])) {
            Session::set('totp_pending_user', $user);
            $this->redirect('2fa/verify');
        }

        RateLimiter::reset($ip, 'login');
        Auth::login($user);
        $userModel->touchLastLogin((int)$user['id']);

        // Po logowaniu: jeśli super admin → admin dashboard,
        // jeśli użytkownik ma 1 klub → ustaw i idź do dashboard,
        // jeśli ma >1 klubów → klub-select.
        if (Auth::isSuperAdmin()) {
            $this->redirect('admin/dashboard');
        }

        $clubs = $userModel->getClubsForUser((int)$user['id']);
        if (count($clubs) === 1) {
            Auth::setClub((int)$clubs[0]['club_id'], $clubs[0]['role']);
            $this->redirect('dashboard');
        }
        if (count($clubs) === 0) {
            Session::flash('warning', 'Nie masz przypisanego żadnego klubu. Skontaktuj się z administratorem.');
            Auth::logout();
            $this->redirect('auth/login');
        }
        $this->redirect('club-select');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('auth/login');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }
        $this->view->setLayout('auth');
        $this->view->render('auth/register', [
            'title' => 'Rejestracja klubu',
            'flashError' => Session::getFlash('error'),
            'appName'    => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function register(): void
    {
        Csrf::verify();

        $clubName = trim($_POST['club_name'] ?? '');
        $city     = trim($_POST['city'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($clubName === '' || $email === '' || $username === '' || $password === '' || $fullName === '') {
            Session::flash('error', 'Uzupełnij wszystkie wymagane pola.');
            $this->redirect('register');
        }
        if (strlen($password) < 8) {
            Session::flash('error', 'Hasło musi mieć co najmniej 8 znaków.');
            $this->redirect('register');
        }

        $userModel = new UserModel();
        if ($userModel->findByUsername($username) || $userModel->findByEmail($email)) {
            Session::flash('error', 'Użytkownik o tym loginie lub e-mailu już istnieje.');
            $this->redirect('register');
        }

        $db = \App\Helpers\Database::pdo();
        $db->beginTransaction();
        try {
            $clubModel = new \App\Models\ClubModel();
            $clubId    = $clubModel->insert([
                'name'  => $clubName,
                'city'  => $city,
                'email' => $email,
            ]);
            (new \App\Models\ClubCustomizationModel())->ensureExists($clubId);

            $userId = $userModel->create([
                'username'  => $username,
                'email'     => $email,
                'full_name' => $fullName,
                'password'  => $password,
            ]);

            (new \App\Models\UserClubModel())->grantRole($userId, $clubId, 'zarzad');

            $plan = $db->query("SELECT id FROM subscription_plans WHERE code='trial' LIMIT 1")->fetchColumn();
            if ($plan) {
                $stmt = $db->prepare(
                    "INSERT INTO club_subscriptions (club_id, plan_id, valid_until, status, billing_cycle)
                     VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'trial', 'monthly')"
                );
                $stmt->execute([$clubId, (int)$plan]);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Błąd rejestracji: ' . $e->getMessage());
            $this->redirect('register');
        }

        Session::flash('success', 'Klub został zarejestrowany. Zaloguj się, aby kontynuować.');
        $this->redirect('auth/login');
    }
}
