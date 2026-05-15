<?php

namespace App\Controllers\Api;

use App\Helpers\Database;
use App\Models\AnnouncementReadModel;

class AnnouncementsApiController extends BaseApiController
{
    public function index(): void
    {
        $this->requireMember();

        $cursor = isset($_GET['cursor']) && (int)$_GET['cursor'] > 0 ? (int)$_GET['cursor'] : null;
        $limit  = max(1, min(50, (int)($_GET['limit'] ?? 20)));

        $sql = "SELECT a.* FROM announcements a
                WHERE a.club_id = ?
                  AND a.target IN ('members','all','public')
                  AND a.published = 1
                  AND (a.publish_from IS NULL OR a.publish_from <= NOW())
                  AND (a.publish_to   IS NULL OR a.publish_to   >= NOW())";
        $params = [$this->clubId];
        if ($cursor !== null) {
            $sql .= " AND a.id < ?";
            $params[] = $cursor;
        }
        $sql .= " ORDER BY FIELD(a.priority,'urgent','important','normal'), a.created_at DESC, a.id DESC
                  LIMIT " . ($limit + 1);

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            array_pop($rows);
        }

        $ids   = array_column($rows, 'id');
        $reads = (new AnnouncementReadModel())->readIdsForMember($this->memberId, $ids);
        foreach ($rows as &$r) {
            $r['read_at'] = $reads[(int)$r['id']] ?? null;
        }
        unset($r);

        $this->json([
            'data'        => $rows,
            'next_cursor' => $hasMore && !empty($rows) ? (int)end($rows)['id'] : null,
        ]);
    }

    public function show(string $id): void
    {
        $this->requireMember();
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM announcements
             WHERE id = ? AND club_id = ?
               AND target IN ('members','all','public')
               AND published = 1
               AND (publish_from IS NULL OR publish_from <= NOW())
               AND (publish_to   IS NULL OR publish_to   >= NOW())
             LIMIT 1"
        );
        $stmt->execute([(int)$id, $this->clubId]);
        $row = $stmt->fetch();
        if (!$row) {
            $this->error('Ogłoszenie nie istnieje.', 404, 'not_found');
        }
        $rd = Database::pdo()->prepare(
            "SELECT read_at FROM announcement_reads WHERE announcement_id = ? AND member_id = ?"
        );
        $rd->execute([(int)$row['id'], $this->memberId]);
        $row['read_at'] = $rd->fetchColumn() ?: null;
        $this->json(['data' => $row]);
    }

    public function markRead(string $id): void
    {
        $this->requireMember();
        $stmt = Database::pdo()->prepare(
            "SELECT id FROM announcements WHERE id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([(int)$id, $this->clubId]);
        if (!$stmt->fetchColumn()) {
            $this->error('Ogłoszenie nie istnieje.', 404, 'not_found');
        }
        (new AnnouncementReadModel())->markRead((int)$id, $this->memberId);
        $this->json(['status' => 'ok']);
    }
}
