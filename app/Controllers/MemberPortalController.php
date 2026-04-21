<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\MemberAuth;
use App\Helpers\RateLimiter;
use App\Helpers\Session;
use App\Models\EventModel;
use App\Models\MedicalExamModel;
use App\Models\MemberBeltModel;
use App\Models\MemberConsentModel;
use App\Models\MemberIdentityModel;
use App\Models\MemberLicenseModel;
use App\Models\MemberModel;
use App\Models\MemberNotificationModel;
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
            $offset = max(0, (int)($_GET['week'] ?? 0));
            $from   = (new \DateTime())->modify("+{$offset} weeks")->modify('monday this week')->format('Y-m-d');
            $to     = (new \DateTime($from))->modify('+13 days')->format('Y-m-d');
            $in     = implode(',', array_map('intval', $sportIds));
            $stmt2  = $db->prepare(
                "SELECT t.*, s.name AS sport_name, s.color, u.full_name AS instructor_name
                 FROM trainings t
                 JOIN club_sports cs ON cs.id = t.club_sport_id
                 JOIN sports s ON s.id = cs.sport_id
                 LEFT JOIN users u ON u.id = t.instructor_id
                 WHERE t.club_sport_id IN ({$in})
                   AND DATE(t.start_time) BETWEEN ? AND ?
                   AND t.status != 'odwolany'
                 ORDER BY t.start_time"
            );
            $stmt2->execute([$from, $to]);
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
            case 'swimming':
                $resModel = new \App\Sports\Swimming\Models\SwimmingResultModel();
                $all      = $resModel->listForClub($memberId);
                $data = array_merge($data, [
                    'title'         => 'Pływanie — Mój profil',
                    'personalBests' => $resModel->personalBests($memberId),
                    'recent'        => array_slice($all, 0, 15),
                ]);
                break;
            default:
                Session::flash('error', 'Nieznana sekcja sportowa.');
                $this->redirect('portal/dashboard');
        }

        $this->view->setLayout('portal');
        $this->view->render('portal/sport_' . preg_replace('/[^a-z0-9_]/', '', $key), $data);
    }
}
