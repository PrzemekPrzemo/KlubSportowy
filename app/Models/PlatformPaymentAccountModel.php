<?php

namespace App\Models;

use PDO;

/**
 * Model konta merchanta klubu u dostawcy split-payments
 * (Stripe Connect lub P24 Marketplace).
 */
class PlatformPaymentAccountModel extends BaseModel
{
    protected string $table = 'platform_payment_accounts';

    public function findByClubProvider(int $clubId, string $provider): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE club_id = ? AND provider = ? LIMIT 1"
        );
        $stmt->execute([$clubId, $provider]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listAll(): array
    {
        return $this->db->query(
            "SELECT ppa.*, c.name AS club_name
               FROM {$this->table} ppa
               LEFT JOIN clubs c ON c.id = ppa.club_id
              ORDER BY ppa.created_at DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function upsertAccount(
        int $clubId,
        string $provider,
        string $externalId,
        string $accountType = 'express',
        string $country = 'PL',
        string $currency = 'PLN'
    ): int {
        $existing = $this->findByClubProvider($clubId, $provider);
        if ($existing) {
            return (int)$existing['id'];
        }
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table}
              (club_id, provider, external_account_id, account_type, country, default_currency)
              VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$clubId, $provider, $externalId, $accountType, $country, $currency]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Zsynchronizuj status z provider API.
     */
    public function syncStatus(
        int $id,
        string $kycStatus,
        bool $chargesEnabled,
        bool $payoutsEnabled,
        array $capabilities,
        bool $onboardingComplete
    ): void {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
                SET kyc_status = ?,
                    charges_enabled = ?,
                    payouts_enabled = ?,
                    capabilities = ?,
                    onboarding_complete = ?,
                    onboarded_at = CASE WHEN ? = 1 AND onboarded_at IS NULL THEN NOW() ELSE onboarded_at END
              WHERE id = ?"
        );
        $stmt->execute([
            $kycStatus,
            $chargesEnabled ? 1 : 0,
            $payoutsEnabled ? 1 : 0,
            json_encode($capabilities, JSON_UNESCAPED_UNICODE),
            $onboardingComplete ? 1 : 0,
            $onboardingComplete ? 1 : 0,
            $id,
        ]);
    }

    public function isClubReady(int $clubId, string $provider): bool
    {
        $row = $this->findByClubProvider($clubId, $provider);
        return $row !== null
            && (int)$row['onboarding_complete'] === 1
            && (int)$row['charges_enabled'] === 1;
    }
}
