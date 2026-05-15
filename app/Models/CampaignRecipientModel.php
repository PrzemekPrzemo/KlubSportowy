<?php

namespace App\Models;

/**
 * Pojedynczy odbiorca kampanii — per (campaign, member, channel).
 * Brak club_id na tabeli (FK cascade z campaigns). Operujemy zawsze
 * w kontekście kampanii — która sama jest scoped do club_id.
 */
class CampaignRecipientModel extends BaseModel
{
    protected string $table = 'campaign_recipients';

    /**
     * Insert masowy: array of [campaign_id, member_id, channel, to_address].
     * Używa multi-row INSERT dla wydajności.
     */
    public function insertBatch(int $campaignId, array $rows): int
    {
        if (empty($rows)) return 0;
        $values     = [];
        $params     = [];
        foreach ($rows as $r) {
            $values[] = '(?, ?, ?, ?, ?)';
            $params[] = $campaignId;
            $params[] = (int)$r['member_id'];
            $params[] = $r['channel'];
            $params[] = $r['to_address'];
            $params[] = 'queued';
        }
        $sql = "INSERT INTO campaign_recipients
                (campaign_id, member_id, channel, to_address, status)
                VALUES " . implode(', ', $values);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** Następna paczka do wysłki dla kampanii. */
    public function nextQueued(int $campaignId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM campaign_recipients
             WHERE campaign_id = ? AND status = 'queued'
             ORDER BY id ASC
             LIMIT " . max(1, $limit)
        );
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }

    public function markSent(int $recipientId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE campaign_recipients SET status='sent', sent_at=NOW() WHERE id = ?"
        );
        return $stmt->execute([$recipientId]);
    }

    public function markFailed(int $recipientId, string $error): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE campaign_recipients
             SET status='failed', error_message=?, sent_at=NOW()
             WHERE id = ?"
        );
        return $stmt->execute([substr($error, 0, 500), $recipientId]);
    }

    public function countQueued(int $campaignId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM campaign_recipients
             WHERE campaign_id = ? AND status = 'queued'"
        );
        $stmt->execute([$campaignId]);
        return (int)$stmt->fetchColumn();
    }

    public function listForCampaign(int $campaignId, int $limit = 200): array
    {
        $stmt = $this->db->prepare(
            "SELECT cr.*, m.first_name, m.last_name, m.member_number
             FROM campaign_recipients cr
             JOIN members m ON m.id = cr.member_id
             WHERE cr.campaign_id = ?
             ORDER BY cr.id ASC
             LIMIT " . max(1, $limit)
        );
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }
}
