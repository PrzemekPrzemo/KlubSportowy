<?php

namespace App\Controllers;

use App\Helpers\ClubBranding;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\WhitelabelSanitizer;
use App\Models\ActivityLogModel;
use App\Models\ClubCustomizationModel;
use App\Models\ClubModel;
use App\Models\ClubSettingsModel;
use App\Models\UserClubModel;
use App\Models\UserModel;

class ClubManagementController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad']);
    }

    public function settings(): void
    {
        $clubId   = $this->currentClub();
        $club     = (new ClubModel())->findById($clubId);
        $settings = (new ClubSettingsModel())->getAll($clubId);
        $this->render('club/settings', [
            'title'    => 'Ustawienia klubu',
            'club'     => $club,
            'settings' => $settings,
        ]);
    }

    public function saveSettings(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'short_name' => trim($_POST['short_name'] ?? '') ?: null,
            'city'       => trim($_POST['city'] ?? '') ?: null,
            'nip'        => trim($_POST['nip'] ?? '') ?: null,
            'regon'      => trim($_POST['regon'] ?? '') ?: null,
            'email'      => trim($_POST['email'] ?? '') ?: null,
            'phone'      => trim($_POST['phone'] ?? '') ?: null,
            'address'    => trim($_POST['address'] ?? '') ?: null,
            'website'    => trim($_POST['website'] ?? '') ?: null,
        ];
        if ($data['name'] === '') {
            Session::flash('error', 'Nazwa klubu jest wymagana.');
            $this->redirect('club/settings');
        }
        (new ClubModel())->update($clubId, $data);
        Session::flash('success', 'Ustawienia zapisane.');
        $this->redirect('club/settings');
    }

    public function customization(): void
    {
        $clubId  = $this->currentClub();
        $custom  = (new ClubCustomizationModel())->findForClub($clubId) ?? ClubCustomizationModel::defaults();
        $this->render('club/customization', [
            'title'  => 'Wygląd i branding',
            'custom' => $custom,
        ]);
    }

    public function saveCustomization(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        $data = [
            'primary_color' => trim($_POST['primary_color'] ?? '#0d6efd'),
            'navbar_bg'     => trim($_POST['navbar_bg'] ?? '#212529'),
            'accent_color'  => trim($_POST['accent_color'] ?? '#198754'),
            'motto'         => trim($_POST['motto'] ?? '') ?: null,
            'subdomain'     => trim($_POST['subdomain'] ?? '') ?: null,
        ];

        // Custom CSS — sanitization OBLIGATORY przy zapisie.
        $cssRaw = (string)($_POST['custom_css'] ?? '');
        if ($cssRaw !== '') {
            $clean = WhitelabelSanitizer::sanitizeCss($cssRaw);
            if ($clean === null) {
                Session::flash('error', 'Custom CSS zostal odrzucony: zawiera niedozwolone konstrukcje (np. <script>, javascript:, expression(), @import).');
                $this->redirect('club/customization');
            }
            $data['custom_css']            = $clean;
            $data['custom_css_updated_at'] = date('Y-m-d H:i:s');
        } else {
            $data['custom_css']            = null;
            $data['custom_css_updated_at'] = null;
        }

        // Upload logo (public/uploads/clubs/{id}/logo_*.ext — serwowane bezpośrednio)
        if (!empty($_FILES['logo']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png','jpg','jpeg','webp','svg','gif'], true)) {
                $dir = ROOT_PATH . '/public/uploads/clubs/' . $clubId;
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $filename = 'logo_' . time() . '.' . $ext;
                $target   = $dir . '/' . $filename;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
                    $data['logo_path'] = 'uploads/clubs/' . $clubId . '/' . $filename;
                }
            }
        }

        (new ClubCustomizationModel())->upsert($clubId, $data);
        ClubBranding::flushCache($clubId);
        Session::flash('success', 'Branding zaktualizowany.');
        $this->redirect('club/customization');
    }

    // ── Whitelabel: favicon upload ───────────────────────────────────────────

    public function uploadFavicon(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();

        if (empty($_FILES['favicon']['tmp_name'])) {
            Session::flash('error', 'Nie wybrano pliku favicon.');
            $this->redirect('club/customization');
        }

        $file = $_FILES['favicon'];
        $err  = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Blad uploadu favicon (kod ' . $err . ').');
            $this->redirect('club/customization');
        }
        if (((int)($file['size'] ?? 0)) > 50 * 1024) {
            Session::flash('error', 'Favicon musi byc mniejszy niz 50 KB.');
            $this->redirect('club/customization');
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            Session::flash('error', 'Nieprawidlowy upload pliku.');
            $this->redirect('club/customization');
        }

        // Sprawdz magic bytes — Content-Type od klienta jest niewiarygodne.
        $fh = @fopen($file['tmp_name'], 'rb');
        if (!$fh) {
            Session::flash('error', 'Nie udalo sie odczytac pliku.');
            $this->redirect('club/customization');
        }
        $bytes = (string)fread($fh, 8);
        fclose($fh);

        $ext = null;
        if (strncmp($bytes, "\x89PNG\r\n\x1a\n", 8) === 0) {
            $ext = 'png';
        } elseif (strncmp($bytes, "\x00\x00\x01\x00", 4) === 0) {
            $ext = 'ico';
        }
        if ($ext === null) {
            Session::flash('error', 'Favicon musi byc plikiem PNG lub ICO (sprawdzono magic bytes).');
            $this->redirect('club/customization');
        }

        // Zapisz do storage/uploads/branding/club_:id_favicon.{ext}
        $dir = ROOT_PATH . '/public/uploads/branding';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            Session::flash('error', 'Nie udalo sie utworzyc katalogu dla favicon.');
            $this->redirect('club/customization');
        }
        $filename = 'club_' . $clubId . '_favicon.' . $ext;
        $absPath  = $dir . '/' . $filename;

        // Usun stary favicon (inne rozszerzenie) jesli istnieje.
        foreach (['png', 'ico'] as $oldExt) {
            $old = $dir . '/club_' . $clubId . '_favicon.' . $oldExt;
            if ($oldExt !== $ext && is_file($old)) @unlink($old);
        }

        if (!@move_uploaded_file($file['tmp_name'], $absPath)) {
            Session::flash('error', 'Nie udalo sie zapisac favicon.');
            $this->redirect('club/customization');
        }

        $relPath = 'uploads/branding/' . $filename;
        (new ClubCustomizationModel())->upsert($clubId, ['favicon_path' => $relPath]);
        ClubBranding::flushCache($clubId);
        (new ActivityLogModel())->log('club_branding_favicon', 'club', $clubId, $relPath);
        Session::flash('success', 'Favicon zaktualizowany.');
        $this->redirect('club/customization');
    }

    public function deleteFavicon(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();

        $row = (new ClubCustomizationModel())->findForClub($clubId);
        if ($row && !empty($row['favicon_path'])) {
            $abs = ROOT_PATH . '/public/' . ltrim((string)$row['favicon_path'], '/');
            if (is_file($abs)) @unlink($abs);
        }
        (new ClubCustomizationModel())->upsert($clubId, ['favicon_path' => null]);
        ClubBranding::flushCache($clubId);
        Session::flash('success', 'Favicon usuniety.');
        $this->redirect('club/customization');
    }

    // ── Whitelabel: custom CSS osobny endpoint ───────────────────────────────

    public function saveCustomCss(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();

        $raw = (string)($_POST['custom_css'] ?? '');
        if (strlen($raw) > 50 * 1024) {
            Session::flash('error', 'Custom CSS przekracza limit 50 KB.');
            $this->redirect('club/customization');
        }

        if (trim($raw) === '') {
            (new ClubCustomizationModel())->upsert($clubId, [
                'custom_css'            => null,
                'custom_css_updated_at' => null,
            ]);
            ClubBranding::flushCache($clubId);
            Session::flash('success', 'Custom CSS wyczyszczony.');
            $this->redirect('club/customization');
        }

        $clean = WhitelabelSanitizer::sanitizeCss($raw);
        if ($clean === null) {
            Session::flash('error', 'CSS zostal odrzucony — zawiera niedozwolone konstrukcje.');
            $this->redirect('club/customization');
        }

        (new ClubCustomizationModel())->upsert($clubId, [
            'custom_css'            => $clean,
            'custom_css_updated_at' => date('Y-m-d H:i:s'),
        ]);
        ClubBranding::flushCache($clubId);
        (new ActivityLogModel())->log('club_branding_css', 'club', $clubId, 'len=' . strlen($clean));
        Session::flash('success', 'Custom CSS zapisany.');
        $this->redirect('club/customization');
    }

    // ── Whitelabel: email header ─────────────────────────────────────────────

    public function saveEmailHeader(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();

        $raw = (string)($_POST['email_header_html'] ?? '');
        if (strlen($raw) > 5000) {
            Session::flash('error', 'Email header przekracza limit 5000 znakow.');
            $this->redirect('club/customization');
        }

        if (trim($raw) === '') {
            (new ClubCustomizationModel())->upsert($clubId, ['email_header_html' => null]);
            ClubBranding::flushCache($clubId);
            Session::flash('success', 'Email header wyczyszczony (uzyty bedzie default).');
            $this->redirect('club/customization');
        }

        $clean = WhitelabelSanitizer::sanitizeEmailHeaderHtml($raw);
        (new ClubCustomizationModel())->upsert($clubId, ['email_header_html' => $clean]);
        ClubBranding::flushCache($clubId);
        Session::flash('success', 'Email header zapisany.');
        $this->redirect('club/customization');
    }

    // ── Whitelabel: communication (from name + SMS sender) ───────────────────

    public function saveCommunication(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();

        $fromName = trim((string)($_POST['email_from_name'] ?? ''));
        $smsId    = strtoupper(trim((string)($_POST['sms_sender_id'] ?? '')));

        $data = [];

        if ($fromName === '') {
            $data['email_from_name'] = null;
        } elseif (strlen($fromName) > 120) {
            Session::flash('error', 'Nazwa nadawcy emaila moze miec max 120 znakow.');
            $this->redirect('club/customization');
        } else {
            // Wytnij znaki niedozwolone w From: header.
            if (preg_match('/[\r\n\0]/', $fromName)) {
                Session::flash('error', 'Nazwa nadawcy zawiera niedozwolone znaki (newline/null).');
                $this->redirect('club/customization');
            }
            $data['email_from_name'] = $fromName;
        }

        if ($smsId === '') {
            $data['sms_sender_id'] = null;
        } elseif (preg_match('/^[A-Z0-9]{1,11}$/', $smsId) !== 1) {
            Session::flash('error', 'SMS sender ID musi byc 1-11 znakow A-Z, 0-9.');
            $this->redirect('club/customization');
        } else {
            $data['sms_sender_id'] = $smsId;
        }

        (new ClubCustomizationModel())->upsert($clubId, $data);
        ClubBranding::flushCache($clubId);
        Session::flash('success', 'Ustawienia komunikacji zapisane.');
        $this->redirect('club/customization');
    }

    public function smtp(): void
    {
        $clubId = $this->currentClub();
        $cs     = new ClubSettingsModel();
        $smtp = [
            'enabled'    => $cs->get($clubId, 'smtp_enabled', '0'),
            'host'       => $cs->get($clubId, 'smtp_host', ''),
            'port'       => $cs->get($clubId, 'smtp_port', '587'),
            'secure'     => $cs->get($clubId, 'smtp_secure', 'tls'),
            'user'       => $cs->get($clubId, 'smtp_user', ''),
            'pass'       => $cs->get($clubId, 'smtp_pass_enc', ''),
            'from_email' => $cs->get($clubId, 'smtp_from_email', ''),
            'from_name'  => $cs->get($clubId, 'smtp_from_name', ''),
        ];
        $sms = [
            'provider' => $cs->get($clubId, 'sms_provider', 'log'),
            'api_key'  => $cs->get($clubId, 'sms_api_key', ''),
            'from'     => $cs->get($clubId, 'sms_from', 'KlubSport'),
        ];
        $this->render('club/smtp', [
            'title' => 'SMTP i SMS',
            'smtp'  => $smtp,
            'sms'   => $sms,
        ]);
    }

    public function saveSmtp(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        $cs     = new ClubSettingsModel();

        $fields = [
            'smtp_enabled'    => ['boolean', isset($_POST['smtp_enabled']) ? '1' : '0'],
            'smtp_host'       => ['text',    trim($_POST['smtp_host'] ?? '')],
            'smtp_port'       => ['number',  (int)($_POST['smtp_port'] ?? 587)],
            'smtp_secure'     => ['text',    trim($_POST['smtp_secure'] ?? 'tls')],
            'smtp_user'       => ['text',    trim($_POST['smtp_user'] ?? '')],
            'smtp_pass_enc'   => ['text',    trim($_POST['smtp_pass_enc'] ?? '')],
            'smtp_from_email' => ['text',    trim($_POST['smtp_from_email'] ?? '')],
            'smtp_from_name'  => ['text',    trim($_POST['smtp_from_name'] ?? '')],
            'sms_provider'    => ['text',    trim($_POST['sms_provider'] ?? 'log')],
            'sms_api_key'     => ['text',    trim($_POST['sms_api_key'] ?? '')],
            'sms_from'        => ['text',    trim($_POST['sms_from'] ?? '')],
        ];
        foreach ($fields as $key => [$type, $value]) {
            $cs->set($clubId, $key, $value, $type);
        }
        Session::flash('success', 'Konfiguracja zapisana.');
        $this->redirect('club/smtp');
    }

    public function users(): void
    {
        $clubId = $this->currentClub();
        $users  = (new UserClubModel())->getForClub($clubId);
        $this->render('club/users', [
            'title' => 'Użytkownicy klubu',
            'users' => $users,
        ]);
    }

    public function addUser(): void
    {
        Csrf::verify();
        $clubId    = $this->currentClub();
        $email     = trim($_POST['email'] ?? '');
        $fullName  = trim($_POST['full_name'] ?? '');
        $role      = in_array($_POST['role'] ?? '', ['zarzad','trener','instruktor','sedzia','lekarz','ksiegowy'], true) ? $_POST['role'] : 'instruktor';
        $password  = $_POST['password'] ?? '';

        if ($email === '' || $fullName === '' || strlen($password) < 8) {
            Session::flash('error', 'Uzupełnij wszystkie pola i ustaw hasło min. 8 znaków.');
            $this->redirect('club/users');
        }

        $userModel = new UserModel();
        $existing  = $userModel->findByEmail($email);
        if ($existing) {
            $userId = (int)$existing['id'];
        } else {
            $userId = $userModel->create([
                'username'  => explode('@', $email)[0] . '_' . bin2hex(random_bytes(2)),
                'email'     => $email,
                'full_name' => $fullName,
                'password'  => $password,
            ]);
        }
        (new UserClubModel())->grantRole($userId, $clubId, $role);
        Session::flash('success', 'Użytkownik dodany do klubu.');
        $this->redirect('club/users');
    }

    public function revokeUser(string $userId): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        $role   = $_POST['role'] ?? '';
        if ($role !== '') {
            (new UserClubModel())->revokeRole((int)$userId, $clubId, $role);
            Session::flash('success', 'Rola odebrana.');
        }
        $this->redirect('club/users');
    }

    /**
     * Cross-sport overview dla zarzadu klubu — top 10 najaktywniejszych
     * czlonkow, rozklad czlonkow per sport + trend rejestracji 12m.
     *
     * GET /admin/clubs/cross-sport-overview
     * Rola: zarzad (sprawdzane w konstruktorze).
     */
    public function crossSportOverview(): void
    {
        $topActive       = \App\Helpers\CrossSportStats::topActiveForClub(10);
        $perSport        = \App\Helpers\CrossSportStats::membersPerSportForClub();
        $registrationTr  = \App\Helpers\CrossSportStats::registrationTrendForClub();

        $this->render('admin/club_management/cross_sport_overview', [
            'title'             => 'Cross-sport overview',
            'topActive'         => $topActive,
            'perSport'          => $perSport,
            'registrationTrend' => $registrationTr,
        ]);
    }
}
