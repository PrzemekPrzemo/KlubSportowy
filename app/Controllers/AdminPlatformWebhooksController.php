<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use PDO;

/**
 * Super admin: kolejka webhook deliveries cross-klub.
 *
 * Dashboard + queue management dla `webhook_deliveries` (migracja 086).
 * Dispatcher (App\Helpers\Webhooks\WebhookDispatcher) tworzy wpisy w statusie
 * 'pending', worker (cli/webhook_worker.php) je przetwarza. Ten controller
 * pozwala super-adminowi:
 *
 *   - przegladnac wszystkie deliveries (filter: status / klub / event_type / data)
 *   - retry pojedynczego delivery (force back to pending, attempts=0)
 *   - fail permanently (status=failed bez wysylki)
 *
 * Wzorowane na AdminPlatformKsefController::queue() (PR #176).
 *
 * Bezpieczenstwo: requireSuperAdmin — webhook queue zawiera URL i payload
 * z roznych klubow (cross-tenant data).
 */
class AdminPlatformWebhooksController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    public function queue(): void
    {
        $filters = [
            'status'     => trim((string)($_GET['status'] ?? '')) ?: null,
            'club_id'    => !empty($_GET['club_id']) ? (int)$_GET['club_id'] : null,
            'event_type' => trim((string)($_GET['event_type'] ?? '')) ?: null,
            'date_from'  => trim((string)($_GET['date_from'] ?? '')) ?: null,
            'date_to'    => trim((string)($_GET['date_to'] ?? '')) ?: null,
        ];

        $rows  = $this->listDeliveries($filters, 200);
        $stats = $this->stats();

        $this->render('admin/platform/webhooks/queue', [
            'title'   => 'Webhooks — kolejka dostarczen',
            'rows'    => $rows,
            'stats'   => $stats,
            'filters' => $filters,
        ]);
    }

    public function retry(string $id): void
    {
        Csrf::verify();
        $deliveryId = (int)$id;
        if ($deliveryId <= 0) {
            Session::flash('error', 'Nieprawidlowy delivery id.');
            $this->redirect('admin/platform/webhooks/queue');
        }

        $row = $this->findDelivery($deliveryId);
        if ($row === null) {
            Session::flash('error', 'Wpis nie istnieje.');
            $this->redirect('admin/platform/webhooks/queue');
        }

        $pdo = Database::pdo();
        $pdo->prepare(
            "UPDATE webhook_deliveries
                SET status = 'pending',
                    attempts = 0,
                    next_retry_at = NOW(),
                    http_status = NULL,
                    response_body = NULL
              WHERE id = ?"
        )->execute([$deliveryId]);

        Session::flash('success', 'Delivery #' . $deliveryId . ' wraca do kolejki.');
        $this->redirect('admin/platform/webhooks/queue');
    }

    public function failPermanently(string $id): void
    {
        Csrf::verify();
        $deliveryId = (int)$id;
        if ($deliveryId <= 0) {
            Session::flash('error', 'Nieprawidlowy delivery id.');
            $this->redirect('admin/platform/webhooks/queue');
        }

        $row = $this->findDelivery($deliveryId);
        if ($row === null) {
            Session::flash('error', 'Wpis nie istnieje.');
            $this->redirect('admin/platform/webhooks/queue');
        }

        $reason = mb_substr(trim((string)($_POST['reason'] ?? 'admin force-fail')), 0, 500);
        $pdo = Database::pdo();
        $pdo->prepare(
            "UPDATE webhook_deliveries
                SET status = 'failed',
                    next_retry_at = NULL,
                    response_body = CONCAT(IFNULL(response_body, ''), '\n[admin force-fail] ', ?)
              WHERE id = ?"
        )->execute([$reason, $deliveryId]);

        Session::flash('success', 'Delivery #' . $deliveryId . ' oznaczone jako failed.');
        $this->redirect('admin/platform/webhooks/queue');
    }

    /**
     * Lista deliveries z filtrami.
     *
     * @param array{status?:?string,club_id?:?int,event_type?:?string,date_from?:?string,date_to?:?string} $filters
     * @return array<int,array<string,mixed>>
     */
    private function listDeliveries(array $filters, int $limit = 200): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'd.status = ?';
            $params[] = (string)$filters['status'];
        }
        if (!empty($filters['club_id'])) {
            $where[]  = 's.club_id = ?';
            $params[] = (int)$filters['club_id'];
        }
        if (!empty($filters['event_type'])) {
            $where[]  = 'd.event_type = ?';
            $params[] = (string)$filters['event_type'];
        }
        if (!empty($filters['date_from'])) {
            $where[]  = 'd.created_at >= ?';
            $params[] = (string)$filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'd.created_at <= ?';
            $params[] = (string)$filters['date_to'] . ' 23:59:59';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $limit    = max(1, min(1000, $limit));

        $sql = "SELECT d.*, s.target_url, s.club_id, s.name AS subscription_name,
                       c.name AS club_name
                  FROM webhook_deliveries d
                  JOIN webhook_subscriptions s ON s.id = d.subscription_id
             LEFT JOIN clubs c                ON c.id = s.club_id
                  {$whereSql}
              ORDER BY d.id DESC
                 LIMIT {$limit}";

        $pdo = Database::pdo();
        $st  = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string,mixed>|null */
    private function findDelivery(int $id): ?array
    {
        $pdo = Database::pdo();
        $st  = $pdo->prepare("SELECT * FROM webhook_deliveries WHERE id = ? LIMIT 1");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /**
     * @return array{total_pending:int,total_retrying:int,delivered_24h:int,failed_24h:int,avg_delivery_seconds:float,event_breakdown:array<int,array{event_type:string,c:int}>}
     */
    private function stats(): array
    {
        $pdo = Database::pdo();

        $st = $pdo->query(
            "SELECT
                SUM(status='pending')   AS pending,
                SUM(status='retrying')  AS retrying,
                SUM(status='delivered' AND delivered_at >= NOW() - INTERVAL 1 DAY) AS delivered_24h,
                SUM(status='failed'    AND created_at   >= NOW() - INTERVAL 1 DAY) AS failed_24h,
                COALESCE(
                    AVG(CASE WHEN status='delivered' AND delivered_at IS NOT NULL
                              THEN TIMESTAMPDIFF(SECOND, created_at, delivered_at) END),
                    0
                ) AS avg_delivery_seconds
               FROM webhook_deliveries"
        );
        $row = $st !== false ? $st->fetch(PDO::FETCH_ASSOC) : [];

        $brkSt = $pdo->query(
            "SELECT event_type, COUNT(*) AS c
               FROM webhook_deliveries
              WHERE created_at >= NOW() - INTERVAL 7 DAY
              GROUP BY event_type
              ORDER BY c DESC
              LIMIT 10"
        );
        $breakdown = $brkSt !== false ? ($brkSt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

        return [
            'total_pending'        => (int)($row['pending']           ?? 0),
            'total_retrying'       => (int)($row['retrying']          ?? 0),
            'delivered_24h'        => (int)($row['delivered_24h']     ?? 0),
            'failed_24h'           => (int)($row['failed_24h']        ?? 0),
            'avg_delivery_seconds' => (float)($row['avg_delivery_seconds'] ?? 0),
            'event_breakdown'      => $breakdown,
        ];
    }
}
