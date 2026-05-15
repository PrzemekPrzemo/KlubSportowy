<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\ActivityLogModel;
use PDO;

/**
 * Audit log UI dla zarządu klubu (oraz super admina).
 *
 * Łączy w jednym widoku trzy źródła zdarzeń audytowych:
 *   - activity_log         — działania użytkowników w obrębie klubu
 *   - sensitive_access_log — dostęp do danych szczególnej kategorii (RODO)
 *   - tenant_access_log    — bypass scope club_id (defense-in-depth)
 *
 * Multi-tenant:
 *   - Zarząd klubu widzi tylko swój klub (`club_id = ClubContext::current()`).
 *   - Super admin (route /admin/platform/audit-log) widzi cross-club, może
 *     filtrować po club_id.
 */
class AuditLogController extends BaseController
{
    /** Akcje krytyczne — wyróżniane czerwoną ikoną i ujęte w alercie 24h. */
    private const CRITICAL_ACTIONS = [
        'member_delete', 'club_delete', 'user_delete',
        'impersonate_start', 'impersonate_stop',
        'export', 'gdpr_export', 'gdpr_delete',
        'admin_grant', 'role_change',
        'audit_isolation_export',
    ];

    /** Akcje o severity warning. */
    private const WARNING_ACTIONS = [
        'login_failed', 'password_reset', 'permission_denied',
        'fee_assign', 'fee_delete', 'invoice_delete',
    ];

    /**
     * Klubowy view: GET /admin/audit-log
     */
    public function index(): void
    {
        $this->requireRole(['zarzad', 'admin']);
        $clubId = $this->resolveClubScope(false);

        $filter  = $this->parseFilters();
        $listing = $this->queryLogs($clubId, $filter);
        $stats   = $this->statsForClub($clubId);
        $users   = $this->usersForClub($clubId);

        $this->render('admin/audit_log/index', [
            'title'          => 'Audyt aktywności klubu',
            'clubId'         => $clubId,
            'isPlatformView' => false,
            'filter'         => $filter,
            'listing'        => $listing,
            'stats'          => $stats,
            'users'          => $users,
            'actionTypes'    => $this->actionTypes(),
            'severities'     => ['info', 'warning', 'critical'],
            'sources'        => ['activity_log', 'sensitive_access', 'tenant_access'],
        ]);
    }

    /**
     * Platform view (super admin): GET /admin/platform/audit-log
     */
    public function platformIndex(): void
    {
        $this->requireSuperAdmin();
        $filter  = $this->parseFilters();
        $clubId  = !empty($_GET['club_id']) ? (int)$_GET['club_id'] : null;

        $listing = $this->queryLogs($clubId, $filter);
        $stats   = $this->statsForClub($clubId);
        $clubs   = $this->allClubs();
        $users   = $this->usersForClub($clubId);

        $this->render('admin/audit_log/index', [
            'title'          => 'Audyt aktywności (wszystkie kluby)',
            'clubId'         => $clubId,
            'isPlatformView' => true,
            'filter'         => $filter,
            'listing'        => $listing,
            'stats'          => $stats,
            'clubs'          => $clubs,
            'users'          => $users,
            'actionTypes'    => $this->actionTypes(),
            'severities'     => ['info', 'warning', 'critical'],
            'sources'        => ['activity_log', 'sensitive_access', 'tenant_access'],
        ]);
    }

    /**
     * Szczegóły wpisu: GET /admin/audit-log/:source/:id
     */
    public function detail(string $source, string $id): void
    {
        $this->requireRole(['zarzad', 'admin']);
        $isSuper = Auth::isSuperAdmin();
        $clubId  = $isSuper ? null : $this->resolveClubScope(false);

        $source = $this->sanitizeSource($source);
        $entryId = (int)$id;
        if ($entryId <= 0) {
            http_response_code(404);
            echo 'Nie znaleziono wpisu.';
            return;
        }

        $entry = $this->fetchEntry($source, $entryId);
        if (!$entry) {
            http_response_code(404);
            echo 'Nie znaleziono wpisu.';
            return;
        }

        // Multi-tenant guard: zarząd widzi tylko swój klub.
        if (!$isSuper) {
            $entryClub = isset($entry['club_id']) ? (int)$entry['club_id'] : (isset($entry['active_club_id']) ? (int)$entry['active_club_id'] : null);
            if ($entryClub !== null && $clubId !== null && $entryClub !== $clubId) {
                http_response_code(403);
                echo 'Brak dostępu.';
                return;
            }
        }

        // Sensitive: tylko dla osób z dostępem do danych wrażliwych.
        if ($source === 'sensitive_access' && !Auth::canAccessSensitiveData()) {
            http_response_code(403);
            echo 'Brak uprawnień do danych szczególnej kategorii.';
            return;
        }

        $related = $this->relatedEvents($entry, $clubId);

        $this->render('admin/audit_log/detail', [
            'title'   => 'Szczegóły zdarzenia',
            'source'  => $source,
            'entry'   => $entry,
            'related' => $related,
            'clubId'  => $clubId,
        ]);
    }

