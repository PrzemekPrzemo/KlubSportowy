<?php

namespace App\Controllers\Api;

use App\Helpers\Database;
use App\Models\MemberNotificationModel;

class NotificationsApiController extends BaseApiController
{
    public function index(): void
    {
        $this->requireMember();
        $cursor = isset($_GET['cursor']) && (int)$_GET['cursor'] > 0 ? (int)$_GET['cursor'] : null;
        $limit  = max(1, min(50, (int)($_GET['limit'] ?? 20)));
        $result = (new MemberNotificationModel())->forMember($this->memberId, $cursor, $limit);
        $this->json($result);
    }

    public function markRead(string $id): void
    {
        $this->requireMember();
        (new MemberNotificationModel())->markRead((int)$id, $this->memberId);
        $this->json(['status' => 'ok']);
    }

    public function markAllRead(): void
    {
        $this->requireMember();
        $n = (new MemberNotificationModel())->markAllRead($this->memberId);
        $this->json(['status' => 'ok', 'marked' => $n]);
    }

    public function unreadCount(): void
    {
        $this->requireMember();
        $this->json(['unread' => (new MemberNotificationModel())->unreadCount($this->memberId)]);
    }
}
