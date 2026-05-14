<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\MemberAuth;
use App\Helpers\RateLimiter;
use App\Helpers\Session;
use App\Models\AntiDopingModel;
use App\Models\EventModel;
use App\Models\IdentitySportMembershipModel;
use App\Models\MedicalExamModel;
use App\Models\MemberBeltModel;
use App\Models\MemberConsentModel;
use App\Models\MemberIdentityModel;
use App\Models\MemberLicenseModel;
use App\Models\MemberModel;
use App\Models\MemberNotificationModel;
use App\Models\ClubPaymentGatewayModel;
use App\Models\MemberFeeAssignmentModel;
use App\Models\MemberNotificationPrefModel;
use App\Models\PaymentDueModel;
use App\Models\PaymentModel;
use App\Models\SportAttendanceModel;
use App\Models\SportHistoryModel;
use App\Models\SportRankingModel;
use App\Models\TournamentModel;
use App\Models\TournamentParticipantModel;
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

        // 2FA challenge — jeśli zawodnik ma włączone, przekieruj do weryfikacji
        if (!empty($member['totp_enabled']) && !empty($member['totp_confirmed_at'])) {
            Session::set('portal_pending_member_id', (int)$member['id']);
            $this->redirect('portal/2fa/verify');
        }

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
     * POST: Switch active sport section for the logged identity.
     * Verifies the membership belongs to the identity and updates session.
     */
    public function switchSection(string $id): void
    {
        Csrf::verify();
        MemberAuth::requireLogin();

        $identityId = MemberAuth::identityId();
        if ($identityId === null) {
            Session::flash('error', 'Switcher sekcji wymaga konta z tozsamoscia (member_identity).');
            $this->redirect('portal/dashboard');
            return;
        }

        $membership = (new IdentitySportMembershipModel())->findActive((int)$id);
        if ($membership === null || (int)$membership['identity_id'] !== $identityId) {
            Session::flash('error', 'Wybrana sekcja nie nalezy do Twojego konta.');
            $this->redirect('portal/dashboard');
            return;
        }

        MemberAuth::setActiveMembership($membership);
        Session::flash('success', sprintf(
            'Aktywna sekcja: %s w klubie %s.',
            $membership['sport_name'] ?? $membership['sport_key'],
            $membership['club_name']
        ));
        $this->redirect('portal/dashboard');
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

        // Active sport keys per klub (filtruje kafelki dashboardu + nav)
        $clubId = MemberAuth::clubId() ?? (int)($member['club_id'] ?? 0);
        $activeSportKeys = $clubId
            ? (new \App\Models\SportModel())->activeKeysForClub($clubId)
            : [];

        $this->view->setLayout('portal');
        $this->view->render('portal/dashboard', [
            'title'           => 'Witaj, ' . $member['first_name'],
            'member'          => $member,
            'payments'        => $payments['data'] ?? [],
            'totalThisYear'   => $totalThisYear,
            'medical'         => $medical,
            'licenses'        => $licenses,
            'upcoming'        => $upcoming,
            'trainings'       => $trainings,
            'activeSportKeys' => $activeSportKeys,
            'appName'         => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
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

    /**
     * Faza P.6 — moje należności (payment_dues) + zniżki + button "Zapłać teraz".
     *
     * Pokazuje:
     *   - aktywne subskrypcje opłat zawodnika z przypisanymi zniżkami
     *   - listę payment_dues (oczekujące, częściowe, przeterminowane, opłacone)
     *   - sumaryczne saldo zaległości
     *   - czy klub ma aktywną bramkę płatności (z P.5) — wpływa na widoczność "Zapłać teraz"
     */
    public function dues(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $today    = date('Y-m-d');

        // Należności (payment_dues)
        $dueModel = new PaymentDueModel();
        $dues     = $dueModel->forMember($memberId);

        // Aktywne subskrypcje + ich zniżki
        $assignModel  = new MemberFeeAssignmentModel();
        $assignments  = $assignModel->activeForMember($memberId, $today);
        $assignmentDiscounts = [];
        foreach ($assignments as $a) {
            $assignmentDiscounts[(int)$a['id']] = $assignModel->discountsForAssignment((int)$a['id']);
        }

        // Saldo: tylko dla bieżącego zawodnika (filtruj manualnie)
        $totalOutstanding = 0.0;
        $totalOverdue     = 0.0;
        foreach ($dues as $d) {
            $remaining = (float)$d['net_amount'] - (float)$d['paid_amount'];
            if (in_array($d['status'], ['pending', 'partial', 'overdue'], true)) {
                $totalOutstanding += $remaining;
                if ($d['status'] === 'overdue' || (in_array($d['status'], ['pending','partial']) && $d['due_date'] < $today)) {
                    $totalOverdue += $remaining;
                }
            }
        }

        // Czy klub ma aktywną bramkę online?
        $hasActiveGateway = (new ClubPaymentGatewayModel())->activeGateway() !== null;

        $this->view->setLayout('portal');
        $this->view->render('portal/dues', [
            'title'             => 'Moje należności',
            'member'            => MemberAuth::member(),
            'dues'              => $dues,
            'assignments'       => $assignments,
            'assignmentDiscounts' => $assignmentDiscounts,
            'totalOutstanding'  => $totalOutstanding,
            'totalOverdue'      => $totalOverdue,
            'hasActiveGateway'  => $hasActiveGateway,
            'statuses'          => PaymentDueModel::$STATUSES,
            'appName'           => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    /**
     * Faza S.2 — portal opt-out z powiadomień (RODO compliance).
     *
     * Zawodnik wybiera per template_type + per channel czy chce dostawać
     * powiadomienia. Domyślnie WSZYSTKO opt-IN (brak rekordu w prefs).
     *
     * Bonus: "Wycisz wszystko" → global opt-out (template_type=NULL).
     */
    public function notificationPrefs(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();

        $prefsModel = new MemberNotificationPrefModel();
        $existingPrefs = $prefsModel->listForMember($memberId);

        // Index po template_type dla łatwego lookupu w widoku
        $prefByTemplate = [];
        $globalOptOut   = false;
        foreach ($existingPrefs as $p) {
            if ($p['template_type'] === null) {
                $globalOptOut = !empty($p['opted_out']);
            } else {
                $prefByTemplate[$p['template_type']] = $p;
            }
        }

        // Lista template'ów dostępnych w klubie + globalnych
        $stmt = \App\Helpers\Database::pdo()->prepare(
            "SELECT DISTINCT template_type, name FROM email_templates
             WHERE club_id IS NULL OR club_id = ?
             ORDER BY template_type"
        );
        $stmt->execute([$clubId]);
        $templates = $stmt->fetchAll();

        // Friendly labels (Polish)
        $templateLabels = [
            'welcome'         => 'Powitanie nowego zawodnika',
            'fee_reminder'    => 'Przypomnienia o składkach',
            'license_expiry'  => 'Wygasające licencje',
            'medical_expiry'  => 'Wygasające badania lekarskie',
            'event_reminder'  => 'Przypomnienia o wydarzeniach',
            'training_reminder' => 'Przypomnienia o treningach',
            'attendance'      => 'Frekwencja',
            'tournament'      => 'Zawody i turnieje',
        ];

        $this->view->setLayout('portal');
        $this->view->render('portal/notification_prefs', [
            'title'           => 'Preferencje powiadomień',
            'member'          => MemberAuth::member(),
            'templates'       => $templates,
            'templateLabels'  => $templateLabels,
            'prefByTemplate'  => $prefByTemplate,
            'globalOptOut'    => $globalOptOut,
            'appName'         => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function updateNotificationPrefs(): void
    {
        MemberAuth::requireLogin();
        \App\Helpers\Csrf::verify();
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();

        $prefsModel = new MemberNotificationPrefModel();

        // Global opt-out: jeśli zaznaczone, ustaw template_type=NULL, opted_out=1
        $globalOff = !empty($_POST['global_opt_out']);
        $prefsModel->setPreference($memberId, $clubId, null, 'both', $globalOff);

        // Per template — array z form: prefs[template_type][opted_out] + [channel]
        if (!$globalOff && !empty($_POST['prefs']) && is_array($_POST['prefs'])) {
            foreach ($_POST['prefs'] as $tpl => $cfg) {
                if (!is_string($tpl) || $tpl === '') continue;
                $tpl = preg_replace('/[^a-z0-9_-]/i', '', $tpl); // sanitize
                if ($tpl === '') continue;

                $optedOut = !empty($cfg['opted_out']);
                $channel  = in_array($cfg['channel'] ?? '', ['email', 'sms', 'both'], true)
                            ? $cfg['channel'] : 'both';
                $prefsModel->setPreference($memberId, $clubId, $tpl, $channel, $optedOut);
            }
        }

        Session::flash('success', 'Preferencje powiadomień zapisane.');
        $this->redirect('portal/notification-prefs');
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

    public function memberCard(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $member   = (new MemberModel())->withoutScope()->withSports($memberId);

        $qrData = 'MEMBER:' . MemberAuth::clubId() . ':' . $memberId . ':' . ($member['member_number'] ?? '');

        $db = Database::pdo();
        $stmt = $db->prepare("SELECT name, logo_path FROM clubs WHERE id = ? LIMIT 1");
        $stmt->execute([(int)MemberAuth::clubId()]);
        $club = $stmt->fetch() ?: ['name' => '', 'logo_path' => null];

        $all = (new MemberLicenseModel())->listForClub(null, null, 1, 50)['data'] ?? [];
        $licenses = array_values(array_filter($all, fn($l) => (int)$l['member_id'] === $memberId && $l['status'] === 'aktywna'));

        $this->view->setLayout('portal');
        $this->view->render('portal/member_card', [
            'title'    => 'Karta zawodnika',
            'member'   => $member,
            'club'     => $club,
            'qrData'   => $qrData,
            'licenses' => $licenses,
            'appName'  => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function uploadPhoto(): void
    {
        MemberAuth::requireLogin();
        Csrf::verify();

        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Błąd przesyłania pliku.');
            $this->redirect('portal/member-card');
        }

        $file  = $_FILES['photo'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if (!isset($allowed[$mime])) {
            Session::flash('error', 'Dozwolone formaty: JPG, PNG, WebP.');
            $this->redirect('portal/member-card');
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            Session::flash('error', 'Plik jest za duży. Maksymalny rozmiar: 2 MB.');
            $this->redirect('portal/member-card');
        }

        $dir = ROOT_PATH . '/public/uploads/member_photos/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'member_' . (int)MemberAuth::id() . '_' . time() . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            Session::flash('error', 'Nie udało się zapisać pliku.');
            $this->redirect('portal/member-card');
        }

        $member = MemberAuth::member();
        if (!empty($member['photo_path'])) {
            $old = ROOT_PATH . '/public/' . ltrim($member['photo_path'], '/');
            if (file_exists($old) && str_contains($old, '/uploads/')) {
                @unlink($old);
            }
        }

        (new MemberModel())->withoutScope()->update((int)MemberAuth::id(), [
            'photo_path' => 'uploads/member_photos/' . $filename,
        ]);
        Session::flash('success', 'Zdjęcie profilowe zaktualizowane.');
        $this->redirect('portal/member-card');
    }

    public function trainingLog(): void
    {
        MemberAuth::requireLogin();
        $memberId  = (int)MemberAuth::id();
        $weekStart = $_GET['week'] ?? date('Y-m-d', strtotime('monday this week'));
        $weekStart = preg_replace('/[^0-9\-]/', '', $weekStart);
        if (strtotime($weekStart) === false) {
            $weekStart = date('Y-m-d', strtotime('monday this week'));
        }

        $model = new \App\Models\AthleteTrainingLogModel();
        $this->view->setLayout('portal');
        $this->view->render('portal/training_log', [
            'title'     => 'Dziennik treningowy',
            'member'    => MemberAuth::member(),
            'weekStart' => $weekStart,
            'weekLogs'  => $model->weekLogs($memberId, $weekStart),
            'weekTotal' => $model->weeklyTotal($memberId, $weekStart),
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function storeTrainingLog(): void
    {
        MemberAuth::requireLogin();
        \App\Helpers\Csrf::verify();
        $memberId = (int)MemberAuth::id();

        $sessionType = array_key_exists($_POST['session_type'] ?? '', \App\Models\AthleteTrainingLogModel::$SESSION_TYPES)
            ? $_POST['session_type'] : 'trening';

        (new \App\Models\AthleteTrainingLogModel())->insert([
            'member_id'    => $memberId,
            'log_date'     => trim($_POST['log_date'] ?? '') ?: date('Y-m-d'),
            'session_type' => $sessionType,
            'sport_key'    => trim($_POST['sport_key'] ?? '') ?: null,
            'duration_min' => !empty($_POST['duration_min']) ? (int)$_POST['duration_min'] : null,
            'distance_km'  => !empty($_POST['distance_km'])  ? (float)$_POST['distance_km'] : null,
            'volume_kg'    => !empty($_POST['volume_kg'])    ? (int)$_POST['volume_kg'] : null,
            'intensity'    => !empty($_POST['intensity'])    ? max(1, min(10, (int)$_POST['intensity'])) : null,
            'avg_hr'       => !empty($_POST['avg_hr'])       ? (int)$_POST['avg_hr'] : null,
            'max_hr'       => !empty($_POST['max_hr'])       ? (int)$_POST['max_hr'] : null,
            'avg_power_w'  => !empty($_POST['avg_power_w'])  ? (int)$_POST['avg_power_w'] : null,
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ]);
        \App\Helpers\Session::flash('success', 'Sesja treningowa zapisana.');
        $this->redirect('portal/training-log');
    }

    public function deleteTrainingLog(string $id): void
    {
        MemberAuth::requireLogin();
        \App\Helpers\Csrf::verify();
        $memberId = (int)MemberAuth::id();
        $model = new \App\Models\AthleteTrainingLogModel();
        $row   = $model->findById((int)$id);
        if ($row && (int)$row['member_id'] === $memberId) {
            $model->delete((int)$id);
            \App\Helpers\Session::flash('success', 'Sesja usunięta.');
        }
        $this->redirect('portal/training-log');
    }

    public function emergencyContacts(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $model    = new \App\Models\EmergencyContactModel();
        $this->view->setLayout('portal');
        $this->view->render('portal/emergency_contacts', [
            'title'         => 'Kontakty awaryjne',
            'member'        => MemberAuth::member(),
            'contacts'      => $model->listForMember($memberId),
            'relationships' => \App\Models\EmergencyContactModel::$RELATIONSHIPS,
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function storeEmergencyContact(): void
    {
        MemberAuth::requireLogin();
        \App\Helpers\Csrf::verify();
        $memberId = (int)MemberAuth::id();
        $name     = trim($_POST['contact_name'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        if ($name === '' || $phone === '') {
            \App\Helpers\Session::flash('error', 'Imię i telefon są wymagane.');
            $this->redirect('portal/emergency-contacts');
        }

        $rel = array_key_exists($_POST['relationship'] ?? '', \App\Models\EmergencyContactModel::$RELATIONSHIPS)
            ? $_POST['relationship'] : 'rodzic';

        $model = new \App\Models\EmergencyContactModel();
        $id = $model->insert([
            'member_id'    => $memberId,
            'contact_name' => $name,
            'relationship' => $rel,
            'phone'        => $phone,
            'phone_alt'    => trim($_POST['phone_alt'] ?? '') ?: null,
            'email'        => trim($_POST['email'] ?? '') ?: null,
            'is_primary'   => isset($_POST['is_primary']) ? 1 : 0,
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ]);
        if (isset($_POST['is_primary'])) {
            $model->setPrimary($memberId, (int)$id);
        }
        \App\Helpers\Session::flash('success', 'Kontakt dodany.');
        $this->redirect('portal/emergency-contacts');
    }

    public function deleteEmergencyContact(string $id): void
    {
        MemberAuth::requireLogin();
        \App\Helpers\Csrf::verify();
        (new \App\Models\EmergencyContactModel())->delete((int)$id);
        \App\Helpers\Session::flash('success', 'Kontakt usunięty.');
        $this->redirect('portal/emergency-contacts');
    }

    public function bodyMetrics(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $model    = new \App\Models\BodyMetricsModel();
        $this->view->setLayout('portal');
        $this->view->render('portal/body_metrics', [
            'title'    => 'Pomiary ciała',
            'member'   => MemberAuth::member(),
            'metrics'  => $model->listForMember($memberId, 100),
            'latest'   => $model->latestForMember($memberId),
            'history'  => $model->weightHistory($memberId, 12),
            'errors'   => Session::getFlash('body_metrics_errors') ?: [],
            'oldInput' => Session::getFlash('body_metrics_input') ?: [],
            'appName'  => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    /**
     * POST /portal/body-metrics — self-entry by zawodnik.
     * Walidacja: waga 20-250 kg, wzrost 100-250 cm, BF 0-70%,
     * HR 30-200 bpm, wingspan 100-260 cm, data nie z przyszlosci.
     */
    public function storeBodyMetrics(): void
    {
        Csrf::verify();
        MemberAuth::requireLogin();

        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();

        $input = [
            'measured_at'  => trim($_POST['measured_at'] ?? '') ?: date('Y-m-d'),
            'weight_kg'    => $_POST['weight_kg']    ?? null,
            'height_cm'    => $_POST['height_cm']    ?? null,
            'body_fat_pct' => $_POST['body_fat_pct'] ?? null,
            'resting_hr'   => $_POST['resting_hr']   ?? null,
            'wingspan_cm'  => $_POST['wingspan_cm']  ?? null,
            'notes'        => trim($_POST['notes']  ?? '') ?: null,
        ];

        $errors = \App\Models\BodyMetricsModel::validate($input);
        if (!empty($errors)) {
            Session::flash('body_metrics_errors', $errors);
            Session::flash('body_metrics_input',  $input);
            $this->redirect('portal/body-metrics');
            return;
        }

        $row = [
            'club_id'     => $clubId,
            'member_id'   => $memberId,
            'measured_at' => $input['measured_at'],
            'weight_kg'   => $input['weight_kg']    !== null && $input['weight_kg']    !== '' ? (float)$input['weight_kg']    : null,
            'height_cm'   => $input['height_cm']    !== null && $input['height_cm']    !== '' ? (int)$input['height_cm']      : null,
            'body_fat_pct'=> $input['body_fat_pct'] !== null && $input['body_fat_pct'] !== '' ? (float)$input['body_fat_pct'] : null,
            'resting_hr'  => $input['resting_hr']   !== null && $input['resting_hr']   !== '' ? (int)$input['resting_hr']     : null,
            'wingspan_cm' => $input['wingspan_cm']  !== null && $input['wingspan_cm']  !== '' ? (int)$input['wingspan_cm']    : null,
            'measured_by' => 'self', // oznaczenie zawodnik wpisal samodzielnie
            'notes'       => $input['notes'],
        ];

        (new \App\Models\BodyMetricsModel())->insert($row);
        Session::flash('success', 'Pomiar zapisany.');
        $this->redirect('portal/body-metrics');
    }

    public function medical(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $exams    = (new MedicalExamModel())->listForClub($memberId, 1, 50)['data'] ?? [];
        $this->view->setLayout('portal');
        $this->view->render('portal/medical', [
            'title'  => 'Moje badania lekarskie',
            'member' => MemberAuth::member(),
            'exams'  => $exams,
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function licenses(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $all      = (new MemberLicenseModel())->listForClub(null, null, 1, 100)['data'] ?? [];
        $licenses = array_values(array_filter($all, fn($l) => (int)$l['member_id'] === $memberId));
        $this->view->setLayout('portal');
        $this->view->render('portal/licenses', [
            'title'    => 'Moje licencje',
            'member'   => MemberAuth::member(),
            'licenses' => $licenses,
            'appName'  => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function consents(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();
        $consents = (new MemberConsentModel())->allForMember($memberId, $clubId);
        $this->view->setLayout('portal');
        $this->view->render('portal/consents', [
            'title'    => 'Moje zgody RODO',
            'member'   => MemberAuth::member(),
            'consents' => $consents,
            'appName'  => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function updateConsent(): void
    {
        MemberAuth::requireLogin();
        Csrf::verify();
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();
        $type     = $_POST['type'] ?? '';
        $granted  = (int)($_POST['granted'] ?? 0) === 1;
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $allowed = ['rodo', 'marketing', 'wizerunek', 'newsletter', 'profilowanie'];
        if (!in_array($type, $allowed, true)) {
            Session::flash('error', 'Nieprawidłowy typ zgody.');
            $this->redirect('portal/consents');
        }

        (new MemberConsentModel())->setConsent($memberId, $clubId, $type, $granted, $ip);
        Session::flash('success', $granted ? 'Zgoda udzielona.' : 'Zgoda wycofana.');
        $this->redirect('portal/consents');
    }

    /**
     * Anti-doping consent declaration (B3) — zawodnik akceptuje regulamin
     * WADA / POLADA i deklaruje zgode na obowiazujace zasady. Prawnie
     * deklaracja musi byc opatrzona data i adresem IP. Dla maloletnich
     * (separate flow przez minor_consents) - opiekun podpisuje na miejscu.
     */
    public function antiDoping(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $current  = (new AntiDopingModel())->forMember($memberId);

        $this->view->setLayout('portal');
        $this->view->render('portal/anti_doping', [
            'title'            => 'Deklaracja anti-doping (WADA)',
            'member'           => MemberAuth::member(),
            'current'          => $current,
            'declarationTypes' => AntiDopingModel::$DECLARATION_TYPES,
            'appName'          => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function storeAntiDoping(): void
    {
        Csrf::verify();
        MemberAuth::requireLogin();

        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();

        if (empty($_POST['confirm_read']) || empty($_POST['confirm_truthful'])) {
            Session::flash('error', 'Musisz potwierdzic zapoznanie sie z regulaminem oraz prawdziwosc oswiadczen.');
            $this->redirect('portal/anti-doping');
            return;
        }

        $type = $_POST['declaration_type'] ?? 'WADA';
        if (!array_key_exists($type, AntiDopingModel::$DECLARATION_TYPES)) {
            $type = 'WADA';
        }

        $signedDate = date('Y-m-d');
        $validUntil = date('Y-m-d', strtotime('+1 year'));
        $ip         = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        (new AntiDopingModel())->insert([
            'club_id'          => $clubId,
            'member_id'        => $memberId,
            'declaration_type' => $type,
            'signed_date'      => $signedDate,
            'valid_until'      => $validUntil,
            'signed_ip'        => $ip,
            'witness'          => 'self', // zawodnik podpisal samodzielnie
            'notes'            => 'Podpisana w portalu zawodnika.',
        ]);

        Session::flash('success', sprintf(
            'Deklaracja %s zlozona. Wazna do %s.',
            $type,
            $validUntil
        ));
        $this->redirect('portal/anti-doping');
    }

    public function announcements(): void
    {
        MemberAuth::requireLogin();
        $clubId = (int)MemberAuth::clubId();
        $db     = Database::pdo();
        $stmt   = $db->prepare(
            "SELECT a.*, u.full_name AS author_name, s.name AS sport_name
             FROM announcements a
             LEFT JOIN users u ON u.id = a.author_id
             LEFT JOIN sports s ON s.id = a.sport_id
             WHERE a.club_id = ?
               AND a.published = 1
               AND a.target IN ('members','all')
               AND (a.publish_from IS NULL OR a.publish_from <= NOW())
               AND (a.publish_to   IS NULL OR a.publish_to   >= NOW())
             ORDER BY a.priority DESC, a.created_at DESC
             LIMIT 30"
        );
        $stmt->execute([$clubId]);
        $announcements = $stmt->fetchAll();
        $this->view->setLayout('portal');
        $this->view->render('portal/announcements', [
            'title'         => 'Ogłoszenia klubu',
            'member'        => MemberAuth::member(),
            'announcements' => $announcements,
            'appName'       => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function schedule(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $db       = Database::pdo();

        // Get member's club_sport_ids
        $stmt = $db->prepare(
            "SELECT ms.club_sport_id, s.name AS sport_name, s.icon, s.color
             FROM member_sports ms
             JOIN club_sports cs ON cs.id = ms.club_sport_id
             JOIN sports s ON s.id = cs.sport_id
             WHERE ms.member_id = ? AND ms.is_active = 1"
        );
        $stmt->execute([$memberId]);
        $memberSports = $stmt->fetchAll();
        $sportIds     = array_column($memberSports, 'club_sport_id');

        $trainings = [];
        if (!empty($sportIds)) {
            $offset  = max(0, (int)($_GET['week'] ?? 0));
            $fromDt  = (new \DateTime())->modify("+{$offset} weeks")->modify('monday this week')->setTime(0, 0, 0);
            $toDt    = (clone $fromDt)->modify('+14 days'); // half-open: 2 weeks
            $fromStr = $fromDt->format('Y-m-d H:i:s');
            $toStr   = $toDt->format('Y-m-d H:i:s');
            $in      = implode(',', array_map('intval', $sportIds));
            // Range predicate on t.start_time (sargable) lets MySQL use
            // composite index trainings(club_sport_id, start_time) added
            // in migration 030_perf_indexes.sql. Wrapping with DATE()
            // would have forced a full scan.
            $stmt2  = $db->prepare(
                "SELECT t.*, s.name AS sport_name, s.color, u.full_name AS instructor_name
                 FROM trainings t
                 JOIN club_sports cs ON cs.id = t.club_sport_id
                 JOIN sports s ON s.id = cs.sport_id
                 LEFT JOIN users u ON u.id = t.instructor_id
                 WHERE t.club_sport_id IN ({$in})
                   AND t.start_time >= ?
                   AND t.start_time <  ?
                   AND t.status != 'odwolany'
                 ORDER BY t.start_time"
            );
            $stmt2->execute([$fromStr, $toStr]);
            $trainings = $stmt2->fetchAll();
        }

        $week = max(0, (int)($_GET['week'] ?? 0));
        $this->view->setLayout('portal');
        $this->view->render('portal/schedule', [
            'title'        => 'Plan treningów',
            'member'       => MemberAuth::member(),
            'trainings'    => $trainings,
            'memberSports' => $memberSports,
            'week'         => $week,
            'appName'      => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function attendance(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $year     = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $model    = new SportAttendanceModel();
        $summary  = $model->memberYearlySummary($memberId, $year);
        $recent   = $model->recentForMember($memberId, 20);
        $this->view->setLayout('portal');
        $this->view->render('portal/attendance', [
            'title'    => 'Moja frekwencja',
            'member'   => MemberAuth::member(),
            'summary'  => $summary,
            'recent'   => $recent,
            'year'     => $year,
            'appName'  => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function results(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $season   = $_GET['season'] ?? null;
        $rankings = (new SportRankingModel())->listForMember($memberId, null);
        if ($season !== null) {
            $rankings = array_filter($rankings, fn($r) => $r['season'] === $season);
        }
        $seasons = (new SportRankingModel())->seasonsForMember($memberId);
        $this->view->setLayout('portal');
        $this->view->render('portal/results', [
            'title'    => 'Moje wyniki i rankingi',
            'member'   => MemberAuth::member(),
            'rankings' => array_values($rankings),
            'seasons'  => $seasons,
            'season'   => $season,
            'appName'  => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    /**
     * Cross-sport stats dashboard — jednolity widok aktywnosci czlonka
     * po wszystkich dyscyplinach (USP multi-sport).
     *
     * GET /portal/dashboard/cross-sport
     */
    public function crossSportDashboard(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();

        $stats = \App\Helpers\CrossSportStats::forMember($memberId);

        $this->view->setLayout('portal');
        $this->view->render('member_portal/cross_sport_dashboard', [
            'title'   => 'Cross-sport stats',
            'member'  => MemberAuth::member(),
            'stats'   => $stats,
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function belts(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();
        $belts    = (new MemberBeltModel())->listForMember($memberId, $clubId);
        $this->view->setLayout('portal');
        $this->view->render('portal/belts', [
            'title'  => 'Moje pasy i stopnie',
            'member' => MemberAuth::member(),
            'belts'  => $belts,
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function notifications(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();
        $model    = new MemberNotificationModel();
        $notifications = $model->unreadForMember($memberId, $clubId, 50);
        $allNotifications = $model->allForMember($memberId, $clubId, 50);
        $this->view->setLayout('portal');
        $this->view->render('portal/notifications', [
            'title'         => 'Powiadomienia',
            'member'        => MemberAuth::member(),
            'notifications' => $allNotifications,
            'unreadCount'   => count($notifications),
            'appName'       => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function markNotificationRead(string $id): void
    {
        MemberAuth::requireLogin();
        Csrf::verify();
        $memberId = (int)MemberAuth::id();
        $model    = new MemberNotificationModel();
        $notif    = $model->find((int)$id);
        if ($notif && (int)$notif['member_id'] === $memberId) {
            $model->markRead((int)$id, $memberId);
            if (!empty($notif['link'])) {
                $this->redirect(ltrim($notif['link'], '/'));
            }
        }
        $this->redirect('portal/notifications');
    }

    public function tournaments(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();
        $db       = Database::pdo();

        $stmt = $db->prepare(
            "SELECT t.*,
                    (SELECT status FROM tournament_participants WHERE tournament_id = t.id AND member_id = ? LIMIT 1) AS my_status
             FROM tournaments t
             WHERE t.club_id = ? AND t.status IN ('planowany','otwarty','w_trakcie','zakonczony')
             ORDER BY t.start_date DESC
             LIMIT 40"
        );
        $stmt->execute([$memberId, $clubId]);
        $tournaments = $stmt->fetchAll();

        $this->view->setLayout('portal');
        $this->view->render('portal/tournaments', [
            'title'       => 'Zawody i turnieje',
            'member'      => MemberAuth::member(),
            'tournaments' => $tournaments,
            'appName'     => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function registerTournament(string $id): void
    {
        MemberAuth::requireLogin();
        Csrf::verify();
        $memberId    = (int)MemberAuth::id();
        $clubId      = (int)MemberAuth::clubId();
        $tournamentId = (int)$id;

        $model = new TournamentParticipantModel();
        [$ok, $msg] = $model->registerMember($tournamentId, $memberId, $clubId);
        Session::flash($ok ? 'success' : 'error', $msg);
        $this->redirect('portal/tournaments');
    }

    public function withdrawTournament(string $id): void
    {
        MemberAuth::requireLogin();
        Csrf::verify();
        $memberId     = (int)MemberAuth::id();
        $tournamentId = (int)$id;

        (new TournamentParticipantModel())->withdrawMember($tournamentId, $memberId);
        Session::flash('success', 'Wycofano zgłoszenie.');
        $this->redirect('portal/tournaments');
    }

    public function sportDetail(string $key): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $member   = MemberAuth::member();
        $appName  = (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy';
        $data     = ['member' => $member, 'appName' => $appName];

        switch ($key) {
            case 'bjj':
                $beltModel  = new \App\Sports\Bjj\Models\BjjBeltModel();
                $resultModel= new \App\Sports\Bjj\Models\BjjResultModel();
                $data = array_merge($data, [
                    'title'         => 'BJJ — Mój profil',
                    'currentBelt'   => $beltModel->currentBelt($memberId),
                    'recentResults' => $resultModel->listForClub($memberId),
                ]);
                break;
            case 'gymnastics':
                $resModel   = new \App\Sports\Gymnastics\Models\GymnasticsResultModel();
                $minorModel = new \App\Sports\Gymnastics\Models\GymnasticsMinorModel();
                $data = array_merge($data, [
                    'title'      => 'Gimnastyka — Mój profil',
                    'myResults'  => $resModel->listForClub(null, $memberId),
                    'consent'    => $minorModel->consentForMember($memberId),
                ]);
                break;
            case 'floorball':
                $teamModel  = new \App\Sports\Floorball\Models\FloorballTeamModel();
                $matchModel = new \App\Sports\Floorball\Models\FloorballMatchModel();
                $myTeam     = $teamModel->playerTeam($memberId);
                $data = array_merge($data, [
                    'title'    => 'Floorball — Mój profil',
                    'myTeam'   => $myTeam,
                    'myStats'  => $matchModel->statsForMember($memberId),
                    'upcoming' => $myTeam ? $matchModel->schedule((int)$myTeam['id'], 'zaplanowany') : [],
                ]);
                break;
            case 'padel':
                $pairModel = new \App\Sports\Padel\Models\PadelPairModel();
                $resModel  = new \App\Sports\Padel\Models\PadelReservationModel();
                $data = array_merge($data, [
                    'title'        => 'Padel — Mój profil',
                    'myPairs'      => $pairModel->pairsForMember($memberId),
                    'reservations' => $resModel->reservationsForMember($memberId),
                ]);
                break;
            case 'sailing':
                $boatModel = new \App\Sports\Sailing\Models\SailingBoatModel();
                $raceModel = new \App\Sports\Sailing\Models\SailingRaceModel();
                $data = array_merge($data, [
                    'title'   => 'Żeglarstwo — Mój profil',
                    'myBoats' => $boatModel->boatsForMember($memberId),
                    'races'   => $raceModel->racesForMember($memberId),
                ]);
                break;
            case 'triathlon':
                $resModel = new \App\Sports\Triathlon\Models\TriathlonResultModel();
                $data = array_merge($data, [
                    'title'   => 'Triathlon — Mój profil',
                    'pbs'     => $resModel->pbsForMember($memberId),
                    'recent'  => $resModel->listForClub($memberId),
                ]);
                break;
            case 'crossfit':
                $prModel  = new \App\Sports\CrossFit\Models\CrossFitPrModel();
                $wodModel = new \App\Sports\CrossFit\Models\CrossFitWodModel();
                $topPrs   = $prModel->topByMember($memberId, 6);
                $recent   = $wodModel->recentForMember($memberId, 5);
                $leaderboard = [];
                foreach ($recent as $s) {
                    $board = $wodModel->leaderboard((int)$s['wod_id'], 20);
                    $pos   = null;
                    foreach ($board as $i => $entry) {
                        if ((int)$entry['member_id'] === $memberId) { $pos = $i + 1; break; }
                    }
                    $leaderboard[] = ['wod_name' => $s['wod_name'], 'position' => $pos, 'score' => $s['score']];
                }
                $data = array_merge($data, [
                    'title'               => 'CrossFit — Mój profil',
                    'topPrs'              => $topPrs,
                    'recentScores'        => $recent,
                    'leaderboardPositions'=> $leaderboard,
                ]);
                break;
            case 'rugby':
                $tModel = new \App\Sports\Rugby\Models\RugbyTeamModel();
                $mModel = new \App\Sports\Rugby\Models\RugbyMatchModel();
                $myTeam = $tModel->playerTeam($memberId);
                $data = array_merge($data, [
                    'title'         => 'Rugby — Mój profil',
                    'myTeam'        => $myTeam,
                    'myStats'       => $mModel->statsForMember($memberId),
                    'recentMatches' => $myTeam ? array_slice($mModel->listForClub((int)$myTeam['id']), 0, 10) : [],
                ]);
                break;
            case 'alpineski':
                $rModel = new \App\Sports\AlpineSki\Models\AlpineSkiResultModel();
                $data = array_merge($data, [
                    'title'     => 'Narciarstwo alpejskie — Mój profil',
                    'myResults' => $rModel->listForClub($memberId),
                    'bestFis'   => $rModel->bestFisPoints($memberId),
                ]);
                break;
            case 'xcski':
                $rModel = new \App\Sports\XcSki\Models\XcSkiResultModel();
                $data = array_merge($data, [
                    'title'     => 'Narciarstwo biegowe — Mój profil',
                    'myResults' => $rModel->listForClub($memberId),
                    'bestFis'   => $rModel->bestFisPoints($memberId),
                ]);
                break;
            case 'skijump':
                $rModel = new \App\Sports\SkiJump\Models\SkiJumpResultModel();
                $data = array_merge($data, [
                    'title'     => 'Skoki narciarskie — Mój profil',
                    'myResults' => $rModel->listForClub($memberId),
                    'longest'   => $rModel->longestJump($memberId),
                ]);
                break;
            case 'snowboard':
                $rModel = new \App\Sports\Snowboard\Models\SnowboardResultModel();
                $data = array_merge($data, [
                    'title'      => 'Snowboard — Mój profil',
                    'myResults'  => $rModel->listForClub($memberId),
                    'bestScores' => $rModel->bestScorePerDiscipline($memberId),
                ]);
                break;
            case 'figureskating':
                $rModel = new \App\Sports\FigureSkating\Models\FigureSkatingResultModel();
                $data = array_merge($data, [
                    'title'     => 'Łyżwiarstwo figurowe — Mój profil',
                    'myResults' => $rModel->listForClub($memberId),
                    'bests'     => $rModel->bestPerDiscipline($memberId),
                ]);
                break;
            case 'biathlon':
                $rModel = new \App\Sports\Biathlon\Models\BiathlonResultModel();
                $data = array_merge($data, [
                    'title'     => 'Biathlon — Mój profil',
                    'myResults' => $rModel->listForClub($memberId),
                    'accuracy'  => $rModel->accuracyStats($memberId),
                ]);
                break;
            case 'kickboxing':
                $bModel = new \App\Sports\Kickboxing\Models\KickboxingBeltModel();
                $rModel = new \App\Sports\Kickboxing\Models\KickboxingResultModel();
                $data = array_merge($data, [
                    'title'       => 'Kickboxing — Mój profil',
                    'currentBelt' => $bModel->currentBelt($memberId),
                    'record'      => $rModel->recordForMember($memberId),
                    'recent'      => $rModel->listForClub($memberId),
                ]);
                break;
            case 'mma':
                $fModel = new \App\Sports\Mma\Models\MmaFighterModel();
                $rModel = new \App\Sports\Mma\Models\MmaResultModel();
                $data = array_merge($data, [
                    'title'      => 'MMA — Mój profil',
                    'fighter'    => $fModel->forMember($memberId),
                    'record'     => $rModel->recordForMember($memberId),
                    'winMethods' => $rModel->winMethods($memberId),
                    'myResults'  => $rModel->listForClub($memberId),
                ]);
                break;
            case 'kayaking':
                $rModel = new \App\Sports\Kayaking\Models\KayakResultModel();
                $data = array_merge($data, [
                    'title'     => 'Kajakarstwo — Mój profil',
                    'myResults' => $rModel->listForClub($memberId),
                    'pbs'       => $rModel->personalBests($memberId),
                ]);
                break;
            case 'golf':
                $hModel = new \App\Sports\Golf\Models\GolfHandicapModel();
                $rModel = new \App\Sports\Golf\Models\GolfRoundModel();
                $data = array_merge($data, [
                    'title'            => 'Golf — Mój profil',
                    'currentHandicap'  => $hModel->currentForMember($memberId),
                    'bestRound'        => $rModel->bestScore($memberId),
                    'myRounds'         => $rModel->listForClub($memberId),
                ]);
                break;
            case 'bridge':
                $pModel = new \App\Sports\Bridge\Models\BridgePartnershipModel();
                $tModel = new \App\Sports\Bridge\Models\BridgeTournamentModel();
                $data = array_merge($data, [
                    'title'          => 'Brydż — Mój profil',
                    'myPartnerships' => $pModel->partnershipsForMember($memberId),
                    'myTournaments'  => $tModel->tournamentsForMember($memberId),
                    'totalPzbs'      => $tModel->totalPzbsPoints($memberId),
                ]);
                break;
            case 'fieldhockey':
                $tModel = new \App\Sports\FieldHockey\Models\FieldHockeyTeamModel();
                $mModel = new \App\Sports\FieldHockey\Models\FieldHockeyMatchModel();
                $myTeam = $tModel->playerTeam($memberId);
                $data = array_merge($data, [
                    'title'         => 'Hokej na trawie — Mój profil',
                    'myTeam'        => $myTeam,
                    'myStats'       => $mModel->statsForMember($memberId),
                    'recentMatches' => $myTeam ? array_slice($mModel->listForClub((int)$myTeam['id']), 0, 10) : [],
                ]);
                break;
            case 'swimming':
                $resModel = new \App\Sports\Swimming\Models\SwimmingResultModel();
                $all      = $resModel->listForClub($memberId);
                $data = array_merge($data, [
                    'title'         => 'Pływanie — Mój profil',
                    'personalBests' => $resModel->personalBests($memberId),
                    'recent'        => array_slice($all, 0, 15),
                ]);
                break;
            case 'climbing':
                $resModel   = new \App\Sports\Climbing\Models\ClimbingResultModel();
                $routeModel = new \App\Sports\Climbing\Models\ClimbingRouteModel();
                $data = array_merge($data, [
                    'title'        => 'Wspinaczka — Mój profil',
                    'myResults'    => array_filter($resModel->listForClub(), fn($r) => (int)$r['member_id'] === $memberId),
                    'activeRoutes' => $routeModel->activeRoutes(),
                ]);
                break;
            case 'weightlifting':
                $rModel = new \App\Sports\Weightlifting\Models\WeightliftingResultModel();
                $data = array_merge($data, [
                    'title'         => 'Podnoszenie ciężarów — Mój profil',
                    'personalBests' => $rModel->personalBests($memberId),
                    'myResults'     => $rModel->listForClub($memberId),
                ]);
                break;
            case 'taekwondo':
                $bModel = new \App\Sports\Taekwondo\Models\TaekwondoBeltModel();
                $rModel = new \App\Sports\Taekwondo\Models\TaekwondoResultModel();
                $allBelts = array_filter($bModel->listForClub(), fn($b) => (int)$b['member_id'] === $memberId);
                $data = array_merge($data, [
                    'title'        => 'Taekwondo — Mój profil',
                    'currentBelt'  => $bModel->currentBelt($memberId),
                    'beltHistory'  => array_values($allBelts),
                    'myResults'    => array_filter($rModel->listForClub(), fn($r) => (int)$r['member_id'] === $memberId),
                ]);
                break;
            case 'fencing':
                $fModel = new \App\Sports\Fencing\Models\FencingFencerModel();
                $rModel = new \App\Sports\Fencing\Models\FencingResultModel();
                $data = array_merge($data, [
                    'title'     => 'Szermierka — Mój profil',
                    'myProfile' => $fModel->forMember($memberId),
                    'myResults' => array_filter($rModel->listForClub(), fn($r) => (int)$r['member_id'] === $memberId),
                ]);
                break;
            case 'icehockey':
                $tModel = new \App\Sports\IceHockey\Models\IceHockeyTeamModel();
                $mModel = new \App\Sports\IceHockey\Models\IceHockeyMatchModel();
                $myTeam = $tModel->playerTeam($memberId);
                $data = array_merge($data, [
                    'title'         => 'Hokej — Mój profil',
                    'myTeam'        => $myTeam,
                    'myStats'       => $mModel->statsForMember($memberId),
                    'recentMatches' => $myTeam ? array_slice($mModel->listForClub((int)$myTeam['id']), 0, 10) : [],
                ]);
                break;
            case 'cycling':
                $ftpModel = new \App\Sports\Cycling\Models\CyclingFtpModel();
                $resModel = new \App\Sports\Cycling\Models\CyclingResultModel();
                $data = array_merge($data, [
                    'title'      => 'Kolarstwo — Mój profil',
                    'latestFtp'  => $ftpModel->latestForMember($memberId),
                    'ftpHistory' => array_slice($ftpModel->listForClub($memberId), 0, 10),
                    'results'    => array_filter($resModel->listForClub(), fn($r) => (int)$r['member_id'] === $memberId),
                ]);
                break;
            case 'handball':
                $tModel = new \App\Sports\Handball\Models\HandballTeamModel();
                $mModel = new \App\Sports\Handball\Models\HandballMatchModel();
                $myTeam = $tModel->playerTeam($memberId);
                $data = array_merge($data, [
                    'title'    => 'Piłka ręczna — Mój profil',
                    'myTeam'   => $myTeam,
                    'myStats'  => $mModel->statsForMember($memberId),
                    'upcoming' => $myTeam ? $mModel->listForClub((int)$myTeam['id'], 'zaplanowany') : [],
                ]);
                break;
            case 'boxing':
                $resModel = new \App\Sports\Boxing\Models\BoxingResultModel();
                $medModel = new \App\Sports\Boxing\Models\BoxingMedicalModel();
                $all      = $resModel->listForClub($memberId);
                $data = array_merge($data, [
                    'title'   => 'Boks — Mój profil',
                    'record'  => $resModel->recordForMember($memberId),
                    'recent'  => array_slice($all, 0, 10),
                    'medical' => $medModel->currentForMember($memberId),
                ]);
                break;
            case 'tennis':
                $matchModel   = new \App\Sports\Tennis\Models\TennisMatchModel();
                $rankingModel = new \App\Sports\Tennis\Models\TennisRankingModel();
                $ranking      = $rankingModel->ranking();
                $entry        = null;
                foreach ($ranking as $r) {
                    if ((int)$r['member_id'] === $memberId) { $entry = $r; break; }
                }
                $data = array_merge($data, [
                    'title'         => 'Tenis — Mój profil',
                    'myMatches'     => $matchModel->listForClub($memberId),
                    'stats'         => $matchModel->statsForMember($memberId),
                    'rankingEntry'  => $entry,
                ]);
                break;
            default:
                // Generic fallback dla sportow bez dedykowanego case-u —
                // uzywa archetypu (z manifestu) i introspekcji INFORMATION_SCHEMA
                // do auto-detekcji co pokazac. Pokrywa wszystkie sporty z
                // archetypem (49/49 po wdrozeniu Fazy I).
                $manifest = \App\Helpers\SportModuleLoader::get($key);
                $archetypeFqcn = $manifest['archetype'] ?? null;
                if (!$archetypeFqcn || !class_exists($archetypeFqcn)) {
                    Session::flash('error', 'Sekcja sportowa nie ma jeszcze widoku w portalu.');
                    $this->redirect('portal/dashboard');
                    return;
                }
                $archetype = new $archetypeFqcn();
                $adapter   = new \App\Helpers\SportPortalAdapter(Database::pdo());
                $clubId    = MemberAuth::clubId();
                $payload   = $adapter->loadForMember($archetype, $memberId, $clubId);
                $data = array_merge($data, $payload);
                $this->view->setLayout('portal');
                $this->view->render('portal/sport_generic', $data);
                return;
        }

        $this->view->setLayout('portal');
        $this->view->render('portal/sport_' . preg_replace('/[^a-z0-9_]/', '', $key), $data);
    }
}
