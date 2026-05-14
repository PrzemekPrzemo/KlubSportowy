<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
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
            'custom_css'    => trim($_POST['custom_css'] ?? '') ?: null,
            'motto'         => trim($_POST['motto'] ?? '') ?: null,
            'subdomain'     => trim($_POST['subdomain'] ?? '') ?: null,
        ];

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
        Session::flash('success', 'Branding zaktualizowany.');
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
