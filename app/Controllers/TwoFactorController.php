<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\RateLimiter;
use App\Helpers\Session;
use App\Helpers\Totp;

class TwoFactorController extends BaseController
{
    public function setup(): void
    {
        $this->requireLogin();
        $db   = Database::pdo();
        $user = $db->prepare("SELECT * FROM users WHERE id = ?");
        $user->execute([Auth::id()]);
        $row = $user->fetch();

        // Generuj i zapisz sekret tymczasowo (w sesji) — potwierdzenie kodem
        if (!Session::has('totp_pending_secret')) {
            Session::set('totp_pending_secret', Totp::generateSecret());
        }
        $secret = Session::get('totp_pending_secret');

        $appCfg = require ROOT_PATH . '/config/app.php';
        $issuer = $appCfg['app_name'] ?? 'KlubSportowy';
        $label  = $row['username'] ?? 'user';
        $url    = Totp::otpauthUrl($secret, $label, $issuer);

        $this->render('2fa/setup', [
            'title'  => 'Konfiguracja 2FA',
            'user'   => $row,
            'secret' => $secret,
            'otpUrl' => $url,
        ]);
    }

    public function confirm(): void
    {
        $this->requireLogin();
        Csrf::verify();

        $secret = Session::get('totp_pending_secret');
        $code   = preg_replace('/\D/', '', $_POST['code'] ?? '');

        if (!$secret || !Totp::verifyCode($secret, $code)) {
            Session::flash('error', 'Nieprawidłowy kod. Spróbuj ponownie.');
            $this->redirect('2fa/setup');
        }

        $db = Database::pdo();
        $stmt = $db->prepare(
            "UPDATE users SET totp_secret = ?, totp_enabled = 1, totp_confirmed_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$secret, Auth::id()]);

        // Wygeneruj kody zapasowe
        $codes = Totp::generateBackupCodes(10);
        foreach ($codes as $c) {
            $hash = password_hash($c, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO totp_backup_codes (user_id, code_hash) VALUES (?, ?)");
            $stmt->execute([Auth::id(), $hash]);
        }

        Session::remove('totp_pending_secret');
        Session::flash('success', '2FA włączone. Zapisz kody zapasowe w bezpiecznym miejscu.');

        $this->render('2fa/backup_codes', [
            'title' => 'Kody zapasowe',
            'codes' => $codes,
        ]);
    }

    public function disable(): void
    {
        $this->requireLogin();
        Csrf::verify();
        $db = Database::pdo();
        $db->prepare("UPDATE users SET totp_enabled = 0, totp_secret = NULL WHERE id = ?")
           ->execute([Auth::id()]);
        $db->prepare("DELETE FROM totp_backup_codes WHERE user_id = ?")->execute([Auth::id()]);
        Session::flash('success', '2FA wyłączone.');
        $this->redirect('dashboard');
    }

    public function verify(): void
    {
        // Ekran weryfikacji po haśle — stan sesji: 'totp_pending_user'
        $pendingUser = Session::get('totp_pending_user');
        if (!$pendingUser) {
            $this->redirect('auth/login');
        }
        $this->view->setLayout('auth');
        $this->view->render('2fa/verify', [
            'title' => 'Weryfikacja 2FA',
            'flashError' => Session::getFlash('error'),
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function verifyCode(): void
    {
        Csrf::verify();
        $pendingUser = Session::get('totp_pending_user');
        if (!$pendingUser) {
            $this->redirect('auth/login');
        }

        // Rate limit: 6-digit TOTP can be brute-forced quickly bez tej ochrony.
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!RateLimiter::check($ip, '2fa_verify')) {
            Session::flash('error', 'Zbyt wiele nieudanych prob. Sprobuj ponownie za kilka minut.');
            $this->redirect('auth/2fa');
        }

        $code = trim($_POST['code'] ?? '');
        $db   = Database::pdo();
        $stmt = $db->prepare("SELECT totp_secret FROM users WHERE id = ?");
        $stmt->execute([$pendingUser['id']]);
        $secret = $stmt->fetchColumn();

        $ok = false;
        if ($secret && Totp::verifyCode($secret, $code)) {
            $ok = true;
        } else {
            // Kod zapasowy?
            $stmt = $db->prepare(
                "SELECT id, code_hash FROM totp_backup_codes
                 WHERE user_id = ? AND used_at IS NULL"
            );
            $stmt->execute([$pendingUser['id']]);
            foreach ($stmt->fetchAll() as $row) {
                if (password_verify($code, $row['code_hash'])) {
                    $ok = true;
                    $db->prepare("UPDATE totp_backup_codes SET used_at = NOW() WHERE id = ?")
                       ->execute([$row['id']]);
                    break;
                }
            }
        }

        if (!$ok) {
            RateLimiter::hit($ip, '2fa_verify');
            Session::flash('error', 'Nieprawidłowy kod.');
            $this->redirect('2fa/verify');
        }
        RateLimiter::reset($ip, '2fa_verify');

        // Dokończ logowanie
        Auth::login($pendingUser);
        Session::remove('totp_pending_user');

        if (!empty($pendingUser['is_super_admin'])) {
            $this->redirect('admin/dashboard');
        }
        $this->redirect('club-select');
    }
}
