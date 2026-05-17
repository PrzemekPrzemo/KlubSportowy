<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\EmailService;
use App\Helpers\GuardianAuth;
use App\Helpers\RateLimiter;
use App\Helpers\Session;
use App\Models\GuardianModel;

/**
 * Publiczne endpointy autoryzacji opiekuna:
 *   /guardian/login, /guardian/activate/:token, /guardian/forgot-password, /guardian/logout
 */
class GuardianAuthController extends BaseController
{
    public function showLogin(): void
    {
        if (GuardianAuth::check()) {
            $this->redirect('portal/guardian');
        }
        $this->view->setLayout('portal_auth');
        $this->view->render('guardian/auth/login', [
            'title'        => 'Portal opiekuna',
            'flashError'   => Session::getFlash('error'),
            'flashSuccess' => Session::getFlash('success'),
            'appName'      => 'ClubDesk',
        ]);
    }

    public function login(): void
    {
        Csrf::verify();

        $email    = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if (!RateLimiter::check($ip, 'guardian_login', 5, 15)) {
            Session::flash('error', 'Zbyt wiele prob logowania. Sprobuj za kilka minut.');
            $this->redirect('guardian/login');
        }

        if ($email === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Podaj e-mail i haslo.');
            $this->redirect('guardian/login');
        }

        $model    = new GuardianModel();
        $guardian = $model->findByEmailGlobal($email);

        if (!$guardian
            || empty($guardian['active'])
            || empty($guardian['portal_password'])
            || !$model->verifyPassword($guardian, $password)
        ) {
            RateLimiter::hit($ip, 'guardian_login');
            Session::flash('error', 'Nieprawidlowy e-mail lub haslo.');
            $this->redirect('guardian/login');
        }

        RateLimiter::reset($ip, 'guardian_login');
        $model->touchLogin((int)$guardian['id']);
        GuardianAuth::login($guardian);
        $this->redirect('portal/guardian');
    }

    public function showActivate(string $token): void
    {
        $model    = new GuardianModel();
        $guardian = $model->findByActivationToken($token);

        if (!$guardian) {
            $this->view->setLayout('portal_auth');
            $this->view->render('guardian/auth/activate', [
                'title'    => 'Aktywacja konta',
                'error'    => 'Link aktywacyjny jest nieprawidlowy lub wygasl. Skontaktuj sie z klubem aby otrzymac nowe zaproszenie.',
                'token'    => null,
                'guardian' => null,
            ]);
            return;
        }

        $this->view->setLayout('portal_auth');
        $this->view->render('guardian/auth/activate', [
            'title'    => 'Aktywacja konta opiekuna',
            'token'    => $token,
            'guardian' => $guardian,
            'error'    => Session::getFlash('error'),
        ]);
    }

    public function activate(string $token): void
    {
        Csrf::verify();

        $model    = new GuardianModel();
        $guardian = $model->findByActivationToken($token);

        if (!$guardian) {
            Session::flash('error', 'Link aktywacyjny wygasl.');
            $this->redirect('guardian/login');
        }

        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['password_confirm'] ?? '');
        $accept   = isset($_POST['accept_terms']);

        if (!$accept) {
            Session::flash('error', 'Musisz zaakceptowac regulamin i polityke prywatnosci.');
            $this->redirect('guardian/activate/' . $token);
        }
        if (strlen($password) < 8) {
            Session::flash('error', 'Haslo musi miec co najmniej 8 znakow.');
            $this->redirect('guardian/activate/' . $token);
        }
        if ($password !== $confirm) {
            Session::flash('error', 'Hasla nie sa identyczne.');
            $this->redirect('guardian/activate/' . $token);
        }

        $model->activate((int)$guardian['id'], $password);

        $fresh = $model->withoutScope()->findById((int)$guardian['id']);
        if ($fresh) {
            GuardianAuth::login($fresh);
        }

        Session::flash('success', 'Konto aktywowane. Witamy w portalu opiekuna.');
        $this->redirect('portal/guardian');
    }

    public function logout(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Csrf::verify();
        }
        GuardianAuth::logout();
        $this->redirect('guardian/login');
    }

    public function showForgotPassword(): void
    {
        $this->view->setLayout('portal_auth');
        $this->view->render('guardian/auth/forgot_password', [
            'title'        => 'Reset hasla — portal opiekuna',
            'flashError'   => Session::getFlash('error'),
            'flashSuccess' => Session::getFlash('success'),
            'appName'      => 'ClubDesk',
        ]);
    }

    public function sendForgotPassword(): void
    {
        Csrf::verify();
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if (!RateLimiter::check($ip, 'guardian_forgot', 5, 60)) {
            Session::flash('error', 'Zbyt wiele prob. Sprobuj za godzine.');
            $this->redirect('guardian/forgot-password');
        }
        RateLimiter::hit($ip, 'guardian_forgot');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Nieprawidlowy e-mail.');
            $this->redirect('guardian/forgot-password');
        }

        $model    = new GuardianModel();
        $accounts = $model->allByEmail($email);

        foreach ($accounts as $g) {
            try {
                $token   = bin2hex(random_bytes(32));
                $expires = (new \DateTimeImmutable("+1 hour"))->format('Y-m-d H:i:s');
                $stmt    = Database::pdo()->prepare(
                    "UPDATE guardians
                     SET activation_token = ?, activation_token_expires_at = ?
                     WHERE id = ?"
                );
                $stmt->execute([$token, $expires, (int)$g['id']]);

                $clubName = $this->clubName((int)$g['club_id']);
                $link     = url('guardian/activate/' . $token);

                EmailService::queueFromTemplate(
                    (int)$g['club_id'],
                    'guardian_password_reset',
                    $email,
                    [
                        'guardian.first_name' => $g['first_name'] ?? '',
                        'club.name'           => $clubName,
                        'reset_link'          => $link,
                    ]
                );
            } catch (\Throwable) {
                // best-effort
            }
        }

        Session::flash('success', 'Jesli konto istnieje, wyslalismy link resetu hasla.');
        $this->redirect('guardian/forgot-password');
    }

    /**
     * Wysyla email z linkiem aktywacyjnym do opiekuna.
     * Wywolywane z ClubGuardiansController::invite() oraz cli/migrate_legacy_guardian_consents.php.
     */
    public static function sendInvitation(
        int $clubId,
        array $guardian,
        string $token,
        ?array $memberContext = null
    ): bool {
        $link = url('guardian/activate/' . $token);

        $clubName = '';
        try {
            $stmt = Database::pdo()->prepare("SELECT name FROM clubs WHERE id = ?");
            $stmt->execute([$clubId]);
            $clubName = (string)$stmt->fetchColumn();
        } catch (\Throwable) {}

        $vars = [
            'guardian.first_name' => $guardian['first_name'] ?? '',
            'member.first_name'   => $memberContext['first_name'] ?? '',
            'member.last_name'    => $memberContext['last_name']  ?? '',
            'club.name'           => $clubName,
            'activation_link'     => $link,
        ];

        $res = EmailService::queueFromTemplate(
            $clubId,
            'guardian_invitation',
            (string)$guardian['email'],
            $vars
        );
        return $res !== null;
    }

    private function clubName(int $clubId): string
    {
        try {
            $stmt = Database::pdo()->prepare("SELECT name FROM clubs WHERE id = ?");
            $stmt->execute([$clubId]);
            return (string)$stmt->fetchColumn();
        } catch (\Throwable) {
            return '';
        }
    }
}
