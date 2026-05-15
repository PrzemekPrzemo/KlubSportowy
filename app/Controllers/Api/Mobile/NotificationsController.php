<?php

namespace App\Controllers\Api\Mobile;

use App\Helpers\Database;
use App\Models\MemberNotificationModel;

/**
 * Mobile API v1 — in-app notifications.
 * Re-uses MemberNotificationModel.
 */
class NotificationsController extends V1Controller
{
    /** GET /api/mobile/v1/notifications?page=N */
    public function index(): void
    {
        $this->requireAuth();
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;

        // MemberNotificationModel::allForMember only supports a flat limit, so
        // we build a paginated query inline against `member_notifications`.
        $db = Database::pdo();
        $countStmt = $db->prepare(
            "SELECT COUNT(*) FROM member_notifications WHERE member_id = ? AND club_id = ?"
        );
        $countStmt->execute([$this->memberId, $this->clubId]);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT * FROM member_notifications
             WHERE member_id = ? AND club_id = ?
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->bindValue(1, $this->memberId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $this->clubId, \PDO::PARAM_INT);
        $stmt->bindValue(3, $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(4, ($page - 1) * $perPage, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $shaped = array_map(fn($n) => [
            'id'         => (int)$n['id'],
            'type'       => $n['type'],
            'title'      => $n['title'],
            'body'       => $n['body'],
            'link'       => $n['link'],
            'read_at'    => $n['read_at'],
            'created_at' => $n['created_at'],
            'is_read'    => $n['read_at'] !== null,
        ], $rows);

        $this->paginated([
            'data'         => $shaped,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    /** POST /api/mobile/v1/notifications/:id/read */
    public function markRead(string $id): void
    {
        $this->requireAuth();
        (new MemberNotificationModel())->markRead((int)$id, $this->memberId);
        $this->json(['id' => (int)$id, 'read' => true]);
    }

    /** POST /api/mobile/v1/notifications/read-all */
    public function markAllRead(): void
    {
        $this->requireAuth();
        $stmt = Database::pdo()->prepare(
            "UPDATE member_notifications SET read_at = NOW()
             WHERE member_id = ? AND club_id = ? AND read_at IS NULL"
        );
        $stmt->execute([$this->memberId, $this->clubId]);
        $this->json(['marked' => $stmt->rowCount()]);
    }
}
