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
            \App\Models\SecurityEventModel::log('login_failed', [
                'username' => $username,
                'reason'   => $user === null ? 'user_not_found' : (!$user['is_active'] ? 'inactive' : 'bad_password'),
            ]);
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
        \App\Models\SecurityEventModel::log('login_success', [
            'username' => $user['username'],
        ]);

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
        \App\Models\SecurityEventModel::log('logout');
        Auth::logout();
        $this->redirect('auth/login');
    }

    /**
     * Self-registration klubów jest wyłączone.
     *
     * Tylko Master Admin może tworzyć nowe kluby — przez panel
     * /admin/clubs lub /admin/demos (token demo z ograniczonym czasem).
     *
     * Endpoint pozostaje dla kompatybilności linkow w landing page —
     * pokazuje statyczna strone z informacja kontaktowa.
     */
    public function showRegister(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }
        $this->view->setLayout('auth');
        $this->view->render('auth/register_disabled', [
            'title'    => 'Rejestracja niedostępna',
            'appName'  => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
            'contactEmail' => (require ROOT_PATH . '/config/app.php')['admin_email'] ?? 'kontakt@clubdesk.pl',
        ]);
    }

    /**
     * POST /register zablokowany — zwraca 410 Gone.
     * Klub moze byc utworzony tylko przez Master Admina.
     */
    public function register(): void
    {
        http_response_code(410);
        Session::flash('error', 'Rejestracja klubów odbywa się tylko przez Master Administratora. Skontaktuj się z nami aby uruchomić nowy klub.');
        $this->redirect('register');
    }
}
