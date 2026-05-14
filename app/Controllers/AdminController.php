<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\ClubCustomizationModel;
use App\Models\ClubModel;
use App\Models\ClubSportModel;
use App\Models\SportModel;
use App\Models\SubscriptionModel;
use App\Models\UserClubModel;
use App\Models\UserModel;

class AdminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    public function dashboard(): void
    {
        $db = Database::pdo();
        $metrics = [
            'clubs'        => (int)$db->query("SELECT COUNT(*) FROM clubs")->fetchColumn(),
            'clubs_active' => (int)$db->query("SELECT COUNT(*) FROM clubs WHERE is_active = 1")->fetchColumn(),
            'users'        => (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'members'      => (int)$db->query("SELECT COUNT(*) FROM members WHERE status='aktywny'")->fetchColumn(),
            'sports'       => (int)$db->query("SELECT COUNT(*) FROM sports WHERE is_active = 1")->fetchColumn(),
            'club_sports'  => (int)$db->query("SELECT COUNT(*) FROM club_sports WHERE is_active = 1")->fetchColumn(),
        ];

        // Revenue trend (last 12 months)
        $revenueTrend = $db->query(
            "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS month, SUM(amount) AS total
             FROM payments
             WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY month ORDER BY month"
        )->fetchAll();

        // Clubs growth (last 12 months)
        $clubsGrowth = $db->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS total
             FROM clubs
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY month ORDER BY month"
        )->fetchAll();

        // Members growth (last 12 months)
        $membersGrowth = $db->query(
            "SELECT DATE_FORMAT(join_date, '%Y-%m') AS month, COUNT(*) AS total
             FROM members
             WHERE join_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY month ORDER BY month"
        )->fetchAll();

        // Total revenue this year
        $revenueThisYear = (float)$db->query(
            "SELECT COALESCE(SUM(amount),0) FROM payments WHERE YEAR(payment_date) = YEAR(CURDATE())"
        )->fetchColumn();

        // Revenue monthly from billing_invoices (last 12 months, paid only)
        $revenueMonthly = $db->query(
            "SELECT DATE_FORMAT(paid_at, '%Y-%m') AS month, SUM(total) AS total
             FROM billing_invoices
             WHERE status = 'paid' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY month ORDER BY month"
        )->fetchAll();

        // Expiring soon: clubs with valid_until <= 7 days from now, not expired/cancelled
        $expiringSoon = $db->query(
            "SELECT c.id, c.name, c.city, cs.valid_until, cs.status, sp.name AS plan_name
             FROM club_subscriptions cs
             JOIN clubs c ON c.id = cs.club_id
             JOIN subscription_plans sp ON sp.id = cs.plan_id
             WHERE cs.valid_until <= DATE_ADD(NOW(), INTERVAL 7 DAY)
               AND cs.status NOT IN ('expired','cancelled')
             ORDER BY cs.valid_until ASC"
        )->fetchAll();

        // Top 10 clubs by active member count
        $topClubs = $db->query(
            "SELECT c.id, c.name, c.city, COALESCE(sp.name, '—') AS plan_name,
                    COUNT(m.id) AS members_count
             FROM clubs c
             LEFT JOIN members m ON m.club_id = c.id AND m.status = 'aktywny'
             LEFT JOIN club_subscriptions cs ON cs.club_id = c.id
             LEFT JOIN subscription_plans sp ON sp.id = cs.plan_id
             GROUP BY c.id, c.name, c.city, sp.name
             ORDER BY members_count DESC
             LIMIT 10"
        )->fetchAll();

        // 5 most recently created clubs
        $recentClubs = $db->query(
            "SELECT id, name, city, created_at
             FROM clubs
             ORDER BY created_at DESC
             LIMIT 5"
        )->fetchAll();

        // Failed invoices: issued but past due
        $failedInvoices = (int)$db->query(
            "SELECT COUNT(*) FROM billing_invoices WHERE status = 'issued' AND due_date < NOW()"
        )->fetchColumn();

        $this->render('admin/global_dashboard', [
            'title'           => 'Panel administratora',
            'metrics'         => $metrics,
            'revenueTrend'    => $revenueTrend,
            'clubsGrowth'     => $clubsGrowth,
            'membersGrowth'   => $membersGrowth,
            'revenueThisYear' => $revenueThisYear,
            'revenueMonthly'  => $revenueMonthly,
            'expiringSoon'    => $expiringSoon,
            'topClubs'        => $topClubs,
            'recentClubs'     => $recentClubs,
            'failedInvoices'  => $failedInvoices,
        ]);
    }

    public function clubs(): void
    {
        $q    = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $data = (new ClubModel())->search($q, $page, 20);

        $this->render('admin/clubs', [
            'title'      => 'Kluby',
            'pagination' => $data,
            'q'          => $q,
        ]);
    }

    public function createClub(): void
    {
        $this->render('admin/club_form', [
            'title' => 'Nowy klub',
            'club'  => null,
        ]);
    }

    public function storeClub(): void
    {
        Csrf::verify();
        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'short_name' => trim($_POST['short_name'] ?? '') ?: null,
            'city'       => trim($_POST['city'] ?? '') ?: null,
            'nip'        => trim($_POST['nip'] ?? '') ?: null,
            'email'      => trim($_POST['email'] ?? '') ?: null,
            'phone'      => trim($_POST['phone'] ?? '') ?: null,
            'address'    => trim($_POST['address'] ?? '') ?: null,
            'is_active'  => 1,
        ];
        if ($data['name'] === '') {
            Session::flash('error', 'Nazwa klubu jest wymagana.');
            $this->redirect('admin/clubs/create');
        }
        $model  = new ClubModel();
        $clubId = $model->insert($data);
        (new ClubCustomizationModel())->ensureExists($clubId);

        // Default trial subscription — Q.1: trial_v2 (nowy kod), fallback na 'trial' (legacy)
        $db   = Database::pdo();
        $plan = $db->query(
            "SELECT id FROM subscription_plans
              WHERE code IN ('trial_v2','trial') AND is_active = 1
              ORDER BY (code='trial_v2') DESC, id ASC
              LIMIT 1"
        )->fetchColumn();
        if ($plan) {
            $stmt = $db->prepare(
                "INSERT INTO club_subscriptions (club_id, plan_id, valid_until, status, billing_cycle)
                 VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'trial', 'monthly')"
            );
            $stmt->execute([$clubId, (int)$plan]);
        }

        Session::flash('success', 'Klub utworzony.');
        $this->redirect('admin/clubs');
    }

    public function editClub(string $id): void
    {
        $club = (new ClubModel())->findById((int)$id);
        if (!$club) {
            Session::flash('error', 'Nie znaleziono klubu.');
            $this->redirect('admin/clubs');
        }
        $this->render('admin/club_form', [
            'title' => 'Edycja klubu',
            'club'  => $club,
        ]);
    }

    public function updateClub(string $id): void
    {
        Csrf::verify();
        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'short_name' => trim($_POST['short_name'] ?? '') ?: null,
            'city'       => trim($_POST['city'] ?? '') ?: null,
            'nip'        => trim($_POST['nip'] ?? '') ?: null,
            'email'      => trim($_POST['email'] ?? '') ?: null,
            'phone'      => trim($_POST['phone'] ?? '') ?: null,
            'address'    => trim($_POST['address'] ?? '') ?: null,
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
        ];
        (new ClubModel())->update((int)$id, $data);
        Session::flash('success', 'Zapisano zmiany.');
        $this->redirect('admin/clubs');
    }

    /**
     * Strona potwierdzenia usuwania klubu — pokazuje statystyki tego co
     * zostanie usuniete + wymaga wpisania nazwy klubu jako confirmation.
     */
    public function confirmDeleteClub(string $id): void
    {
        $club = (new ClubModel())->findById((int)$id);
        if (!$club) {
            Session::flash('error', 'Nie znaleziono klubu.');
            $this->redirect('admin/clubs');
        }

        // Zbierz statystyki — co dokladnie zostanie usuniete
        $pdo = Database::pdo();
        $clubId = (int)$id;
        $stats = [];
        // Lista tabel z FK club_id ktore CASCADE'uja
        $tables = [
            'members'                    => 'Czlonkowie',
            'club_users'                 => 'Uzytkownicy admin',
            'club_subscriptions'         => 'Subskrypcje',
            'club_payment_gateways'      => 'Bramki platnosci',
            'club_shipping_providers'    => 'Konfiguracja InPost',
            'shipments'                  => 'Przesylki',
            'club_federation_credentials'=> 'Credentiale federacji',
            'club_google_calendar'       => 'Google Calendar',
            'club_addons'                => 'Dodatki (addons)',
            'fee_assignments'            => 'Przydzialy skladek',
            'events'                     => 'Wydarzenia',
            'trainings'                  => 'Treningi',
            'tournaments'                => 'Turnieje',
            'sport_rankings'             => 'Rankingi sportowe',
            'announcements'              => 'Ogloszenia',
            'live_channels'              => 'Kanaly live',
            'club_feature_overrides'     => 'Feature overrides',
            'club_customization'         => 'Customizacja brandingu',
        ];
        foreach ($tables as $table => $label) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE club_id = ?");
                $stmt->execute([$clubId]);
                $cnt = (int)$stmt->fetchColumn();
                if ($cnt > 0) {
                    $stats[$table] = ['label' => $label, 'count' => $cnt];
                }
            } catch (\Throwable) {
                // tabela nie istnieje lub brak kolumny club_id — pomin
            }
        }

        $this->render('admin/club_delete', [
            'title' => 'Usun klub: ' . ($club['name'] ?? ''),
            'club'  => $club,
            'stats' => $stats,
        ]);
    }

    /**
     * Wykonanie nieodwracalnego usuniecia klubu wraz z danymi.
     *
     * Wymagania bezpieczenstwa:
     * - requireSuperAdmin (w __construct)
     * - CSRF token
     * - Type-in confirmation: nazwa klubu musi zgadzac sie znak w znak
     * - Audit log do `tenant_access_log` (severity=critical) PRZED delete
     * - Opcjonalny backup przez `cli/backup_club.php` PRZED delete
     *
     * Cascade DB (FK ON DELETE CASCADE) usuwa 99% danych powiazanych.
     * Pozostale (uploaded files w storage/) czyscimy oddzielnie po DB tx.
     */
    public function deleteClub(string $id): void
    {
        Csrf::verify();
        $clubId = (int)$id;
        $club = (new ClubModel())->findById($clubId);
        if (!$club) {
            Session::flash('error', 'Nie znaleziono klubu.');
            $this->redirect('admin/clubs');
        }

        // Type-in confirmation: nazwa musi zgadzac sie dokladnie
        $confirmName = trim($_POST['confirm_name'] ?? '');
        if ($confirmName !== ($club['name'] ?? '')) {
            Session::flash('error',
                'Nazwa klubu nie zgadza sie — wpisana wartosc musi byc identyczna z "'
                . ($club['name'] ?? '') . '". Anulowano.'
            );
            $this->redirect('admin/clubs/' . $clubId . '/delete');
        }

        // Audit log PRZED delete — gdy delete sie powiedzie, nie bedzie sie juz logu
        // mozna stworzyc (FK constraint), wiec robimy to wczesniej
        try {
            (new \App\Models\TenantAccessLogModel())->logBypass(
                tableName:  'clubs',
                operation:  'delete_club',
                callerFile: __FILE__,
                callerLine: __LINE__,
                callerClass: self::class,
                severity:   'critical',
                notes:      'club_id=' . $clubId . ' name=' . substr((string)$club['name'], 0, 80)
            );
        } catch (\Throwable) {
            // non-blocking
        }

        // Opcjonalny backup PRZED usunieciem (zalecane)
        $backupOk = null;
        if (!empty($_POST['backup_first'])) {
            $backupOk = $this->triggerClubBackup($clubId);
        }

        // Wykonaj DELETE — cascade w DB usunie powiazane rekordy
        try {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("DELETE FROM clubs WHERE id = ?");
            $stmt->execute([$clubId]);
            $deleted = $stmt->rowCount();
        } catch (\Throwable $e) {
            Session::flash('error', 'Blad usuwania klubu z bazy: ' . $e->getMessage());
            $this->redirect('admin/clubs/' . $clubId . '/delete');
        }

        if ($deleted === 0) {
            Session::flash('error', 'Klub nie zostal usuniety (rowCount=0). Sprawdz logi.');
            $this->redirect('admin/clubs');
        }

        // Cleanup uploaded files po udanym DB delete
        $this->cleanupClubFiles($clubId);

        $msg = 'Klub "' . ($club['name'] ?? '') . '" zostal usuniety wraz z danymi.';
        if ($backupOk === true) {
            $msg .= ' Backup zapisany w storage/backups/.';
        } elseif ($backupOk === false) {
            $msg .= ' UWAGA: backup nie powiodl sie — sprawdz storage/logs/backup_*.log.';
        }
        Session::flash('success', $msg);
        $this->redirect('admin/clubs');
    }

    /**
     * Trigger asynchronously `cli/backup_club.php <id>` (non-blocking).
     * Zwraca true gdy udalo sie zakolejkowac, false gdy nie.
     */
    private function triggerClubBackup(int $clubId): bool
    {
        $phpBin = PHP_BINARY;
        $script = ROOT_PATH . '/cli/backup_club.php';
        $logDir = ROOT_PATH . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $logFile = $logDir . '/backup_predelete_club_' . $clubId . '_' . date('Ymd_His') . '.log';
        // Synchronously — backup_club.php uzywa mysqldump per tabela, zwykle <60s nawet dla duzych klubow
        $cmd = escapeshellcmd($phpBin) . ' ' . escapeshellarg($script) . ' ' . (int)$clubId
             . ' > ' . escapeshellarg($logFile) . ' 2>&1';
        $exitCode = null;
        @exec($cmd, $output, $exitCode);
        return ($exitCode === 0);
    }

    /**
     * Usuwa pliki uploadow zwiazane z klubem (logo, favicon, branding, dokumenty).
     * Wywolac PO DB delete.
     */
    private function cleanupClubFiles(int $clubId): void
    {
        // Per-club folder uploadow
        $folders = [
            ROOT_PATH . '/storage/uploads/club_' . $clubId,
            ROOT_PATH . '/public/uploads/club_' . $clubId,
        ];
        foreach ($folders as $folder) {
            if (is_dir($folder)) {
                $this->rmdirRecursive($folder);
            }
        }
        // Glob branding files z prefiksem club_<id>_
        $patterns = [
            ROOT_PATH . '/public/uploads/branding/club_' . $clubId . '_*',
            ROOT_PATH . '/storage/uploads/branding/club_' . $clubId . '_*',
        ];
        foreach ($patterns as $pattern) {
            foreach (glob($pattern) ?: [] as $f) {
                if (is_file($f)) @unlink($f);
            }
        }
    }

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) return;
        $entries = @scandir($dir) ?: [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->rmdirRecursive($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function switchClub(string $id): void
    {
        Csrf::verify();
        $clubId = (int)$id;
        $club = (new ClubModel())->findById($clubId);
        if (!$club) {
            Session::flash('error', 'Nie znaleziono klubu.');
            $this->redirect('admin/clubs');
        }
        \App\Helpers\Auth::switchToClubContext($clubId, 'zarzad');
        Session::flash('success', 'Przełączono kontekst na klub: ' . $club['name']);
        $this->redirect('dashboard');
    }

    public function sportsCatalog(): void
    {
        $sports = (new SportModel())->listActive();
        $this->render('admin/sports', [
            'title'  => 'Katalog sportów',
            'sports' => $sports,
        ]);
    }

    public function plans(): void
    {
        $plans = (new SubscriptionModel())->listPlans();
        $this->render('admin/plans', [
            'title' => 'Plany subskrypcyjne',
            'plans' => $plans,
        ]);
    }

    public function activityLog(): void
    {
        $clubId = isset($_GET['club_id']) && $_GET['club_id'] !== '' ? (int)$_GET['club_id'] : null;
        $userId = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int)$_GET['user_id'] : null;
        $action = $_GET['action'] ?? null;
        $from   = $_GET['from'] ?? null;
        $to     = $_GET['to'] ?? null;
        $page   = max(1, (int)($_GET['page'] ?? 1));

        $model = new \App\Models\ActivityLogModel();
        $pagination = $model->listFiltered($clubId, $userId, $action, $from, $to, $page, 50);

        $clubs = (new ClubModel())->findAll('name', 'ASC');

        $this->render('admin/activity_log', [
            'title'      => 'Log aktywności',
            'pagination' => $pagination,
            'clubs'      => $clubs,
            'actions'    => $model->distinctActions(50),
            'filter'     => compact('clubId', 'userId', 'action', 'from', 'to'),
        ]);
    }

    public function clubUsers(string $id): void
    {
        $clubId = (int)$id;
        $club   = (new ClubModel())->findById($clubId);
        if (!$club) {
            Session::flash('error', 'Nie znaleziono klubu.');
            $this->redirect('admin/clubs');
        }
        $users = (new UserClubModel())->getForClub($clubId);
        $this->render('admin/club_users', [
            'title' => 'Użytkownicy klubu: ' . $club['name'],
            'club'  => $club,
            'users' => $users,
        ]);
    }

    public function impersonate(string $id, string $userId): void
    {
        Csrf::verify();
        $clubId     = (int)$id;
        $targetId   = (int)$userId;
        $userClub   = new UserClubModel();
        $roles      = $userClub->rolesForUserInClub($targetId, $clubId);
        if (empty($roles)) {
            Session::flash('error', 'Użytkownik nie ma roli w tym klubie.');
            $this->redirect('admin/clubs/' . $clubId . '/users');
        }
        $db   = Database::pdo();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$targetId]);
        $target = $stmt->fetch();
        if (!$target) {
            Session::flash('error', 'Użytkownik nie istnieje.');
            $this->redirect('admin/clubs/' . $clubId . '/users');
        }
        \App\Helpers\Auth::impersonateClubUser($target, $clubId, $roles[0]);
        (new \App\Models\ActivityLogModel())->log('impersonate_start', 'user', $targetId, 'club=' . $clubId);
        Session::flash('success', 'Impersonujesz: ' . $target['full_name']);
        $this->redirect('dashboard');
    }

    // ── BLOK 2A: Extended admin panel ────────────────────────

    /**
     * GET: Mega-form for creating club with all related data.
     */
    public function createClubFull(): void
    {
        $sports = (new SportModel())->listActive();
        $plans  = (new SubscriptionModel())->listPlans();

        $this->render('admin/club_form_full', [
            'title'  => 'Nowy klub (pełny formularz)',
            'club'   => null,
            'sports' => $sports,
            'plans'  => $plans,
            'clubSportIds' => [],
            'subscription' => null,
        ]);
    }

    /**
     * POST: Create club + customization + sports + subscription + admin user in one transaction.
     */
    public function storeClubFull(): void
    {
        Csrf::verify();
        $db = Database::pdo();

        $clubName = trim($_POST['name'] ?? '');
        if ($clubName === '') {
            Session::flash('error', 'Nazwa klubu jest wymagana.');
            $this->redirect('admin/clubs/create-full');
        }

        $db->beginTransaction();
        try {
            // 1. Create club
            $clubData = [
                'name'       => $clubName,
                'short_name' => trim($_POST['short_name'] ?? '') ?: null,
                'city'       => trim($_POST['city'] ?? '') ?: null,
                'nip'        => trim($_POST['nip'] ?? '') ?: null,
                'email'      => trim($_POST['club_email'] ?? '') ?: null,
                'phone'      => trim($_POST['club_phone'] ?? '') ?: null,
                'address'    => trim($_POST['address'] ?? '') ?: null,
                'is_active'  => 1,
            ];
            $clubId = (new ClubModel())->insert($clubData);

            // 2. Club customization
            (new ClubCustomizationModel())->ensureExists($clubId);

            // 3. Sports
            $sportIds = $_POST['sport_ids'] ?? [];
            if (is_array($sportIds)) {
                $csModel = new ClubSportModel();
                foreach ($sportIds as $sportId) {
                    $csModel->addSportToClub($clubId, (int)$sportId);
                }
            }

            // 4. Subscription
            $planId = (int)($_POST['plan_id'] ?? 0);
            if ($planId > 0) {
                $stmt = $db->prepare(
                    "INSERT INTO club_subscriptions (club_id, plan_id, valid_until, status, billing_cycle,
                     max_members_override, max_sports_override, custom_features, admin_notes)
                     VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'active', 'monthly', ?, ?, ?, ?)"
                );
                $maxMembersOvr = trim($_POST['max_members_override'] ?? '') !== '' ? (int)$_POST['max_members_override'] : null;
                $maxSportsOvr  = trim($_POST['max_sports_override'] ?? '') !== '' ? (int)$_POST['max_sports_override'] : null;
                $customFeatures = trim($_POST['custom_features'] ?? '') ?: null;
                $adminNotes     = trim($_POST['admin_notes'] ?? '') ?: null;
                $stmt->execute([$clubId, $planId, $maxMembersOvr, $maxSportsOvr, $customFeatures, $adminNotes]);
            }

            // 5. Admin user
            $adminEmail = trim($_POST['admin_email'] ?? '');
            $adminName  = trim($_POST['admin_name'] ?? '');
            $adminPass  = $_POST['admin_password'] ?? '';
            if ($adminEmail !== '' && $adminName !== '' && $adminPass !== '') {
                $userModel = new UserModel();
                $existing  = $userModel->findByEmail($adminEmail);
                if ($existing) {
                    $adminUserId = (int)$existing['id'];
                } else {
                    $adminUserId = $userModel->create([
                        'username'  => $adminEmail,
                        'email'     => $adminEmail,
                        'password'  => $adminPass,
                        'full_name' => $adminName,
                        'is_active' => 1,
                    ]);
                }
                // 6. Grant zarzad role
                (new UserClubModel())->grantRole($adminUserId, $clubId, 'zarzad');
            }

            $db->commit();
            Session::flash('success', 'Klub utworzony z pełną konfiguracją.');
            $this->redirect('admin/clubs');
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Błąd podczas tworzenia klubu: ' . $e->getMessage());
            $this->redirect('admin/clubs/create-full');
        }
    }

    /**
     * GET: Edit full club form, pre-filled.
     */
    public function editClubFull(string $id): void
    {
        $clubId = (int)$id;
        $club   = (new ClubModel())->findById($clubId);
        if (!$club) {
            Session::flash('error', 'Nie znaleziono klubu.');
            $this->redirect('admin/clubs');
        }

        $sports       = (new SportModel())->listActive();
        $plans        = (new SubscriptionModel())->listPlans();
        $subscription = (new SubscriptionModel())->findForClub($clubId);
        $clubSports   = (new SportModel())->listForClub($clubId);
        $clubSportIds = array_column($clubSports, 'id');

        $this->render('admin/club_form_full', [
            'title'        => 'Edycja klubu (pełny formularz)',
            'club'         => $club,
            'sports'       => $sports,
            'plans'        => $plans,
            'clubSportIds' => $clubSportIds,
            'subscription' => $subscription,
        ]);
    }

    /**
     * POST: Update all club data in transaction.
     */
    public function updateClubFull(string $id): void
    {
        Csrf::verify();
        $clubId = (int)$id;
        $db = Database::pdo();

        $db->beginTransaction();
        try {
            // 1. Update club
            $clubData = [
                'name'       => trim($_POST['name'] ?? ''),
                'short_name' => trim($_POST['short_name'] ?? '') ?: null,
                'city'       => trim($_POST['city'] ?? '') ?: null,
                'nip'        => trim($_POST['nip'] ?? '') ?: null,
                'email'      => trim($_POST['club_email'] ?? '') ?: null,
                'phone'      => trim($_POST['club_phone'] ?? '') ?: null,
                'address'    => trim($_POST['address'] ?? '') ?: null,
                'is_active'  => isset($_POST['is_active']) ? 1 : 0,
            ];
            (new ClubModel())->update($clubId, $clubData);

            // 2. Sync sports
            $sportIds = $_POST['sport_ids'] ?? [];
            if (is_array($sportIds)) {
                $csModel = new ClubSportModel();
                // Deactivate all first
                $db->prepare("UPDATE club_sports SET is_active = 0 WHERE club_id = ?")->execute([$clubId]);
                foreach ($sportIds as $sportId) {
                    $csModel->addSportToClub($clubId, (int)$sportId);
                }
            }

            // 3. Update subscription
            $planId = (int)($_POST['plan_id'] ?? 0);
            if ($planId > 0) {
                $maxMembersOvr  = trim($_POST['max_members_override'] ?? '') !== '' ? (int)$_POST['max_members_override'] : null;
                $maxSportsOvr   = trim($_POST['max_sports_override'] ?? '') !== '' ? (int)$_POST['max_sports_override'] : null;
                $customFeatures = trim($_POST['custom_features'] ?? '') ?: null;
                $adminNotes     = trim($_POST['admin_notes'] ?? '') ?: null;

                $existing = (new SubscriptionModel())->findForClub($clubId);
                if ($existing) {
                    $stmt = $db->prepare(
                        "UPDATE club_subscriptions SET plan_id = ?, max_members_override = ?,
                         max_sports_override = ?, custom_features = ?, admin_notes = ?
                         WHERE club_id = ?"
                    );
                    $stmt->execute([$planId, $maxMembersOvr, $maxSportsOvr, $customFeatures, $adminNotes, $clubId]);
                } else {
                    $stmt = $db->prepare(
                        "INSERT INTO club_subscriptions (club_id, plan_id, valid_until, status, billing_cycle,
                         max_members_override, max_sports_override, custom_features, admin_notes)
                         VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'active', 'monthly', ?, ?, ?, ?)"
                    );
                    $stmt->execute([$clubId, $planId, $maxMembersOvr, $maxSportsOvr, $customFeatures, $adminNotes]);
                }
            }

            // 4. Admin user update (optional)
            $adminEmail = trim($_POST['admin_email'] ?? '');
            $adminName  = trim($_POST['admin_name'] ?? '');
            $adminPass  = $_POST['admin_password'] ?? '';
            if ($adminEmail !== '' && $adminName !== '' && $adminPass !== '') {
                $userModel = new UserModel();
                $existing  = $userModel->findByEmail($adminEmail);
                if ($existing) {
                    $adminUserId = (int)$existing['id'];
                } else {
                    $adminUserId = $userModel->create([
                        'username'  => $adminEmail,
                        'email'     => $adminEmail,
                        'password'  => $adminPass,
                        'full_name' => $adminName,
                        'is_active' => 1,
                    ]);
                }
                (new UserClubModel())->grantRole($adminUserId, $clubId, 'zarzad');
            }

            $db->commit();
            Session::flash('success', 'Klub zaktualizowany.');
            $this->redirect('admin/clubs');
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Błąd: ' . $e->getMessage());
            $this->redirect('admin/clubs/' . $clubId . '/edit-full');
        }
    }

    /**
     * POST: Toggle a sport ON/OFF for a club.
     */
    public function toggleClubSport(string $clubId): void
    {
        Csrf::verify();
        $cid     = (int)$clubId;
        $sportId = (int)($_POST['sport_id'] ?? 0);
        if ($sportId <= 0) {
            Session::flash('error', 'Nieprawidłowy sport.');
            $this->redirect('admin/clubs/' . $cid . '/edit-full');
        }

        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT id, is_active FROM club_sports WHERE club_id = ? AND sport_id = ? LIMIT 1"
        );
        $stmt->execute([$cid, $sportId]);
        $row = $stmt->fetch();

        if ($row) {
            $newState = $row['is_active'] ? 0 : 1;
            $db->prepare("UPDATE club_sports SET is_active = ? WHERE id = ?")->execute([$newState, $row['id']]);
        } else {
            (new ClubSportModel())->addSportToClub($cid, $sportId);
        }

        Session::flash('success', 'Sport zaktualizowany.');
        $this->redirect('admin/clubs/' . $cid . '/edit-full');
    }

    /**
     * POST: Save override limits for a club subscription.
     */
    public function setClubLimits(string $clubId): void
    {
        Csrf::verify();
        $cid = (int)$clubId;
        $db  = Database::pdo();

        $maxMembersOvr  = trim($_POST['max_members_override'] ?? '') !== '' ? (int)$_POST['max_members_override'] : null;
        $maxSportsOvr   = trim($_POST['max_sports_override'] ?? '') !== '' ? (int)$_POST['max_sports_override'] : null;
        $customFeatures = trim($_POST['custom_features'] ?? '') ?: null;
        $adminNotes     = trim($_POST['admin_notes'] ?? '') ?: null;

        $stmt = $db->prepare(
            "UPDATE club_subscriptions SET max_members_override = ?, max_sports_override = ?,
             custom_features = ?, admin_notes = ? WHERE club_id = ?"
        );
        $stmt->execute([$maxMembersOvr, $maxSportsOvr, $customFeatures, $adminNotes, $cid]);

        Session::flash('success', 'Limity zapisane.');
        $this->redirect('admin/clubs/' . $cid . '/analytics');
    }

    /**
     * GET: Per-club analytics with metrics and charts.
     */
    public function clubAnalytics(string $clubId): void
    {
        $cid  = (int)$clubId;
        $club = (new ClubModel())->findById($cid);
        if (!$club) {
            Session::flash('error', 'Nie znaleziono klubu.');
            $this->redirect('admin/clubs');
        }

        $db = Database::pdo();

        // Basic metrics
        $membersCount = (int)$db->prepare("SELECT COUNT(*) FROM members WHERE club_id = ? AND status='aktywny'")->execute([$cid]) ? 0 : 0;
        $stmt = $db->prepare("SELECT COUNT(*) FROM members WHERE club_id = ? AND status='aktywny'");
        $stmt->execute([$cid]);
        $membersCount = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE club_id = ?");
        $stmt->execute([$cid]);
        $paymentsTotal = (float)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM events WHERE club_id = ?");
        $stmt->execute([$cid]);
        $eventsCount = (int)$stmt->fetchColumn();

        $sportsList = (new SportModel())->listForClub($cid);
        $subscription = (new SubscriptionModel())->findForClub($cid);

        // Members per month (last 12)
        $stmt = $db->prepare(
            "SELECT DATE_FORMAT(join_date, '%Y-%m') AS month, COUNT(*) AS total
             FROM members WHERE club_id = ? AND join_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY month ORDER BY month"
        );
        $stmt->execute([$cid]);
        $membersPerMonth = $stmt->fetchAll();

        // Payments per month (last 12)
        $stmt = $db->prepare(
            "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS month, SUM(amount) AS total
             FROM payments WHERE club_id = ? AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY month ORDER BY month"
        );
        $stmt->execute([$cid]);
        $paymentsPerMonth = $stmt->fetchAll();

        // Events per sport
        $stmt = $db->prepare(
            "SELECT COALESCE(s.name, 'Brak sportu') AS sport_name, COUNT(*) AS total
             FROM events e
             LEFT JOIN sports s ON s.id = e.sport_id
             WHERE e.club_id = ?
             GROUP BY sport_name ORDER BY total DESC"
        );
        $stmt->execute([$cid]);
        $eventsPerSport = $stmt->fetchAll();

        $this->render('admin/club_analytics', [
            'title'            => 'Analityka: ' . $club['name'],
            'club'             => $club,
            'membersCount'     => $membersCount,
            'paymentsTotal'    => $paymentsTotal,
            'eventsCount'      => $eventsCount,
            'sportsList'       => $sportsList,
            'subscription'     => $subscription,
            'membersPerMonth'  => $membersPerMonth,
            'paymentsPerMonth' => $paymentsPerMonth,
            'eventsPerSport'   => $eventsPerSport,
        ]);
    }

    /** Impersonacja jako zawodnik — otwiera portal member. */
    public function impersonateMember(string $clubId, string $memberId): void
    {
        Csrf::verify();
        $db   = \App\Helpers\Database::pdo();
        $stmt = $db->prepare("SELECT * FROM members WHERE id = ? AND club_id = ?");
        $stmt->execute([(int)$memberId, (int)$clubId]);
        $member = $stmt->fetch();
        if (!$member) {
            Session::flash('error', 'Nie znaleziono zawodnika.');
            $this->redirect('admin/clubs/' . $clubId . '/users');
        }
        \App\Helpers\Auth::impersonateMember($member);
        (new \App\Models\ActivityLogModel())->log('impersonate_member', 'member', (int)$memberId, 'club=' . $clubId);
        Session::flash('success', 'Impersonujesz zawodnika: ' . $member['first_name'] . ' ' . $member['last_name']);
        $this->redirect('portal/dashboard');
    }
}
