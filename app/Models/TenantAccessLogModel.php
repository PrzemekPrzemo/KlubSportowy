<?php

namespace App\Models;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Session;
use PDO;

/**
 * Audit cross-tenant data access (ClubScopedModel::withoutScope() calls).
 *
 * Tabela: tenant_access_log (migracja 066).
 *
 * Wpisywany jest KAZDY wywolany withoutScope() — bez filtra po club_id —
 * zeby super-admin mial przeglad kto i kiedy widzial dane wielu klubow.
 * Jest to defense-in-depth: ClubScopedModel nadal egzekwuje izolacje
 * domyslnie, a ten log daje obserwowalnosc gdy ktos swiadomie ja omija.
 *
 * Wzorzec zaczerpniety z hovera.app-sys (Hovera ma DB-per-tenant + GRANT
 * USAGE ON tenant_db.* — naturalna izolacja na poziomie MySQL). My nie
 * mozemy sobie pozwolic na ten architektoniczny rewrite, wiec dodajemy
 * mocna obserwowalnosc tam, gdzie kod celowo wychodzi ze scope.
 */
class TenantAccessLogModel extends BaseModel
{
    protected string $table = 'tenant_access_log';

    /**
     * Zarejestruj omijanie scope club_id.
     *
     * Bezpieczne wzgledem brakujacej tabeli (np. testy przed migracja).
     * Nigdy nie rzuca wyjatkiem — audit nie moze blokowac kodu uzytkowego.
     */
    public function logBypass(
        string $tableName,
        string $operation = 'read',
        ?string $callerFile = null,
        ?int $callerLine = null,
        ?string $callerClass = null,
        string $severity = 'info',
        ?string $notes = null
    ): void {
        try {
            $userId    = Auth::id();
            $username  = null;
            try {
                $u = Auth::user();
                $username = is_array($u) ? ($u['username'] ?? $u['email'] ?? null) : null;
            } catch (\Throwable) {}

            $isSuper       = ClubContext::isSuperAdmin() ? 1 : 0;
            $activeClub    = ClubContext::current();
            $requestPath   = $_SERVER['REQUEST_URI']    ?? null;
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? null;

            $stmt = $this->db->prepare(
                'INSERT INTO `tenant_access_log`
                    (user_id, username, is_super_admin, active_club_id,
                     table_name, operation, caller_file, caller_line, caller_class,
                     request_path, request_method, severity, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId !== null ? (int)$userId : null,
                $username,
                $isSuper,
                $activeClub,
                $tableName,
                $operation,
                $callerFile,
                $callerLine,
                $callerClass,
                $requestPath !== null ? substr($requestPath, 0, 255) : null,
                $requestMethod !== null ? substr($requestMethod, 0, 10) : null,
                $severity,
                $notes !== null ? substr($notes, 0, 255) : null,
            ]);
        } catch (\Throwable) {
            // Audit failure nie moze nigdy crashowac requestu uzytkowego.
        }
    }

    /**
     * Lista ostatnich wpisow (paginowana) — uzywana przez AdminAuditController.
     */
    public function recent(int $page = 1, int $perPage = 50): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(500, $perPage));
        $offset  = ($page - 1) * $perPage;

        $total = (int)$this->db->query('SELECT COUNT(*) FROM `tenant_access_log`')->fetchColumn();
        $stmt = $this->db->prepare(
            'SELECT * FROM `tenant_access_log`
             ORDER BY occurred_at DESC, id DESC
             LIMIT ' . $perPage . ' OFFSET ' . $offset
        );
        $stmt->execute();

        return [
            'data'         => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $perPage)),
        ];
    }

    /**
     * Statystyki za ostatnie N dni — kto i co najczesciej omija scope.
     */
    public function statsLastDays(int $days = 7): array
    {
        $days = max(1, min(365, $days));
        $stmt = $this->db->prepare(
            'SELECT table_name, operation, COUNT(*) AS c
             FROM `tenant_access_log`
             WHERE occurred_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY table_name, operation
             ORDER BY c DESC
             LIMIT 50'
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Wyczysc wpisy starsze niz N dni (cron job).
     */
    public function pruneOlderThan(int $days = 90): int
    {
        $days = max(1, $days);
        $stmt = $this->db->prepare(
            'DELETE FROM `tenant_access_log`
             WHERE occurred_at < DATE_SUB(NOW(), INTERVAL ? DAY)'
        );
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }
}
