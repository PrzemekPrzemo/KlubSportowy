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

    /**
     * Cursor-paginated list for mobile inbox. Cursor is the last seen id;
     * uses id-DESC ordering (created_at correlates with id for inserts).
     */
    public function forMember(int $memberId, ?int $cursor = null, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $sql   = "SELECT * FROM member_notifications WHERE member_id = ?";
        $params = [$memberId];
        if ($cursor !== null && $cursor > 0) {
            $sql .= " AND id < ?";
            $params[] = $cursor;
        }
        $sql .= " ORDER BY id DESC LIMIT " . ($limit + 1);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            array_pop($rows);
        }
        $nextCursor = $hasMore && !empty($rows) ? (int)end($rows)['id'] : null;

        foreach ($rows as &$r) {
            if (isset($r['data']) && is_string($r['data']) && $r['data'] !== '') {
                $r['data'] = json_decode($r['data'], true);
            }
        }
        unset($r);

        return [
            'data'        => $rows,
            'next_cursor' => $nextCursor,
        ];
    }

    public function markAllRead(int $memberId): int
    {
        $stmt = $this->db->prepare(
            "UPDATE member_notifications SET read_at = NOW()
             WHERE member_id = ? AND read_at IS NULL"
        );
        $stmt->execute([$memberId]);
        return $stmt->rowCount();
    }

    public function unreadCount(int $memberId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM member_notifications WHERE member_id = ? AND read_at IS NULL"
        );
        $stmt->execute([$memberId]);
        return (int)$stmt->fetchColumn();
    }

    public function create(int $memberId, int $clubId, string $type, string $title, ?string $body = null, ?array $data = null): int
    {
        return $this->insert([
            'member_id' => $memberId,
            'club_id'   => $clubId,
            'type'      => $type,
            'title'     => $title,
            'body'      => $body,
            'data'      => $data !== null ? json_encode($data, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }
}
