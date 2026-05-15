<?php

namespace App\Models;

class AnnouncementReadModel extends BaseModel
{
    protected string $table = 'announcement_reads';

    public function markRead(int $announcementId, int $memberId): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO announcement_reads (announcement_id, member_id, read_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE read_at = read_at"
        );
        $stmt->execute([$announcementId, $memberId]);
    }

    public function isRead(int $announcementId, int $memberId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM announcement_reads WHERE announcement_id = ? AND member_id = ?"
        );
        $stmt->execute([$announcementId, $memberId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * @return array<int,string> map announcement_id => read_at
     */
    public function readIdsForMember(int $memberId, array $announcementIds): array
    {
        if (empty($announcementIds)) {
            return [];
        }
        $ids   = array_values(array_map('intval', $announcementIds));
        $place = implode(',', array_fill(0, count($ids), '?'));
        $stmt  = $this->db->prepare(
            "SELECT announcement_id, read_at FROM announcement_reads
             WHERE member_id = ? AND announcement_id IN ({$place})"
        );
        $stmt->execute([$memberId, ...$ids]);

        $out = [];
        foreach ($stmt->fetchAll() as $r) {
            $out[(int)$r['announcement_id']] = $r['read_at'];
        }
        return $out;
    }
}
