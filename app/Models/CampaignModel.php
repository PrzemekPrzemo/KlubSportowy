<?php

namespace App\Models;

/**
 * Masowe kampanie email/SMS dla zarządu klubu.
 *
 * Workflow:
 *   1. Admin tworzy kampanię → status='draft'/'scheduled'/'sending'
 *   2. Resolver buduje listę odbiorców (campaign_recipients) ze snapshotu filtra
 *   3. CLI `cli/send_campaigns.php` wybiera kampanie 'sending' i wysyła
 *   4. Po pełnym przejściu → status='sent'
 *
 * Izolacja per klub (ClubScopedModel).
 */
class CampaignModel extends ClubScopedModel
{
    protected string $table = 'campaigns';

    public static array $STATUSES = [
        'draft'     => 'Szkic',
        'scheduled' => 'Zaplanowana',
        'sending'   => 'W wysyłce',
        'sent'      => 'Wysłana',
        'failed'    => 'Błąd',
    ];

    public static array $CHANNELS = [
        'email' => 'E-mail',
        'sms'   => 'SMS',
        'both'  => 'E-mail + SMS',
    ];

    /** Lista kampanii klubu, najnowsze pierwsze. */
    public function listForClub(int $limit = 100): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT c.*, u.full_name AS created_by_name
             FROM campaigns c
             LEFT JOIN users u ON u.id = c.created_by
             WHERE c.club_id = ?
             ORDER BY c.created_at DESC
             LIMIT " . max(1, $limit)
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    /** Aktualizuje statystyki kampanii (sent/failed). */
    public function refreshStats(int $campaignId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE campaigns c
             SET sent_count   = (SELECT COUNT(*) FROM campaign_recipients WHERE campaign_id = c.id AND status = 'sent'),
                 failed_count = (SELECT COUNT(*) FROM campaign_recipients WHERE campaign_id = c.id AND status IN ('failed','bounced'))
             WHERE c.id = ?"
        );
        $stmt->execute([$campaignId]);
    }

    /**
     * Cross-tenant: kampanie gotowe do wysyłki (status=sending lub scheduled w przeszłości).
     * Używane przez CLI worker.
     */
    public function fetchReadyToSend(int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM campaigns
             WHERE status = 'sending'
                OR (status = 'scheduled' AND scheduled_at IS NOT NULL AND scheduled_at <= NOW())
             ORDER BY scheduled_at ASC, id ASC
             LIMIT " . max(1, $limit)
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function markStatus(int $campaignId, string $status): bool
    {
        $extra = '';
        if ($status === 'sent') $extra = ', sent_at = NOW()';
        $stmt = $this->db->prepare(
            "UPDATE campaigns SET status = ?{$extra} WHERE id = ?"
        );
        return $stmt->execute([$status, $campaignId]);
    }
}
