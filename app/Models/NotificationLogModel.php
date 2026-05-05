<?php

namespace App\Models;

/**
 * Audit log wysłanych powiadomień. Ma 2 zadania:
 *   1. Anti-spam: cron sprawdza ile razy ten sam target dostał wiadomość
 *      — `max_per_target` w rule kontroluje limit
 *   2. Audyt RODO: kto, kiedy, dlaczego dostał powiadomienie
 *
 * Pełna izolacja per klub.
 */
class NotificationLogModel extends ClubScopedModel
{
    protected string $table = 'notification_log';

    /**
     * Zlicza ile razy dany (member_id, template_type, target_id) dostał już
     * powiadomienie. Używane przed wysłaniem aby nie spamować.
     */
    public function countSentForTarget(
        int $memberId,
        string $templateType,
        string $targetType,
        ?int $targetId = null
    ): int {
        $clubId = $this->clubId();
        $sql = "SELECT COUNT(*) FROM notification_log
                WHERE club_id = ? AND member_id = ?
                  AND template_type = ? AND target_type = ?
                  AND status IN ('queued','sent')";
        $params = [$clubId, $memberId, $templateType, $targetType];
        if ($targetId !== null) {
            $sql .= " AND target_id = ?";
            $params[] = $targetId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Lista wpisów logu (do panelu admina, z filtrami).
     */
    public function listForClub(array $filters = [], int $limit = 200): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT nl.*, m.first_name, m.last_name, m.member_number
                FROM notification_log nl
                LEFT JOIN members m ON m.id = nl.member_id
                WHERE nl.club_id = ?";
        $params = [$clubId];

        if (!empty($filters['template_type'])) {
            $sql .= " AND nl.template_type = ?";
            $params[] = $filters['template_type'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND nl.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['member_id'])) {
            $sql .= " AND nl.member_id = ?";
            $params[] = (int)$filters['member_id'];
        }
        if (!empty($filters['since'])) {
            $sql .= " AND nl.created_at >= ?";
            $params[] = $filters['since'];
        }

        $sql .= " ORDER BY nl.created_at DESC LIMIT " . max(1, min(1000, $limit));
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Stats — ile zalogowano dziś (per status). Dashboard widget.
     */
    public function todayStats(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT status, COUNT(*) AS cnt
             FROM notification_log
             WHERE club_id = ? AND DATE(created_at) = CURDATE()
             GROUP BY status"
        );
        $stmt->execute([$clubId]);
        $out = ['queued' => 0, 'sent' => 0, 'failed' => 0, 'suppressed' => 0];
        foreach ($stmt->fetchAll() as $r) {
            $out[$r['status']] = (int)$r['cnt'];
        }
        return $out;
    }
}