    /**
     * Export CSV: GET /admin/audit-log/export
     */
    public function export(): void
    {
        $this->requireRole(['zarzad', 'admin']);
        $isSuper = Auth::isSuperAdmin();

        if (!$isSuper && !Auth::canAccessSensitiveData()) {
            // Eksport audit logów to akcja zarządcza, dozwolona dla zarząd/admin.
            // Zwykli koordynatorzy nie mają dostępu.
            http_response_code(403);
            echo 'Brak uprawnień do eksportu.';
            return;
        }

        $clubId = $isSuper && !empty($_GET['club_id'])
            ? (int)$_GET['club_id']
            : ($isSuper ? null : $this->resolveClubScope(false));

        $filter  = $this->parseFilters();
        // Eksport bez paginacji — bierzemy do 10k wpisów.
        $filter['per_page'] = 10000;
        $filter['page']     = 1;

        $listing = $this->queryLogs($clubId, $filter);

        // Audit meta-entry: eksport audit logów to sam w sobie zdarzenie audytowe.
        try {
            (new ActivityLogModel())->log(
                'audit_log_export',
                'audit_log',
                null,
                sprintf('rows=%d club=%s', count($listing['data']), $clubId !== null ? (string)$clubId : 'all')
            );
        } catch (\Throwable) {}

        $filename = 'audit_log_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        // BOM dla Excel.
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'source', 'id', 'occurred_at', 'club_id', 'user_id', 'username',
            'action', 'target_type', 'target_id', 'severity', 'ip', 'details',
        ]);
        foreach ($listing['data'] as $row) {
            fputcsv($out, [
                $row['source'],
                $row['id'],
                $row['occurred_at'],
                $row['club_id'],
                $row['user_id'],
                $row['username'] ?? '',
                $row['action'],
                $row['target_type'] ?? '',
                $row['target_id'] ?? '',
                $row['severity'] ?? 'info',
                $row['ip_address'] ?? '',
                $row['details'] ?? '',
            ]);
        }
        fclose($out);
    }

    // ────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────

    private function resolveClubScope(bool $allowNull): ?int
    {
        if (Auth::isSuperAdmin()) {
            return null;
        }
        $cid = ClubContext::current();
        if ($cid === null) {
            if ($allowNull) return null;
            Session::flash('warning', 'Wybierz klub, aby zobaczyć audyt.');
            $this->redirect('club-select');
        }
        return (int)$cid;
    }

    private function parseFilters(): array
    {
        $allowedSources = ['activity_log', 'sensitive_access', 'tenant_access'];
        $sourceParam = isset($_GET['source']) ? (string)$_GET['source'] : '';
        $source = in_array($sourceParam, $allowedSources, true) ? $sourceParam : '';

        $sev = isset($_GET['severity']) ? (string)$_GET['severity'] : '';
        if (!in_array($sev, ['info', 'warning', 'critical'], true)) {
            $sev = '';
        }

        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        if (!in_array($days, [7, 30, 90, 0], true)) $days = 30;

        return [
            'action'   => isset($_GET['action'])   ? mb_substr(trim((string)$_GET['action']), 0, 80) : '',
            'user_id'  => isset($_GET['user_id'])  ? (int)$_GET['user_id'] : 0,
            'source'   => $source,
            'severity' => $sev,
            'days'     => $days,
            'search'   => isset($_GET['search']) ? mb_substr(trim((string)$_GET['search']), 0, 120) : '',
            'page'     => max(1, (int)($_GET['page']     ?? 1)),
            'per_page' => max(10, min(100, (int)($_GET['per_page'] ?? 50))),
        ];
    }

    private function sanitizeSource(string $s): string
    {
        $allowed = ['activity_log', 'sensitive_access', 'tenant_access'];
        return in_array($s, $allowed, true) ? $s : 'activity_log';
    }

    /**
     * Wykonuje UNION ALL nad trzema tabelami audit i zwraca paginowane wyniki.
     *
     * Wszystkie subqueries mają identyczny zestaw kolumn:
     *   source, id, occurred_at, club_id, user_id, username, action,
     *   target_type, target_id, severity, ip_address, details
     */
    private function queryLogs(?int $clubId, array $filter): array
    {
        $db = Database::pdo();

        $clauses = [];
        $clausesAct = [];
        $clausesSal = [];
        $clausesTal = [];
        $params = [];

        // Date range
        $days = (int)$filter['days'];
        if ($days > 0) {
            $clausesAct[] = 'al.created_at >= DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)';
            $clausesSal[] = 'sal.created_at >= DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)';
            $clausesTal[] = 'tal.occurred_at >= DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)';
        }

        // Club scope
        if ($clubId !== null) {
            $clausesAct[] = 'al.club_id = ?';
            $clausesSal[] = 'sal.club_id = ?';
            $clausesTal[] = '(tal.active_club_id = ? OR tal.active_club_id IS NULL)';
        }

        // User filter
        if ($filter['user_id'] > 0) {
            $clausesAct[] = 'al.user_id = ?';
            $clausesSal[] = 'sal.user_id = ?';
            $clausesTal[] = 'tal.user_id = ?';
        }

        // Action filter (LIKE)
        if ($filter['action'] !== '') {
            $clausesAct[] = 'al.action LIKE ?';
            $clausesSal[] = "CONCAT(sal.data_type, '_', sal.action) LIKE ?";
            $clausesTal[] = 'tal.operation LIKE ?';
        }

        // Search across details/notes
        if ($filter['search'] !== '') {
            $clausesAct[] = '(al.details LIKE ? OR al.action LIKE ? OR al.entity LIKE ?)';
            $clausesSal[] = '(sal.context LIKE ? OR sal.data_type LIKE ?)';
            $clausesTal[] = '(tal.notes LIKE ? OR tal.table_name LIKE ?)';
        }

        $whereAct = $clausesAct ? ('WHERE ' . implode(' AND ', $clausesAct)) : '';
        $whereSal = $clausesSal ? ('WHERE ' . implode(' AND ', $clausesSal)) : '';
        $whereTal = $clausesTal ? ('WHERE ' . implode(' AND ', $clausesTal)) : '';

        $bind = function (array &$arr, array $extras) {
            foreach ($extras as $v) $arr[] = $v;
        };

        // activity_log params
        if ($clubId !== null) $bind($params, [$clubId]);
        if ($filter['user_id'] > 0) $bind($params, [$filter['user_id']]);
        if ($filter['action'] !== '') $bind($params, ['%' . $filter['action'] . '%']);
        if ($filter['search'] !== '') {
            $s = '%' . $filter['search'] . '%';
            $bind($params, [$s, $s, $s]);
        }

        // sensitive_access_log params
        if ($clubId !== null) $bind($params, [$clubId]);
        if ($filter['user_id'] > 0) $bind($params, [$filter['user_id']]);
        if ($filter['action'] !== '') $bind($params, ['%' . $filter['action'] . '%']);
        if ($filter['search'] !== '') {
            $s = '%' . $filter['search'] . '%';
            $bind($params, [$s, $s]);
        }

        // tenant_access_log params
        if ($clubId !== null) $bind($params, [$clubId]);
        if ($filter['user_id'] > 0) $bind($params, [$filter['user_id']]);
        if ($filter['action'] !== '') $bind($params, ['%' . $filter['action'] . '%']);
        if ($filter['search'] !== '') {
            $s = '%' . $filter['search'] . '%';
            $bind($params, [$s, $s]);
        }

        // Source toggle decides which subqueries to UNION
        $parts = [];
        if ($filter['source'] === '' || $filter['source'] === 'activity_log') {
            $parts[] = "SELECT 'activity_log' AS source,
                              al.id              AS id,
                              al.created_at      AS occurred_at,
                              al.club_id         AS club_id,
                              al.user_id         AS user_id,
                              COALESCE(u.full_name, u.username) AS username,
                              al.action          AS action,
                              al.entity          AS target_type,
                              al.entity_id       AS target_id,
                              CASE
                                WHEN al.action IN (" . $this->inList(self::CRITICAL_ACTIONS) . ") THEN 'critical'
                                WHEN al.action IN (" . $this->inList(self::WARNING_ACTIONS)  . ") THEN 'warning'
                                ELSE 'info'
                              END                AS severity,
                              al.ip_address      AS ip_address,
                              al.details         AS details,
                              c.name             AS club_name
                       FROM activity_log al
                       LEFT JOIN users u ON u.id = al.user_id
                       LEFT JOIN clubs c ON c.id = al.club_id
                       {$whereAct}";
        }
        if ($filter['source'] === '' || $filter['source'] === 'sensitive_access') {
            $parts[] = "SELECT 'sensitive_access' AS source,
                              sal.id              AS id,
                              sal.created_at      AS occurred_at,
                              sal.club_id         AS club_id,
                              sal.user_id         AS user_id,
                              COALESCE(u.full_name, u.username) AS username,
                              CONCAT(sal.data_type, '_', sal.action) AS action,
                              'member'            AS target_type,
                              sal.member_id       AS target_id,
                              CASE
                                WHEN sal.action IN ('delete','export') THEN 'critical'
                                WHEN sal.action = 'edit' THEN 'warning'
                                ELSE 'info'
                              END                 AS severity,
                              sal.ip_address      AS ip_address,
                              sal.context         AS details,
                              c.name              AS club_name
                       FROM sensitive_access_log sal
                       LEFT JOIN users u ON u.id = sal.user_id
                       LEFT JOIN clubs c ON c.id = sal.club_id
                       {$whereSal}";
        }
        if ($filter['source'] === '' || $filter['source'] === 'tenant_access') {
            $parts[] = "SELECT 'tenant_access'    AS source,
                              tal.id              AS id,
                              tal.occurred_at     AS occurred_at,
                              tal.active_club_id  AS club_id,
                              tal.user_id         AS user_id,
                              tal.username        AS username,
                              CONCAT('scope_', tal.operation) AS action,
                              tal.table_name      AS target_type,
                              NULL                AS target_id,
                              tal.severity        AS severity,
                              NULL                AS ip_address,
                              tal.notes           AS details,
                              c.name              AS club_name
                       FROM tenant_access_log tal
                       LEFT JOIN clubs c ON c.id = tal.active_club_id
                       {$whereTal}";
        }

        if (empty($parts)) {
            return ['data' => [], 'total' => 0, 'per_page' => $filter['per_page'], 'current_page' => 1, 'last_page' => 1];
        }

        $union = '(' . implode(') UNION ALL (', $parts) . ')';

        // Severity filter applied on outer SELECT.
        $outerWhere = '';
        $outerParams = [];
        if ($filter['severity'] !== '') {
            $outerWhere = ' WHERE severity = ?';
            $outerParams[] = $filter['severity'];
        }

        $sql = "SELECT * FROM ({$union}) AS audit_union{$outerWhere} ORDER BY occurred_at DESC, id DESC";

        $allParams = array_merge($params, $outerParams);

        try {
            return $this->paginateUnion($db, $sql, $allParams, $filter['page'], $filter['per_page']);
        } catch (\Throwable $e) {
            return [
                'data' => [], 'total' => 0,
                'per_page' => $filter['per_page'], 'current_page' => 1, 'last_page' => 1,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function paginateUnion(PDO $db, string $sql, array $params, int $page, int $perPage): array
    {
        $countSql = "SELECT COUNT(*) FROM ({$sql}) AS _wrap";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $db->prepare($sql . ' LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $perPage)),
        ];
    }

    private function statsForClub(?int $clubId): array
    {
        $db = Database::pdo();
        $out = [
            'today'        => 0,
            'critical_7d'  => 0,
            'sensitive_30d' => 0,
            'active_admins' => 0,
            'trend'        => [],
        ];

        try {
            $sql = "SELECT COUNT(*) FROM activity_log WHERE DATE(created_at) = CURDATE()";
            if ($clubId !== null) $sql .= " AND club_id = " . (int)$clubId;
            $out['today'] = (int)$db->query($sql)->fetchColumn();
        } catch (\Throwable) {}

        try {
            $criticalList = "'" . implode("','", self::CRITICAL_ACTIONS) . "'";
            $sql = "SELECT COUNT(*) FROM activity_log
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                      AND action IN ({$criticalList})";
            if ($clubId !== null) $sql .= " AND club_id = " . (int)$clubId;
            $a = (int)$db->query($sql)->fetchColumn();

            $sql2 = "SELECT COUNT(*) FROM tenant_access_log
                     WHERE occurred_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                       AND severity = 'critical'";
            if ($clubId !== null) {
                $sql2 .= " AND (active_club_id = " . (int)$clubId . " OR active_club_id IS NULL)";
            }
            $b = (int)$db->query($sql2)->fetchColumn();
            $out['critical_7d'] = $a + $b;
        } catch (\Throwable) {}

        try {
            $sql = "SELECT COUNT(*) FROM sensitive_access_log
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            if ($clubId !== null) $sql .= " AND club_id = " . (int)$clubId;
            $out['sensitive_30d'] = (int)$db->query($sql)->fetchColumn();
        } catch (\Throwable) {}

        try {
            $sql = "SELECT COUNT(DISTINCT user_id) FROM activity_log
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                      AND user_id IS NOT NULL";
            if ($clubId !== null) $sql .= " AND club_id = " . (int)$clubId;
            $out['active_admins'] = (int)$db->query($sql)->fetchColumn();
        } catch (\Throwable) {}

        // Trend: 14 dni per day (liczba activity_log)
        try {
            $sql = "SELECT DATE(created_at) AS d, COUNT(*) AS c FROM activity_log
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)";
            if ($clubId !== null) $sql .= " AND club_id = " . (int)$clubId;
            $sql .= " GROUP BY DATE(created_at) ORDER BY d ASC";
            $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $out['trend'] = array_map(static fn($r) => [
                'date'  => (string)$r['d'],
                'count' => (int)$r['c'],
            ], $rows);
        } catch (\Throwable) {}

        return $out;
    }

    private function usersForClub(?int $clubId): array
    {
        $db = Database::pdo();
        try {
            if ($clubId !== null) {
                $stmt = $db->prepare(
                    "SELECT DISTINCT u.id, COALESCE(u.full_name, u.username) AS label
                     FROM users u
                     JOIN user_clubs uc ON uc.user_id = u.id
                     WHERE uc.club_id = ?
                     ORDER BY label ASC LIMIT 200"
                );
                $stmt->execute([$clubId]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            return $db->query(
                "SELECT id, COALESCE(full_name, username) AS label
                 FROM users ORDER BY label ASC LIMIT 200"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return [];
        }
    }

    private function allClubs(): array
    {
        try {
            return Database::pdo()
                ->query('SELECT id, name FROM clubs ORDER BY name ASC')
                ->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return [];
        }
    }

    private function actionTypes(): array
    {
        return [
            'login'       => 'Logowanie',
            'logout'      => 'Wylogowanie',
            'member'      => 'Członek',
            'fee'         => 'Składka',
            'payment'     => 'Płatność',
            'export'      => 'Eksport',
            'gdpr'        => 'RODO',
            'impersonate' => 'Impersonacja',
            'scope'       => 'Bypass scope',
            'role'        => 'Zmiana roli',
        ];
    }

    private function fetchEntry(string $source, int $id): ?array
    {
        $db = Database::pdo();
        try {
            if ($source === 'activity_log') {
                $stmt = $db->prepare(
                    "SELECT al.*, COALESCE(u.full_name, u.username) AS username,
                            c.name AS club_name
                     FROM activity_log al
                     LEFT JOIN users u ON u.id = al.user_id
                     LEFT JOIN clubs c ON c.id = al.club_id
                     WHERE al.id = ?"
                );
            } elseif ($source === 'sensitive_access') {
                $stmt = $db->prepare(
                    "SELECT sal.*, COALESCE(u.full_name, u.username) AS username,
                            c.name AS club_name,
                            CONCAT(m.first_name, ' ', m.last_name) AS member_name,
                            m.member_number
                     FROM sensitive_access_log sal
                     LEFT JOIN users u ON u.id = sal.user_id
                     LEFT JOIN clubs c ON c.id = sal.club_id
                     LEFT JOIN members m ON m.id = sal.member_id
                     WHERE sal.id = ?"
                );
            } else {
                $stmt = $db->prepare(
                    "SELECT tal.*, c.name AS club_name
                     FROM tenant_access_log tal
                     LEFT JOIN clubs c ON c.id = tal.active_club_id
                     WHERE tal.id = ?"
                );
            }
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Pokrewne zdarzenia tego samego użytkownika w ±30 minutach. */
    private function relatedEvents(array $entry, ?int $clubId): array
    {
        $db = Database::pdo();
        $userId = $entry['user_id'] ?? null;
        $when   = $entry['created_at'] ?? $entry['occurred_at'] ?? null;
        if ($userId === null || $when === null) return [];

        try {
            $sql = "SELECT al.id, al.action, al.entity, al.entity_id, al.created_at, al.details
                    FROM activity_log al
                    WHERE al.user_id = ?
                      AND al.created_at BETWEEN DATE_SUB(?, INTERVAL 30 MINUTE)
                                          AND DATE_ADD(?, INTERVAL 30 MINUTE)";
            $params = [$userId, $when, $when];
            if ($clubId !== null) {
                $sql .= ' AND al.club_id = ?';
                $params[] = $clubId;
            }
            $sql .= ' ORDER BY al.created_at DESC LIMIT 20';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return [];
        }
    }

    private function inList(array $items): string
    {
        // Generuje fragment SQL: 'a','b','c' (literały — bez user-input, bezpieczne).
        return "'" . implode("','", array_map(static fn($v) => str_replace("'", "''", $v), $items)) . "'";
    }
}
