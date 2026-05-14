<?php

namespace App\Models;

/**
 * Model zgloszen bledow/propozycji (support_reports).
 *
 * UWAGA: NIE rozszerzamy ClubScopedModel — niektore tickety beda zglaszane
 * przez portal-member (sesja portalu, bez ClubContext z panelu klubu).
 */
class SupportReportModel extends BaseModel
{
    protected string $table = 'support_reports';

    /**
     * Lista zgloszen dla klubu z opcjonalnym filtrem statusu.
     * @return array<int,array<string,mixed>>
     */
    public function listForClub(int $clubId, ?string $status = null, int $limit = 100): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `club_id` = ?";
        $params = [$clubId];
        if ($status !== null && $status !== '') {
            $sql .= " AND `status` = ?";
            $params[] = $status;
        }
        $limit = max(1, min(1000, $limit));
        $sql .= " ORDER BY `created_at` DESC LIMIT {$limit}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Zgloszenia konkretnego usera klubowego.
     * @return array<int,array<string,mixed>>
     */
    public function recentForUser(int $userId, int $limit = 50): array
    {
        $limit = max(1, min(500, $limit));
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE `user_id` = ? ORDER BY `created_at` DESC LIMIT {$limit}"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Zgloszenia konkretnego portal-membera.
     * @return array<int,array<string,mixed>>
     */
    public function recentForMember(int $memberId, int $limit = 50): array
    {
        $limit = max(1, min(500, $limit));
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE `member_id` = ? ORDER BY `created_at` DESC LIMIT {$limit}"
        );
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    /**
     * Ile zgloszen utworzyl dany uzytkownik/member w ostatniej godzinie.
     * Wykorzystywane do rate-limitingu (max 5 / h).
     */
    public function countRecentBySubmitter(?int $userId, ?int $memberId, int $minutes = 60): int
    {
        if ($userId === null && $memberId === null) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM `{$this->table}` WHERE `created_at` >= (NOW() - INTERVAL ? MINUTE) AND (";
        $parts = [];
        $params = [$minutes];
        if ($userId !== null) {
            $parts[] = "`user_id` = ?";
            $params[] = $userId;
        }
        if ($memberId !== null) {
            $parts[] = "`member_id` = ?";
            $params[] = $memberId;
        }
        $sql .= implode(' OR ', $parts) . ')';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}
