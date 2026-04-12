<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\EmailService;
use App\Helpers\MemberAuth;
use App\Helpers\Session;

class PasswordResetController extends BaseController
{
    // ================================================================
    // USER (admin/staff) password reset
    // ================================================================

    /** GET /auth/forgot-password */
    public function showForgot(): void
    {
        $this->view->setLayout('auth');
        $this->view->render('auth/forgot_password', [
            'title'        => 'Resetowanie hasla',
            'flashError'   => Session::getFlash('error'),
            'flashSuccess' => Session::getFlash('success'),
            'appName'      => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    /** POST /auth/forgot-password */
    public function sendReset(): void
    {
        Csrf::verify();
        $email = trim($_POST['email'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Podaj prawidlowy adres e-mail.');
            $this->redirect('auth/forgot-password');
        }

        // Always show success to prevent user enumeration
        $db   = Database::pdo();
        $stmt = $db->prepare("SELECT id, email FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token     = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $ins = $db->prepare(
                "INSERT INTO password_resets (email, token_hash, type, expires_at) VALUES (?, ?, 'user', ?)"
            );
            $ins->execute([$email, $tokenHash, $expiresAt]);

            $resetUrl = BASE_URL . '/auth/reset-password/' . $token;
            $body     = "Witaj,\n\nOtrzymalismy prosbe o zresetowanie hasla.\n"
                      . "Kliknij ponizszy link, aby ustawic nowe haslo:\n\n"
                      . $resetUrl . "\n\n"
                      . "Link wygasa za 1 godzine.\n\n"
                      . "Jesli nie prosiles o zmiane hasla, zignoruj te wiadomosc.";

            try {
                EmailService::queue(0, $email, 'Resetowanie hasla - KlubSportowy', $body);
            } catch (\Throwable) {
                // Silently fail — don't reveal whether user exists
            }
        }

        Session::flash('success', 'Jesli podany adres istnieje w systemie, wyslalismy link do resetowania hasla.');
        $this->redirect('auth/forgot-password');
    }

    /** GET /auth/reset-password/:token */
    public function showReset(string $token): void
    {
        $this->validateResetToken($token, 'user');

        $this->view->setLayout('auth');
        $this->view->render('auth/reset_password', [
            'title'        => 'Ustaw nowe haslo',
            'token'        => $token,
            'flashError'   => Session::getFlash('error'),
            'flashSuccess' => Session::getFlash('success'),
            'appName'      => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    /** POST /auth/reset-password */
    public function processReset(): void
    {
        Csrf::verify();
        $token    = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        if ($password === '' || strlen($password) < 8) {
            Session::flash('error', 'Haslo musi miec co najmniej 8 znakow.');
            $this->redirect('auth/reset-password/' . $token);
        }
        if ($password !== $confirm) {
            Session::flash('error', 'Hasla nie sa zgodne.');
            $this->redirect('auth/reset-password/' . $token);
        }

        $reset = $this->validateResetToken($token, 'user');

        $db   = Database::pdo();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hash, $reset['email']]);

        // Mark token as used
        $upd = $db->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?");
        $upd->execute([(int)$reset['id']]);

        Session::flash('success', 'Haslo zostalo zmienione. Mozesz sie teraz zalogowac.');
        $this->redirect('auth/login');
    }

    // ================================================================
    // MEMBER (portal) password reset
    // ================================================================

    /** GET /portal/forgot-password */
    public function showForgotMember(): void
    {
        $this->view->setLayout('auth');
        $this->view->render('portal/forgot_password', [
            'title'        => 'Resetowanie hasla - Portal zawodnika',
            'flashError'   => Session::getFlash('error'),
            'flashSuccess' => Session::getFlash('success'),
            'appName'      => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    /** POST /portal/forgot-password */
    public function sendResetMember(): void
    {
        Csrf::verify();
        $email = trim($_POST['email'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Podaj prawidlowy adres e-mail.');
            $this->redirect('portal/forgot-password');
        }

        $db   = Database::pdo();
        $stmt = $db->prepare("SELECT id, email, club_id FROM members WHERE email = ? AND status = 'aktywny' LIMIT 1");
        $stmt->execute([$email]);
        $member = $stmt->fetch();

        if ($member) {
            $token     = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            $ins = $db->prepare(
                "INSERT INTO password_resets (email, token_hash, type, expires_at) VALUES (?, ?, 'member', ?)"
            );
            $ins->execute([$email, $tokenHash, $expiresAt]);

            $resetUrl = BASE_URL . '/portal/reset-password/' . $token;
            $body     = "Witaj,\n\nOtrzymalismy prosbe o zresetowanie hasla do portalu zawodnika.\n"
                      . "Kliknij ponizszy link, aby ustawic nowe haslo:\n\n"
                      . $resetUrl . "\n\n"
                      . "Link wygasa za 1 godzine.\n\n"
                      . "Jesli nie prosiles o zmiane hasla, zignoruj te wiadomosc.";

            $clubId = (int)($member['club_id'] ?? 0);
            try {
                EmailService::queue($clubId, $email, 'Resetowanie hasla - Portal zawodnika', $body);
            } catch (\Throwable) {}
        }

        Session::flash('success', 'Jesli podany adres istnieje w systemie, wyslalismy link do resetowania hasla.');
        $this->redirect('portal/forgot-password');
    }

    /** GET /portal/reset-password/:token */
    public function showResetMember(string $token): void
    {
        $this->validateResetToken($token, 'member');

        $this->view->setLayout('auth');
        $this->view->render('portal/reset_password', [
            'title'        => 'Ustaw nowe haslo - Portal zawodnika',
            'token'        => $token,
            'flashError'   => Session::getFlash('error'),
            'flashSuccess' => Session::getFlash('success'),
            'appName'      => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    /** POST /portal/reset-password */
    public function processResetMember(): void
    {
        Csrf::verify();
        $token    = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        if ($password === '' || strlen($password) < 8) {
            Session::flash('error', 'Haslo musi miec co najmniej 8 znakow.');
            $this->redirect('portal/reset-password/' . $token);
        }
        if ($password !== $confirm) {
            Session::flash('error', 'Hasla nie sa zgodne.');
            $this->redirect('portal/reset-password/' . $token);
        }

        $reset = $this->validateResetToken($token, 'member');

        $db   = Database::pdo();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE members SET portal_password = ? WHERE email = ?");
        $stmt->execute([$hash, $reset['email']]);

        $upd = $db->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?");
        $upd->execute([(int)$reset['id']]);

        Session::flash('success', 'Haslo zostalo zmienione. Mozesz sie teraz zalogowac.');
        $this->redirect('portal/login');
    }

    // ================================================================
    // Helpers
    // ================================================================

    private function validateResetToken(string $token, string $type): array
    {
        if ($token === '' || strlen($token) !== 64) {
            Session::flash('error', 'Nieprawidlowy lub wygasly link resetowania hasla.');
            $this->redirect($type === 'member' ? 'portal/login' : 'auth/login');
        }

        $tokenHash = hash('sha256', $token);
        $db   = Database::pdo();
        $stmt = $db->prepare(
            "SELECT * FROM password_resets WHERE token_hash = ? AND type = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1"
        );
        $stmt->execute([$tokenHash, $type]);
        $reset = $stmt->fetch();

        if (!$reset) {
            Session::flash('error', 'Nieprawidlowy lub wygasly link resetowania hasla.');
            $this->redirect($type === 'member' ? 'portal/login' : 'auth/login');
        }

        return $reset;
    }
}
