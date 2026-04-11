<?php

namespace App\Models;

class EmailQueueModel extends ClubScopedModel
{
    protected string $table = 'email_queue';

    public function enqueue(int $clubId, string $toEmail, ?string $toName, string $subject, string $body, ?string $templateType = null, ?int $userId = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO email_queue (club_id, to_email, to_name, subject, body, template_type, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)"
        );
        $stmt->execute([$clubId, $toEmail, $toName, $subject, $body, $templateType, $userId]);
        return (int)$this->db->lastInsertId();
    }

    public function pending(int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM email_queue
             WHERE status = 'pending'
               AND (scheduled_at IS NULL OR scheduled_at <= NOW())
             ORDER BY created_at ASC
             LIMIT " . (int)$limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function markSending(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE email_queue SET status = 'sending' WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function markSent(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function markFailed(int $id, string $error): void
    {
        $stmt = $this->db->prepare(
            "UPDATE email_queue SET status = 'failed', attempts = attempts + 1, error = ? WHERE id = ?"
        );
        $stmt->execute([$error, $id]);
    }

    public function listForClub(?string $status = null, int $page = 1, int $perPage = 30): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT * FROM email_queue WHERE 1=1";
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
