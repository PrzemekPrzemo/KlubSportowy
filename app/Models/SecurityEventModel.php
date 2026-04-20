<?php

namespace App\Models;

use App\Helpers\Database;
use App\Helpers\Session;

class SecurityEventModel extends BaseModel
{
    protected string $table = 'security_events';

    /**
     * Log a security event. Never throws — failure is swallowed.
     */
    public static function log(string $type, array $details = []): void
    {
        try {
            $pdo = Database::pdo();
            $userId = null;
            if (class_exists('\\App\\Helpers\\Session')) {
                $userId = Session::get('user_id');
            }
            $stmt = $pdo->prepare(
                "INSERT INTO security_events (event_type, ip_address, user_id, user_agent, url, details)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $type,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $userId !== null ? (int)$userId : null,
                isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null,
                isset($_SERVER['REQUEST_URI']) ? mb_substr($_SERVER['REQUEST_URI'], 0, 1000) : null,
                $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            ]);
        } catch (\Throwable) {
            // never break the app on logging failure
        }
    }

    public function listFiltered(?string $type, ?string $ip, ?string $from, ?string $to, int $page = 1, int $perPage = 30): array
    {
        $where = [];
        $params = [];

        if ($type !== null && $type !== '') {
            $where[] = 's.event_type = ?';
            $params[] = $type;
        }
        if ($ip !== null && $ip !== '') {
            $where[] = 's.ip_address = ?';
            $params[] = $ip;
        }
        if ($from !== null && $from !== '') {
            $where[] = 's.created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== null && $to !== '') {
            $where[] = 's.created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT s.*, u.username, u.full_name
                FROM security_events s
                LEFT JOIN users u ON u.id = s.user_id
                {$whereSql}
                ORDER BY s.created_at DESC, s.id DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Return IPs with recent login_failed events (for admin review).
     * Also returns current block status from rate_limits table.
     */
    public function blockedIps(): array
    {
        $sql = "SELECT s.ip_address,
                       COUNT(*) AS fail_count,
                       MAX(s.created_at) AS last_fail,
                       rl.attempts AS rl_attempts,
                       rl.blocked_until AS rl_blocked_until
                FROM security_events s
                LEFT JOIN rate_limits rl ON rl.ip = s.ip_address AND rl.action = 'login'
                WHERE s.event_type = 'login_failed'
                  AND s.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  AND s.ip_address IS NOT NULL
                GROUP BY s.ip_address, rl.attempts, rl.blocked_until
                ORDER BY fail_count DESC, last_fail DESC
                LIMIT 100";
        return $this->db->query($sql)->fetchAll();
    }

    public function stats24h(): array
    {
        $stmt = $this->db->query(
            "SELECT event_type, COUNT(*) AS c
             FROM security_events
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             GROUP BY event_type"
        );
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[$row['event_type']] = (int)$row['c'];
        }
        return $out;
    }
}
