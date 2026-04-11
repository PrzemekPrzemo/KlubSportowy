<?php

namespace App\Models;

class SmsQueueModel extends ClubScopedModel
{
    protected string $table = 'sms_queue';

    public function enqueue(int $clubId, string $toPhone, ?string $toName, string $message, ?int $userId = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO sms_queue (club_id, to_phone, to_name, message, status, created_by)
             VALUES (?, ?, ?, ?, 'pending', ?)"
        );
        $stmt->execute([$clubId, $toPhone, $toName, $message, $userId]);
        return (int)$this->db->lastInsertId();
    }

    public function pending(int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sms_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT " . (int)$limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function markSending(int $id): void
    {
        $this->db->prepare("UPDATE sms_queue SET status='sending' WHERE id = ?")->execute([$id]);
    }

    public function markSent(int $id): void
    {
        $this->db->prepare("UPDATE sms_queue SET status='sent', sent_at = NOW() WHERE id = ?")->execute([$id]);
    }

    public function markFailed(int $id, string $error): void
    {
        $this->db->prepare(
            "UPDATE sms_queue SET status='failed', attempts = attempts + 1, error = ? WHERE id = ?"
        )->execute([$error, $id]);
    }

    public function listForClub(?string $status = null, int $page = 1, int $perPage = 30): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT * FROM sms_queue WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND club_id = ?"; $params[] = $clubId; }
        if ($status !== null && $status !== '') {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY created_at DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }
}
