<?php

namespace App\Models;

class MemberNotificationModel extends BaseModel
{
    protected string $table = 'member_notifications';

    public function notify(int $memberId, int $clubId, string $type, string $title, ?string $body = null, ?string $link = null): void
    {
        $this->insert([
            'member_id' => $memberId,
            'club_id'   => $clubId,
            'type'      => $type,
            'title'     => $title,
            'body'      => $body,
            'link'      => $link,
        ]);
    }

    public function unreadForMember(int $memberId, int $clubId, int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM member_notifications
             WHERE member_id = ? AND club_id = ? AND read_at IS NULL
             ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$memberId, $clubId, $limit]);
        return $stmt->fetchAll();
    }

    public function allForMember(int $memberId, int $clubId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM member_notifications
             WHERE member_id = ? AND club_id = ?
             ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$memberId, $clubId, $limit]);
        return $stmt->fetchAll();
    }

    public function markRead(int $id, int $memberId): void
    {
        $this->db->prepare(
            "UPDATE member_notifications SET read_at = NOW() WHERE id = ? AND member_id = ? AND read_at IS NULL"
        )->execute([$id, $memberId]);
    }

    public function countUnread(int $memberId, int $clubId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM member_notifications WHERE member_id = ? AND club_id = ? AND read_at IS NULL"
        );
        $stmt->execute([$memberId, $clubId]);
        return (int)$stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM member_notifications WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function purgeOld(int $days = 90): int
    {
        $stmt = $this->db->prepare(
            "DELETE FROM member_notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }
}
