<?php

namespace App\Controllers;

use App\Helpers\Database;
use PDO;

class AdminHealthController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    public function index(): void
    {
        $db = Database::pdo();

        // PHP info
        $requiredExt = ['PDO', 'pdo_mysql', 'json', 'mbstring', 'intl', 'openssl', 'curl', 'gd'];
        $extStatus = [];
        foreach ($requiredExt as $ext) {
            $extStatus[$ext] = extension_loaded($ext);
        }

        $php = [
            'version'          => PHP_VERSION,
            'version_ok'       => version_compare(PHP_VERSION, '8.1.0', '>='),
            'sapi'             => PHP_SAPI,
            'memory_limit'     => ini_get('memory_limit'),
            'max_upload'       => ini_get('upload_max_filesize'),
            'post_max'         => ini_get('post_max_size'),
            'display_errors'   => ini_get('display_errors'),
            'ext'              => $extStatus,
        ];

        // Disk
        $path = ROOT_PATH;
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);
        $used = ($free !== false && $total !== false) ? ($total - $free) : null;
        $disk = [
            'path'        => $path,
            'free_bytes'  => $free !== false ? $free : null,
            'total_bytes' => $total !== false ? $total : null,
            'used_bytes'  => $used,
            'pct_used'    => ($total && $used !== null) ? round(($used / $total) * 100, 1) : null,
        ];

        // DB: table sizes (top 15)
        $dbInfo = ['server' => null, 'tables' => []];
        try {
            $dbInfo['server'] = $db->query('SELECT VERSION()')->fetchColumn();
            $stmt = $db->query(
                "SELECT table_name, table_rows,
                        (data_length + index_length) AS size_bytes
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                 ORDER BY size_bytes DESC
                 LIMIT 15"
            );
            $dbInfo['tables'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $dbInfo['error'] = $e->getMessage();
        }

        // Migrations
        $migrationFiles = glob(ROOT_PATH . '/database/migrations/*.sql') ?: [];
        sort($migrationFiles);
        $latestMigration = $migrationFiles ? basename(end($migrationFiles)) : null;

        // Error/security counts
        $errors = $this->scalar($db, "SELECT COUNT(*) FROM error_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $errors7 = $this->scalar($db, "SELECT COUNT(*) FROM error_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $critical = $this->scalar($db, "SELECT COUNT(*) FROM error_log WHERE level = 'critical' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $secEvents24 = $this->scalar($db, "SELECT COUNT(*) FROM security_events WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $loginFailed24 = $this->scalar($db, "SELECT COUNT(*) FROM security_events WHERE event_type = 'login_failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");

        // Session config
        $session = [
            'gc_maxlifetime'  => (int)ini_get('session.gc_maxlifetime'),
            'cookie_httponly' => (bool)ini_get('session.cookie_httponly'),
            'cookie_secure'   => (bool)ini_get('session.cookie_secure'),
            'cookie_samesite' => ini_get('session.cookie_samesite') ?: 'Lax',
            'strict_mode'     => (bool)ini_get('session.use_strict_mode'),
        ];

        // Liczniki biznesowe
        $counters = [
            'clubs_total'         => $this->scalar($db, "SELECT COUNT(*) FROM clubs"),
            'clubs_active'        => $this->scalar($db, "SELECT COUNT(*) FROM clubs WHERE is_active = 1"),
            'users_total'         => $this->scalar($db, "SELECT COUNT(*) FROM users"),
            'users_super'         => $this->scalar($db, "SELECT COUNT(*) FROM users WHERE is_super_admin = 1"),
            'subs_active'         => $this->scalar($db, "SELECT COUNT(*) FROM club_subscriptions WHERE status = 'active'"),
            'subs_trial'          => $this->scalar($db, "SELECT COUNT(*) FROM club_subscriptions WHERE status = 'trial'"),
            'subs_expired'        => $this->scalar($db, "SELECT COUNT(*) FROM club_subscriptions WHERE status = 'expired'"),
            'subs_suspended'      => $this->scalar($db, "SELECT COUNT(*) FROM club_subscriptions WHERE status = 'suspended'"),
        ];

        // Log file size
        $logFile = ROOT_PATH . '/storage/logs/app.log';
        $errFile = ROOT_PATH . '/storage/logs/errors.log';
        $files = [
            'app_log'   => is_file($logFile) ? filesize($logFile) : 0,
            'error_log' => is_file($errFile) ? filesize($errFile) : 0,
        ];

        $this->render('admin/health/index', [
            'title'           => 'Zdrowie systemu',
            'php'             => $php,
            'disk'            => $disk,
            'db'              => $dbInfo,
            'latestMigration' => $latestMigration,
            'migrationsCount' => count($migrationFiles),
            'errors24'        => $errors,
            'errors7'         => $errors7,
            'critical7'       => $critical,
            'secEvents24'     => $secEvents24,
            'loginFailed24'   => $loginFailed24,
            'session'         => $session,
            'counters'        => $counters,
            'files'           => $files,
            'checkedAt'       => date('Y-m-d H:i:s'),
        ]);
    }

    private function scalar(PDO $db, string $sql): int
    {
        try {
            return (int)$db->query($sql)->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }
}
