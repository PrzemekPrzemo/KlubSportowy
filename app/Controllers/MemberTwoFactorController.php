<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\MemberAuth;
use App\Helpers\RateLimiter;
use App\Helpers\Session;
use App\Helpers\Totp;

class MemberTwoFactorController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /** Portal → ustawienia 2FA (setup). Wymaga już zalogowanego zawodnika. */
    public function setup(): void
    {
        MemberAuth::requireLogin();
        $member = MemberAuth::member();
        if (!$member) { $this->redirect('portal/login'); }

        // Jeśli już włączone → strona backup codes
        if (!empty($member['totp_enabled']) && !empty($member['totp_confirmed_at'])) {
            $this->redirect('portal/2fa/backup-codes');
        }

        // Generuj tymczasowy sekret — zapisz w sesji do czasu potwierdzenia
        $secret = Session::get('portal_pending_totp_secret');
        if (!$secret) {
            $secret = Totp::generateSecret();
            Session::set('portal_pending_totp_secret', $secret);
        }

        $appCfg = require ROOT_PATH . '/config/app.php';
        $issuer = $appCfg['app_name'] ?? 'KlubSportowy';
        $label  = ($member['email'] ?? 'member') . ' (portal)';
        $qrData = Totp::otpauthUrl($secret, $label, $issuer);

        $this->view->setLayout('portal');
        $this->view->render('portal/2fa/setup', [
            'title'  => 'Włącz 2FA — Google Authenticator',
            'secret' => $secret,
            'qrData' => $qrData,
            'member' => $member,
            'appName'=> $issuer,
        ]);
    }

    /** POST: potwierdź sekret (enter 6-cyfrowego kodu po zeskanowaniu QR). */
    public function confirm(): void
    {
        MemberAuth::requireLogin();
        Csrf::verify();
        $code   = trim($_POST['code'] ?? '');
        $secret = Session::get('portal_pending_totp_secret');
        $memberId = (int)MemberAuth::id();

        if (!$secret || !preg_match('/^\d{6}$/', $code)) {
            Session::flash('error', 'Nieprawidłowy format kodu (6 cyfr).');
            $this->redirect('portal/2fa/setup');
        }

        if (!Totp::verifyCode($secret, $code)) {
            Session::flash('error', 'Kod nieprawidłowy. Sprawdź czas urządzenia.');
            $this->redirect('portal/2fa/setup');
        }

        // Zapisz sekret + enable
        $db = Database::pdo();
        $db->prepare(
            "UPDATE members SET totp_enabled = 1, totp_secret = ?, totp_confirmed_at = NOW() WHERE id = ?"
        )->execute([$secret, $memberId]);

        // Generuj 10 kodów zapasowych
        $backups = Totp::generateBackupCodes(10);
        $db->prepare("DELETE FROM member_totp_backup_codes WHERE member_id = ?")->execute([$memberId]);
        $ins = $db->prepare("INSERT INTO member_totp_backup_codes (member_id, code_hash) VALUES (?, ?)");
        foreach ($backups as $b) {
            $ins->execute([$memberId, password_hash($b, PASSWORD_BCRYPT)]);
        }

        Session::forget('portal_pending_totp_secret');
        Session::set('portal_last_backup_codes', $backups); // jednorazowy pokaz
        Session::flash('success', '2FA włączone. Zapisz kody zapasowe.');
        $this->redirect('portal/2fa/backup-codes');
    }

    /** Widok kodów zapasowych — pokaż tylko raz po włączeniu, potem tylko regenerate. */
    public function backupCodes(): void
    {
        MemberAuth::requireLogin();
        $member = MemberAuth::member();
        $codes  = Session::get('portal_last_backup_codes');
        Session::forget('portal_last_backup_codes');

        $this->view->setLayout('portal');
        $this->view->render('portal/2fa/backup_codes', [
            'title'   => 'Kody zapasowe 2FA',
            'member'  => $member,
            'codes'   => $codes,
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    /** POST: regeneruj kody (nadpisuje stare). */
    public function regenerateBackup(): void
    {
        MemberAuth::requireLogin();
        Csrf::verify();
        $memberId = (int)MemberAuth::id();
        $db = Database::pdo();
        $backups = Totp::generateBackupCodes(10);
        $db->prepare("DELETE FROM member_totp_backup_codes WHERE member_id = ?")->execute([$memberId]);
        $ins = $db->prepare("INSERT INTO member_totp_backup_codes (member_id, code_hash) VALUES (?, ?)");
        foreach ($backups as $b) {
            $ins->execute([$memberId, password_hash($b, PASSWORD_BCRYPT)]);
        }
        Session::set('portal_last_backup_codes', $backups);
        Session::flash('success', 'Nowe kody zapasowe wygenerowane.');
        $this->redirect('portal/2fa/backup-codes');
    }

    /** POST: wyłącz 2FA — wymaga hasła. */
    public function disable(): void
    {
        MemberAuth::requireLogin();
        Csrf::verify();
        $password = $_POST['password'] ?? '';
        $member   = MemberAuth::member();
        if (!MemberAuth::verifyPassword($member, $password)) {
            Session::flash('error', 'Błędne hasło.');
            $this->redirect('portal/profile');
        }
        $db = Database::pdo();
        $db->prepare("UPDATE members SET totp_enabled = 0, totp_secret = NULL, totp_confirmed_at = NULL WHERE id = ?")
            ->execute([(int)$member['id']]);
        $db->prepare("DELETE FROM member_totp_backup_codes WHERE member_id = ?")
            ->execute([(int)$member['id']]);
        Session::flash('success', '2FA wyłączone.');
        $this->redirect('portal/profile');
    }

    /** GET: widok weryfikacji po logowaniu (przed pełnym setem sesji). */
    public function verify(): void
    {
        Session::start();
        $pendingMemberId = Session::get('portal_pending_member_id');
        if (!$pendingMemberId) {
            $this->redirect('portal/login');
        }
        $this->view->setLayout('portal_auth');
        $this->view->render('portal/2fa/verify', [
            'title'  => 'Weryfikacja 2FA',
            'appName'=> (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    /** POST: weryfikuj kod i zaloguj członka. */
    public function verifySubmit(): void
    {
        Session::start();
        Csrf::verify();

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!RateLimiter::check($ip, 'portal_2fa_verify')) {
            Session::flash('error', 'Zbyt wiele nieudanych prob. Sprobuj ponownie za kilka minut.');
            $this->redirect('portal/2fa/verify');
        }

        $code = trim($_POST['code'] ?? '');
        $pendingMemberId = Session::get('portal_pending_member_id');
        if (!$pendingMemberId || !preg_match('/^\d{6}$|^[A-F0-9]{8}$/i', $code)) {
            Session::flash('error', 'Kod 6 cyfr lub backup 8 znaków.');
            $this->redirect('portal/2fa/verify');
        }

        $db = Database::pdo();
        $stmt = $db->prepare("SELECT * FROM members WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$pendingMemberId]);
        $member = $stmt->fetch();
        if (!$member || !$member['totp_enabled']) {
            $this->redirect('portal/login');
        }

        $ok = false;
        // 6-digit TOTP?
        if (preg_match('/^\d{6}$/', $code)) {
            $ok = Totp::verifyCode($member['totp_secret'], $code);
        } else {
            // backup code — bcrypt compare
            $codes = $db->prepare("SELECT id, code_hash FROM member_totp_backup_codes WHERE member_id = ? AND used_at IS NULL");
            $codes->execute([(int)$member['id']]);
            foreach ($codes->fetchAll() as $bc) {
                if (password_verify(strtoupper($code), $bc['code_hash'])) {
                    $db->prepare("UPDATE member_totp_backup_codes SET used_at = NOW() WHERE id = ?")
                        ->execute([$bc['id']]);
                    $ok = true;
                    break;
                }
            }
        }

        if (!$ok) {
            RateLimiter::hit($ip, 'portal_2fa_verify');
            Session::flash('error', 'Nieprawidłowy kod.');
            $this->redirect('portal/2fa/verify');
        }
        RateLimiter::reset($ip, 'portal_2fa_verify');

        // Zakończ login
        Session::forget('portal_pending_member_id');
        MemberAuth::login($member);
        $this->redirect('portal/dashboard');
    }
}
