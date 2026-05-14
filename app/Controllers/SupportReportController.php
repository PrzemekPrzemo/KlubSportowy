<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\MemberAuth;
use App\Helpers\Session;
use App\Helpers\TodoistClient;
use App\Models\ClubModel;
use App\Models\SupportReportModel;

/**
 * Zglaszanie bledow i propozycji zmian przez uzytkownikow klubowych
 * oraz portal-memberow. Po submit kazde zgloszenie jest synchronizowane
 * jako task w Todoist (projekt ClubDesk.pl).
 */
class SupportReportController extends BaseController
{
    private const ALLOWED_TYPES = ['bug', 'feature', 'question', 'other'];

    private const ALLOWED_MIME = ['image/png', 'image/jpeg', 'image/jpg'];
    private const MAX_UPLOAD_BYTES = 5 * 1024 * 1024; // 5 MB

    private const RATE_LIMIT_PER_HOUR = 20;

    public function __construct()
    {
        parent::__construct();
    }

    // ── Formularz zgloszenia ──────────────────────────────────────────

    public function reportForm(): void
    {
        $this->requireAnyAuth();

        $return = isset($_GET['return']) ? (string)$_GET['return'] : '';
        $this->setLayoutForSession();
        $this->render('support/report', [
            'title'         => 'Zglos blad lub propozycje',
            'allowedTypes'  => self::ALLOWED_TYPES,
            'returnUrl'     => $return,
            'submitterName' => $this->detectSubmitterName(),
        ]);
    }

