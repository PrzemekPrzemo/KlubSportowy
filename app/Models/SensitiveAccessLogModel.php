<?php

namespace App\Models;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Database;

class SensitiveAccessLogModel extends BaseModel
{
    protected string $table = 'sensitive_access_log';

    public static array $DATA_TYPES = [
        'medical'            => 'Badania lekarskie',
        'anti_doping'        => 'Anti-doping',
        'body_metrics'       => 'Pomiary ciała',
        'emergency_contacts' => 'Kontakty awaryjne',
        'minor_consent'      => 'Zgody opiekunów',
        'boxing_medical'     => 'Badania bokserskie',
    ];

    public static array $ACTIONS = [
        'view'   => 'Przeglądanie',
        'list'   => 'Lista',
        'edit'   => 'Edycja',
        'delete' => 'Usunięcie',
        'export' => 'Eksport',
    ];

    /** Logowanie dostępu — silent fail (try/catch) aby nie blokować UI. */
    public static function log(string $dataType, string $action = 'view', ?int $memberId = null, ?string $context = null): void
    {
        try {
            $clubId = ClubContext::current();
            if (!$clubId) return;
            $db = Database::pdo();
            $stmt = $db->prepare(
                "INSERT INTO sensitive_access_log
                    (club_id, user_id, member_id, data_type, action, context, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $clubId,
                Auth::id(),
                $memberId,
                $dataType,
                $action,
                $context ?? ($_SERVER['REQUEST_URI'] ?? null),
                $_SERVER['REMOTE_ADDR'] ?? null,
                mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (\Throwable) {
            // silent fail
        }
    }

    public function listFiltered(?int $clubId, ?string $dataType, ?int $memberId, ?string $from, ?string $to, int $page = 1, int $perPage = 50): array
    {
        $sql = "SELECT sal.*,
                       u.full_name AS user_name,
                       m.first_name AS member_first, m.last_name AS member_last, m.member_number
                FROM sensitive_access_log sal
                LEFT JOIN users u   ON u.id   = sal.user_id
                LEFT JOIN members m ON m.id   = sal.member_id
                WHERE 1=1";
        $params = [];
        if ($clubId)    { $sql .= " AND sal.club_id = ?";    $params[] = $clubId; }
        if ($dataType && array_key_exists($dataType, self::$DATA_TYPES)) {
            $sql .= " AND sal.data_type = ?";
            $params[] = $dataType;
        }
        if ($memberId)  { $sql .= " AND sal.member_id = ?";  $params[] = $memberId; }
        if ($from)      { $sql .= " AND sal.created_at >= ?"; $params[] = $from; }
        if ($to)        { $sql .= " AND sal.created_at <= ?"; $params[] = $to; }
        $sql .= " ORDER BY sal.created_at DESC";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function topAccessors(int $clubId, int $days = 30, int $limit = 10): array
    {
        $db = $this->db;
        $stmt = $db->prepare(
            "SELECT u.id, u.full_name, u.username, COUNT(*) AS access_count
             FROM sensitive_access_log sal
             JOIN users u ON u.id = sal.user_id
             WHERE sal.club_id = ?
               AND sal.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY u.id
             ORDER BY access_count DESC
             LIMIT " . max(1, (int)$limit)
        );
        $stmt->execute([$clubId, $days]);
        return $stmt->fetchAll();
    }
}
