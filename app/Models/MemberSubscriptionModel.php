<?php

namespace App\Models;

/**
 * Subskrypcje cykliczne składek członkowskich (Stripe Subscriptions / P24 recurring).
 *
 * Multi-tenant: każdy rekord scoped per klub (club_id). Klub używa
 * własnych credentials z ClubPaymentGatewayModel — webhooki przychodzące
 * rozpoznają klub po external_subscription_id (UNIQUE) lub metadata.club_id.
 */
class MemberSubscriptionModel extends ClubScopedModel
{
    protected string $table = 'member_subscriptions';

    public const STATUSES = [
        'pending_setup' => 'Oczekuje konfiguracji',
        'active'        => 'Aktywna',
        'paused'        => 'Wstrzymana',
        'cancelled'     => 'Anulowana',
        'past_due'      => 'Zaległa',
        'expired'       => 'Wygasła',
    ];

    public const PERIODS = [
        'monthly'   => ['label' => 'Miesięcznie',  'months' => 1,  'stripe_interval' => 'month', 'stripe_count' => 1],
        'quarterly' => ['label' => 'Kwartalnie',   'months' => 3,  'stripe_interval' => 'month', 'stripe_count' => 3],
        'yearly'    => ['label' => 'Rocznie',      'months' => 12, 'stripe_interval' => 'year',  'stripe_count' => 1],
    ];

    /**
     * Lista subskrypcji członka (scoped per klub przez ClubScopedModel).
     */
    public function forMember(int $memberId): array
    {
        $clubId = $this->clubId();
        if ($clubId === null) return [];

        $stmt = $this->db->prepare(
            "SELECT ms.*, fr.name AS fee_name
             FROM member_subscriptions ms
             LEFT JOIN fee_rates fr ON fr.id = ms.fee_rate_id
             WHERE ms.club_id = ? AND ms.member_id = ?
             ORDER BY ms.created_at DESC"
        );
        $stmt->execute([$clubId, $memberId]);
        return $stmt->fetchAll();
    }

    /**
     * Lista wszystkich subskrypcji w klubie (admin).
     */
    public function listForClub(?string $status = null): array
    {
        $clubId = $this->clubId();
        if ($clubId === null) return [];

        $sql = "SELECT ms.*, fr.name AS fee_name,
                       m.first_name, m.last_name, m.email
                FROM member_subscriptions ms
                LEFT JOIN fee_rates fr ON fr.id = ms.fee_rate_id
                LEFT JOIN members m    ON m.id  = ms.member_id
                WHERE ms.club_id = ?";
        $params = [$clubId];

        if ($status !== null && $status !== '') {
            $sql .= " AND ms.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY ms.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Znajdź po external_subscription_id (webhook routing — nie scoped, bo
     * webhook musi znaleźć rekord zanim wybierze klub).
     */
    public function findByExternalSubscriptionId(string $provider, string $externalId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM member_subscriptions
             WHERE gateway_provider = ? AND external_subscription_id = ?
             LIMIT 1"
        );
        $stmt->execute([$provider, $externalId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Znajdź po setup_session_id — używane po returnie z checkout.
     */
    public function findBySetupSession(string $sessionId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM member_subscriptions WHERE setup_session_id = ? LIMIT 1"
        );
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Lista P24 subskrypcji do chargeu — używane przez CLI cron.
     */
    public function dueP24Charges(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM member_subscriptions
             WHERE status = 'active'
               AND gateway_provider = 'przelewy24'
               AND next_charge_at IS NOT NULL
               AND next_charge_at <= NOW()"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Update status (bez scope) — webhook handler, nie zna club_id naprędce.
     * Zwraca true gdy update zmienił row.
     */
    public function updateStatusByExternalId(string $provider, string $externalId, array $fields): bool
    {
        if (empty($fields)) return false;
        $sets = [];
        $params = [];
        foreach ($fields as $k => $v) {
            $sets[] = "`{$k}` = ?";
            $params[] = $v;
        }
        $params[] = $provider;
        $params[] = $externalId;
        $sql = "UPDATE member_subscriptions SET " . implode(', ', $sets)
             . " WHERE gateway_provider = ? AND external_subscription_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Helper — przelicz next_charge_at na podstawie billing_period.
     */
    public static function calcNextCharge(\DateTimeInterface $from, string $period): \DateTime
    {
        $months = self::PERIODS[$period]['months'] ?? 1;
        $next = (new \DateTime())->setTimestamp($from->getTimestamp());
        $next->modify('+' . $months . ' months');
        return $next;
    }
}
