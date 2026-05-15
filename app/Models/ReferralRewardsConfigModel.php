<?php

namespace App\Models;

/**
 * Migracja 081: referral_rewards_config.
 *
 * Konfiguracja rewardow programu polecen. Domyslnie jeden aktywny
 * rekord (20% rabatu na 1 miesiac). Super admin moze dodawac wiecej
 * np. specjalne kampanie ("Black Friday — 30% za polecenie").
 */
class ReferralRewardsConfigModel extends BaseModel
{
    protected string $table = 'referral_rewards_config';

    /** Pobiera obecnie obowiazujacy aktywny reward (pierwsza pasujaca konfiguracja). */
    public function getActiveReward(): ?array
    {
        $stmt = $this->db->query(
            "SELECT * FROM `{$this->table}`
              WHERE is_active = 1
                AND (valid_from  IS NULL OR valid_from  <= CURDATE())
                AND (valid_until IS NULL OR valid_until >= CURDATE())
              ORDER BY id DESC
              LIMIT 1"
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int,array<string,mixed>> */
    public function listAll(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM `{$this->table}` ORDER BY is_active DESC, id DESC"
        );
        return $stmt->fetchAll();
    }
}