    public function submitReport(): void
    {
        $this->requireAnyAuth();
        Csrf::verify();

        $userId   = Auth::id();
        $memberId = MemberAuth::check() ? MemberAuth::id() : null;
        $clubId   = $this->detectClubId();

        // Rate limit — super admin bez limitu (admin testuje/zglasza w naszym imieniu)
        $model = new SupportReportModel();
        $isSuperAdmin = Auth::isSuperAdmin();
        if (!$isSuperAdmin
            && $model->countRecentBySubmitter($userId, $memberId, 60) >= self::RATE_LIMIT_PER_HOUR) {
            Session::flash('error', 'Zbyt wiele zgloszen w ciagu ostatniej godziny. Sprobuj ponownie pozniej.');
            $this->redirect('support/report');
        }

        // Validate
        $type = (string)($_POST['type'] ?? 'bug');
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            $type = 'bug';
        }
        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));

        $errors = [];
        if (mb_strlen($title) < 5 || mb_strlen($title) > 200) {
            $errors[] = 'Tytul musi miec 5-200 znakow.';
        }
        if (mb_strlen($description) < 10 || mb_strlen($description) > 5000) {
            $errors[] = 'Opis musi miec 10-5000 znakow.';
        }

        if ($errors !== []) {
            Session::flash('error', implode(' ', $errors));
            $this->redirect('support/report');
        }

        // Screenshot upload (optional)
        $screenshotPath = null;
        $screenshotAbsolute = null;
        if (!empty($_FILES['screenshot']) && (int)($_FILES['screenshot']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                [$screenshotPath, $screenshotAbsolute] = $this->handleScreenshotUpload($_FILES['screenshot']);
            } catch (\Throwable $e) {
                Session::flash('error', 'Blad uploadu zrzutu ekranu: ' . $e->getMessage());
                $this->redirect('support/report');
            }
        }

        // Submitter context
        $submitterName  = $this->detectSubmitterName();
        $submitterEmail = $this->detectSubmitterEmail();
        $urlContext     = mb_substr((string)($_POST['url_context'] ?? ($_SERVER['HTTP_REFERER'] ?? '')), 0, 500);
        $userAgent      = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);

        // Insert
        $ticketId = $model->insert([
            'club_id'         => $clubId,
            'user_id'         => $userId,
            'member_id'       => $memberId,
            'submitter_name'  => $submitterName !== '' ? $submitterName : null,
            'submitter_email' => $submitterEmail !== '' ? $submitterEmail : null,
            'type'            => $type,
            'title'           => mb_substr($title, 0, 200),
            'description'     => mb_substr($description, 0, 5000),
            'screenshot_path' => $screenshotPath,
            'url_context'     => $urlContext !== '' ? $urlContext : null,
            'user_agent'      => $userAgent !== '' ? $userAgent : null,
            'status'          => 'new',
        ]);

        // Sync do Todoist (inline; bledy nie crashuja UI)
        $this->syncToTodoist($ticketId, $type, $title, $description, $clubId, $submitterName, $submitterEmail, $urlContext, $userAgent, $screenshotAbsolute);

        Session::flash('success', 'Dziekujemy! Zgloszenie #' . $ticketId . ' zostalo zarejestrowane.');

        // Powrot do strony skad przyszedl albo my-reports
        $return = (string)($_POST['return'] ?? '');
        if ($return !== '' && str_starts_with($return, '/')) {
            header('Location: ' . $return);
            exit;
        }
        $this->redirect('support/my-reports');
    }

    // ── Moja lista zgloszen ──────────────────────────────────────────

    public function myReports(): void
    {
        $this->requireAnyAuth();

        $model = new SupportReportModel();
        $userId = Auth::id();
        $memberId = MemberAuth::check() ? MemberAuth::id() : null;

        $rows = [];
        if ($userId !== null) {
            $rows = $model->recentForUser($userId, 100);
        } elseif ($memberId !== null) {
            $rows = $model->recentForMember($memberId, 100);
        }

        $this->setLayoutForSession();
        $this->render('support/my_reports', [
            'title'   => 'Moje zgloszenia',
            'reports' => $rows,
        ]);
    }

    // ── Admin lista zgloszen z klubu ─────────────────────────────────

    public function adminIndex(): void
    {
        Auth::requireLogin();
        if (!Auth::isSuperAdmin() && !Auth::hasRole(['zarzad', 'admin'])) {
            http_response_code(403);
            die('Brak uprawnien.');
        }

        $clubId = ClubContext::current();
        $model  = new SupportReportModel();

        $allowedStatus = ['new', 'in_progress', 'resolved', 'wont_fix', 'duplicate'];
        $allowedTypes  = self::ALLOWED_TYPES;

        $statusFilter = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
        if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatus, true)) {
            $statusFilter = '';
        }
        $typeFilter = isset($_GET['type']) ? trim((string)$_GET['type']) : '';
        if ($typeFilter !== '' && !in_array($typeFilter, $allowedTypes, true)) {
            $typeFilter = '';
        }
        $searchQ    = isset($_GET['q']) ? mb_substr(trim((string)$_GET['q']), 0, 100) : '';
        $period     = isset($_GET['period']) ? (string)$_GET['period'] : 'all';
        if (!in_array($period, ['7', '30', 'all'], true)) {
            $period = 'all';
        }

        $isSuper = Auth::isSuperAdmin();
        $useGlobal = $isSuper && $clubId === null;

        // Build WHERE clause
        $where = [];
        $params = [];

        if (!$useGlobal) {
            $where[]  = '`club_id` = ?';
            $params[] = (int)$clubId;
        }
        if ($statusFilter !== '') {
            $where[]  = '`status` = ?';
            $params[] = $statusFilter;
        }
        if ($typeFilter !== '') {
            $where[]  = '`type` = ?';
            $params[] = $typeFilter;
        }
        if ($searchQ !== '') {
            $where[]  = '`title` LIKE ?';
            $params[] = '%' . $searchQ . '%';
        }
        if ($period !== 'all') {
            $where[]  = '`created_at` >= (NOW() - INTERVAL ? DAY)';
            $params[] = (int)$period;
        }

        $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT * FROM `support_reports`" . $sqlWhere . " ORDER BY `created_at` DESC LIMIT 200";
        $stmt = $model->getDb()->prepare($sql);
        $stmt->execute($params);
        $reports = $stmt->fetchAll();

        // Stats panel (per current club scope; super-admin global => global stats)
        $stats = $this->computeStats($model->getDb(), $useGlobal ? null : (int)$clubId);

        $this->render('admin/support/index', [
            'title'         => 'Zgloszenia bledow i propozycji',
            'reports'       => $reports,
            'statusFilter'  => $statusFilter,
            'typeFilter'    => $typeFilter,
            'searchQ'       => $searchQ,
            'period'        => $period,
            'allowedStatus' => $allowedStatus,
            'allowedTypes'  => $allowedTypes,
            'stats'         => $stats,
        ]);
    }

    /**
     * Szczegolowy widok zgloszenia.
     */
    public function adminDetail(string $id): void
    {
        Auth::requireLogin();
        if (!Auth::isSuperAdmin() && !Auth::hasRole(['zarzad', 'admin'])) {
            http_response_code(403);
            die('Brak uprawnien.');
        }

        $ticketId = (int)$id;
        $model = new SupportReportModel();
        $row = $model->findById($ticketId);
        if (!$row) {
            Session::flash('error', 'Zgloszenie nie istnieje.');
            $this->redirect('admin/support');
        }

        // Authorization
        if (!Auth::isSuperAdmin()) {
            $clubId = ClubContext::current();
            if ((int)($row['club_id'] ?? 0) !== (int)$clubId) {
                http_response_code(403);
                die('Brak uprawnien do tego zgloszenia.');
            }
        }

        // Resolver name (jesli ustawiony)
        $resolverName = null;
        if (!empty($row['resolved_by'])) {
            try {
                $stmt = $model->getDb()->prepare('SELECT username, full_name FROM `users` WHERE id = ? LIMIT 1');
                $stmt->execute([(int)$row['resolved_by']]);
                $u = $stmt->fetch();
                if ($u) {
                    $resolverName = (string)($u['full_name'] ?? $u['username'] ?? '');
                }
            } catch (\Throwable) {}
        }

        $allowedStatus = ['new', 'in_progress', 'resolved', 'wont_fix', 'duplicate'];

        $this->render('admin/support/detail', [
            'title'         => 'Zgloszenie #' . $ticketId,
            'report'        => $row,
            'resolverName'  => $resolverName,
            'allowedStatus' => $allowedStatus,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function computeStats(\PDO $db, ?int $clubId): array
    {
        $scopeWhere = $clubId !== null ? ' WHERE `club_id` = ?' : '';
        $scopeParams = $clubId !== null ? [$clubId] : [];

        // Counts per status
        $sql = "SELECT `status`, COUNT(*) AS c FROM `support_reports`" . $scopeWhere . " GROUP BY `status`";
        $stmt = $db->prepare($sql);
        $stmt->execute($scopeParams);
        $byStatus = ['new' => 0, 'in_progress' => 0, 'resolved' => 0, 'wont_fix' => 0, 'duplicate' => 0];
        foreach ($stmt->fetchAll() as $r) {
            $byStatus[(string)$r['status']] = (int)$r['c'];
        }

        // Counts per type
        $sql = "SELECT `type`, COUNT(*) AS c FROM `support_reports`" . $scopeWhere . " GROUP BY `type`";
        $stmt = $db->prepare($sql);
        $stmt->execute($scopeParams);
        $byType = ['bug' => 0, 'feature' => 0, 'question' => 0, 'other' => 0];
        foreach ($stmt->fetchAll() as $r) {
            $byType[(string)$r['type']] = (int)$r['c'];
        }

        // Resolved last 30d
        $where30 = $clubId !== null
            ? "WHERE `club_id` = ? AND `resolved_at` >= (NOW() - INTERVAL 30 DAY)"
            : "WHERE `resolved_at` >= (NOW() - INTERVAL 30 DAY)";
        $stmt = $db->prepare("SELECT COUNT(*) FROM `support_reports` {$where30}");
        $stmt->execute($scopeParams);
        $resolvedLast30 = (int)$stmt->fetchColumn();

        // Avg resolution time (godziny) — z ostatnich 90 dni
        $where90 = $clubId !== null
            ? "WHERE `club_id` = ? AND `resolved_at` IS NOT NULL AND `created_at` >= (NOW() - INTERVAL 90 DAY)"
            : "WHERE `resolved_at` IS NOT NULL AND `created_at` >= (NOW() - INTERVAL 90 DAY)";
        $stmt = $db->prepare(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, `created_at`, `resolved_at`)) FROM `support_reports` {$where90}"
        );
        $stmt->execute($scopeParams);
        $avgHours = $stmt->fetchColumn();
        $avgHours = $avgHours === false || $avgHours === null ? null : (float)$avgHours;

        return [
            'by_status'         => $byStatus,
            'by_type'           => $byType,
            'resolved_last_30d' => $resolvedLast30,
            'avg_resolution_h'  => $avgHours,
        ];
    }

    /**
     * Manualne wymuszenie sync statusu zgloszen z Todoistem.
     * Endpoint: POST /admin/support/sync-now
     * Wykonuje to samo co cron `cli/todoist_sync_status.php`, ale inline.
     */
    public function syncNow(): void
    {
        Auth::requireLogin();
        if (!Auth::isSuperAdmin() && !Auth::hasRole(['zarzad', 'admin'])) {
            http_response_code(403);
            die('Brak uprawnien.');
        }
        Csrf::verify();

        $client = new TodoistClient();
        if (!$client->isConfigured()) {
            Session::flash('error', 'Todoist nie jest skonfigurowany (config/todoist.local.php).');
            $this->redirect('admin/support');
        }

        $db = \App\Helpers\Database::pdo();
        // Super admin sync wszystkie, zarzad/admin tylko z aktywnego klubu
        $clubId = ClubContext::current();
        $where = "todoist_task_id IS NOT NULL AND todoist_task_id <> '' AND status IN ('new','in_progress')";
        $params = [];
        if (!Auth::isSuperAdmin() && $clubId !== null) {
            $where .= " AND club_id = ?";
            $params[] = (int)$clubId;
        }
        $stmt = $db->prepare("SELECT id, todoist_task_id FROM support_reports WHERE {$where} ORDER BY id ASC LIMIT 100");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $processed = 0; $resolved = 0; $deleted = 0; $stillOpen = 0; $errors = 0;
        $upd = $db->prepare(
            "UPDATE support_reports
                SET status = ?, resolved_at = ?, resolution_notes = ?, todoist_synced_at = ?, todoist_sync_error = NULL
              WHERE id = ?"
        );
        $touchSync = $db->prepare(
            "UPDATE support_reports SET todoist_synced_at = ?, todoist_sync_error = NULL WHERE id = ?"
        );
        $touchErr = $db->prepare("UPDATE support_reports SET todoist_sync_error = ? WHERE id = ?");

        foreach ($rows as $row) {
            $processed++;
            $ticketId = (int)$row['id'];
            $taskId   = (string)$row['todoist_task_id'];
            try {
                $task = $client->getTask($taskId);
                if ($task === null) {
                    $upd->execute(['resolved', date('Y-m-d H:i:s'), 'Task deleted in Todoist', date('Y-m-d H:i:s'), $ticketId]);
                    $deleted++;
                    continue;
                }
                $isCompleted = !empty($task['is_completed']) || !empty($task['completed_at']) || !empty($task['checked']);
                if ($isCompleted) {
                    $upd->execute(['resolved', date('Y-m-d H:i:s'), 'Closed in Todoist', date('Y-m-d H:i:s'), $ticketId]);
                    $resolved++;
                } else {
                    $touchSync->execute([date('Y-m-d H:i:s'), $ticketId]);
                    $stillOpen++;
                }
            } catch (\Throwable $e) {
                $errors++;
                try { $touchErr->execute([mb_substr($e->getMessage(), 0, 1000), $ticketId]); } catch (\Throwable) {}
            }
            // Light throttle — 50ms
            usleep(50000);
        }

        $msg = "Sync zakonczony: sprawdzono {$processed}, zamknieto {$resolved}, usunieto z Todoist {$deleted}, wciaz otwarte {$stillOpen}";
        if ($errors > 0) {
            $msg .= ", bledy: {$errors}";
            Session::flash('warning', $msg);
        } else {
            Session::flash('success', $msg . '.');
        }
        $this->redirect('admin/support');
    }

    public function updateStatus(string $id): void
    {
        Auth::requireLogin();
        if (!Auth::isSuperAdmin() && !Auth::hasRole(['zarzad', 'admin'])) {
            http_response_code(403);
            die('Brak uprawnien.');
        }
        Csrf::verify();

        $ticketId = (int)$id;
        $newStatus = (string)($_POST['status'] ?? '');
        $allowed   = ['new', 'in_progress', 'resolved', 'wont_fix', 'duplicate'];
        if (!in_array($newStatus, $allowed, true)) {
            Session::flash('error', 'Nieprawidlowy status.');
            $this->redirect('admin/support');
        }

        $model = new SupportReportModel();
        $row = $model->findById($ticketId);
        if (!$row) {
            Session::flash('error', 'Zgloszenie nie istnieje.');
            $this->redirect('admin/support');
        }

        // Authorization: zarzad/admin moga edytowac tylko zgloszenia ze swojego klubu
        if (!Auth::isSuperAdmin()) {
            $clubId = ClubContext::current();
            if ((int)($row['club_id'] ?? 0) !== (int)$clubId) {
                http_response_code(403);
                die('Brak uprawnien do tego zgloszenia.');
            }
        }

        $oldStatus = (string)($row['status'] ?? 'new');

        $update = ['status' => $newStatus];
        if (in_array($newStatus, ['resolved', 'wont_fix', 'duplicate'], true)) {
            $update['resolved_at'] = date('Y-m-d H:i:s');
            $update['resolved_by'] = Auth::id();
            $notes = trim((string)($_POST['resolution_notes'] ?? ''));
            if ($notes !== '') {
                $update['resolution_notes'] = mb_substr($notes, 0, 2000);
            }
        }
        // Reopen — wyczysc resolved_at jesli wracamy do 'new' lub 'in_progress'
        if (in_array($newStatus, ['new', 'in_progress'], true) && in_array($oldStatus, ['resolved', 'wont_fix', 'duplicate'], true)) {
            $update['resolved_at']      = null;
            $update['resolved_by']      = null;
        }
        $model->update($ticketId, $update);

        // Push do Todoist (best-effort)
        $this->pushStatusToTodoist($ticketId, $row, $newStatus, $oldStatus);

        Session::flash('success', 'Status zaktualizowany.');

        $returnTo = (string)($_POST['return'] ?? '');
        if ($returnTo !== '' && str_starts_with($returnTo, '/')) {
            header('Location: ' . $returnTo);
            exit;
        }
        $this->redirect('admin/support');
    }

    /**
     * Push zmiany statusu do Todoist (close / reopen / comment).
     * Bledy zapisuje do todoist_sync_error, nie crashuje.
     *
     * @param array<string,mixed> $row aktualny support_report (przed update)
     */
    private function pushStatusToTodoist(int $ticketId, array $row, string $newStatus, string $oldStatus): void
    {
        $taskId = (string)($row['todoist_task_id'] ?? '');
        if ($taskId === '') return;

        $model = new SupportReportModel();
        try {
            $client = new TodoistClient();
            if (!$client->isConfigured()) return;

            $closedStatuses = ['resolved', 'wont_fix', 'duplicate'];
            $isOldClosed = in_array($oldStatus, $closedStatuses, true);
            $isNewClosed = in_array($newStatus, $closedStatuses, true);

            if ($isNewClosed && !$isOldClosed) {
                // Close task
                $ok = $client->closeTask($taskId);
                if (!$ok) {
                    throw new \RuntimeException('closeTask returned false');
                }
                $model->update($ticketId, [
                    'todoist_synced_at'  => date('Y-m-d H:i:s'),
                    'todoist_sync_error' => null,
                ]);
            } elseif (!$isNewClosed && $isOldClosed) {
                // Reopen
                $ok = $client->reopenTask($taskId);
                if (!$ok) {
                    throw new \RuntimeException('reopenTask returned false');
                }
                $model->update($ticketId, [
                    'todoist_synced_at'  => date('Y-m-d H:i:s'),
                    'todoist_sync_error' => null,
                ]);
            } elseif ($newStatus === 'in_progress' && $oldStatus !== 'in_progress') {
                // Comment "przyjete do realizacji"
                $user = Auth::user();
                $adminName = (string)($user['full_name'] ?? $user['username'] ?? 'admin');
                $client->addComment($taskId, "Przyjete do realizacji przez {$adminName}.");
                $model->update($ticketId, [
                    'todoist_synced_at'  => date('Y-m-d H:i:s'),
                    'todoist_sync_error' => null,
                ]);
            }
        } catch (\Throwable $e) {
            try {
                $model->update($ticketId, [
                    'todoist_sync_error' => mb_substr('push: ' . $e->getMessage(), 0, 1000),
                ]);
            } catch (\Throwable) {}
            error_log('[support_reports] Todoist push failed ticket=' . $ticketId . ': ' . $e->getMessage());
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function requireAnyAuth(): void
    {
        if (Auth::check() || MemberAuth::check()) {
            return;
        }
        Session::flash('error', 'Musisz byc zalogowany aby zglosic problem.');
        header('Location: ' . url('auth/login'));
        exit;
    }

    private function setLayoutForSession(): void
    {
        // Jesli portal-member -> uzyj portal layout, inaczej main
        if (MemberAuth::check() && !Auth::check()) {
            $this->view->setLayout('portal');
        } else {
            $this->view->setLayout('main');
        }
    }

    private function detectClubId(): ?int
    {
        $cid = ClubContext::current();
        if ($cid !== null) return (int)$cid;
        if (MemberAuth::check()) {
            $mcid = MemberAuth::clubId();
            if ($mcid !== null) return (int)$mcid;
        }
        return null;
    }

    private function detectSubmitterName(): string
    {
        $user = Auth::user();
        if ($user) {
            return (string)($user['full_name'] ?? $user['username'] ?? '');
        }
        if (MemberAuth::check()) {
            $m = MemberAuth::member();
            if ($m) {
                $fn = trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? ''));
                return $fn !== '' ? $fn : (string)($m['email'] ?? '');
            }
            $pm = Session::get('portal_member_name');
            if ($pm) return (string)$pm;
        }
        return '';
    }

    private function detectSubmitterEmail(): string
    {
        $user = Auth::user();
        if ($user && !empty($user['email'])) return (string)$user['email'];
        if (MemberAuth::check()) {
            $m = MemberAuth::member();
            if ($m && !empty($m['email'])) return (string)$m['email'];
            $pe = Session::get('portal_member_email');
            if ($pe) return (string)$pe;
        }
        return '';
    }

    /**
     * @param array<string,mixed> $file
     * @return array{0:string,1:string} [relative_path_db, absolute_path]
     */
    private function handleScreenshotUpload(array $file): array
    {
        if ((int)($file['size'] ?? 0) > self::MAX_UPLOAD_BYTES) {
            throw new \RuntimeException('Plik przekracza 5 MB.');
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new \RuntimeException('Brak pliku.');
        }

        // MIME check (server-side detect, ignore client-reported type)
        $mime = function_exists('mime_content_type') ? (mime_content_type($tmp) ?: '') : '';
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            throw new \RuntimeException('Dozwolone tylko PNG/JPG.');
        }

        // Magic bytes check
        $fh = fopen($tmp, 'rb');
        if ($fh === false) {
            throw new \RuntimeException('Nie mozna otworzyc pliku.');
        }
        $head = fread($fh, 8) ?: '';
        fclose($fh);
        $isPng  = str_starts_with($head, "\x89PNG\r\n\x1a\n");
        $isJpeg = str_starts_with($head, "\xFF\xD8\xFF");
        if (!$isPng && !$isJpeg) {
            throw new \RuntimeException('Nieprawidlowy format pliku (magic bytes).');
        }

        $ext = $isPng ? 'png' : 'jpg';

        $dir = ROOT_PATH . '/storage/uploads/support';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Nie mozna utworzyc katalogu uploads/support.');
        }

        $filename = 'support_' . uniqid('', true) . '.' . $ext;
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $absolute = $dir . '/' . $filename;

        if (!@move_uploaded_file($tmp, $absolute)) {
            throw new \RuntimeException('Nie mozna zapisac pliku.');
        }
        @chmod($absolute, 0644);

        return ['storage/uploads/support/' . $filename, $absolute];
    }

    private function syncToTodoist(
        int $ticketId,
        string $type,
        string $title,
        string $description,
        ?int $clubId,
        string $submitterName,
        string $submitterEmail,
        string $urlContext,
        string $userAgent,
        ?string $screenshotAbsolute
    ): void {
        $model = new SupportReportModel();

        try {
            $client = new TodoistClient();
            if (!$client->isConfigured()) {
                $model->update($ticketId, [
                    'todoist_sync_error' => 'Todoist API token not configured.',
                ]);
                return;
            }

            $clubName = '';
            if ($clubId !== null) {
                try {
                    $club = (new ClubModel())->findById($clubId);
                    $clubName = (string)($club['name'] ?? '');
                } catch (\Throwable) {}
            }

            $typeLabel = strtoupper($type);
            $taskContent = "[{$typeLabel}] " . $title;

            $taskDescription =
                "**Zgloszenie #{$ticketId}** | typ: {$type}\n\n" .
                $description . "\n\n" .
                "---\n" .
                "**Kontekst:**\n" .
                ($clubName !== '' ? "- Klub: {$clubName} (id={$clubId})\n" : "- Klub: (brak)\n") .
                "- Uzytkownik: " . ($submitterName !== '' ? $submitterName : '(nieznany)') .
                ($submitterEmail !== '' ? " <{$submitterEmail}>" : '') . "\n" .
                "- URL strony: " . ($urlContext !== '' ? $urlContext : '(brak)') . "\n" .
                "- User agent: " . ($userAgent !== '' ? $userAgent : '(brak)') . "\n" .
                "- Zgloszenie w panelu: https://portal.clubdesk.pl/admin/support";

            // Priority mapping: bug -> p2, feature -> p3, question/other -> p4
            $priority = match ($type) {
                'bug'     => 'p2',
                'feature' => 'p3',
                default   => 'p4',
            };

            $taskId = $client->createTask($taskContent, $taskDescription, $priority);
            if ($taskId === null) {
                $model->update($ticketId, [
                    'todoist_sync_error' => 'createTask returned null id',
                ]);
                return;
            }

            $update = [
                'todoist_task_id'   => $taskId,
                'todoist_synced_at' => date('Y-m-d H:i:s'),
                'todoist_sync_error' => null,
            ];

            // Screenshot attachment
            if ($screenshotAbsolute !== null && is_file($screenshotAbsolute)) {
                $upload = $client->uploadFile($screenshotAbsolute, basename($screenshotAbsolute));
                if (is_array($upload) && !empty($upload)) {
                    $client->addCommentWithAttachment($taskId, 'Zrzut ekranu', $upload);
                }
            }

            $model->update($ticketId, $update);
        } catch (\Throwable $e) {
            try {
                $model->update($ticketId, [
                    'todoist_sync_error' => mb_substr($e->getMessage(), 0, 1000),
                ]);
            } catch (\Throwable) {}
            error_log('[support_reports] Todoist sync failed for ticket ' . $ticketId . ': ' . $e->getMessage());
        }
    }
}
