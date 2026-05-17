<?php

namespace App\Models;

use PDO;

/**
 * Reguły naliczania platform fee.
 *
 * Scope:
 *   - global         — domyślna stawka dla całej platformy
 *   - plan           — per-plan (plan_code → subscription_plans.code)
 *   - club_override  — wyjątek dla konkretnego klubu (negocjowana stawka)
 */
class PlatformFeeRuleModel extends BaseModel
{
    protected string $table = 'platform_fee_rules';

    public function listAll(): array
    {
        return $this->db->query(
            "SELECT pfr.*, c.name AS club_name
               FROM {$this->table} pfr
               LEFT JOIN clubs c ON c.id = pfr.club_id
              ORDER BY scope, effective_from DESC, id DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table}
              (scope, plan_code, club_id, fee_percent, fee_fixed_cents,
               min_fee_cents, max_fee_cents, effective_from, effective_until, active)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['scope'] ?? 'global',
            $data['plan_code'] !== '' ? $data['plan_code'] : null,
            !empty($data['club_id']) ? (int)$data['club_id'] : null,
            (float)($data['fee_percent'] ?? 2.0),
            (int)($data['fee_fixed_cents'] ?? 0),
            (int)($data['min_fee_cents'] ?? 0),
            !empty($data['max_fee_cents']) ? (int)$data['max_fee_cents'] : null,
            $data['effective_from'] ?? date('Y-m-d'),
            !empty($data['effective_until']) ? $data['effective_until'] : null,
            !empty($data['active']) ? 1 : 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET
                fee_percent = ?,
                fee_fixed_cents = ?,
                min_fee_cents = ?,
                max_fee_cents = ?,
                effective_from = ?,
                effective_until = ?,
                active = ?
              WHERE id = ?"
        );
        $stmt->execute([
            (float)($data['fee_percent'] ?? 2.0),
            (int)($data['fee_fixed_cents'] ?? 0),
            (int)($data['min_fee_cents'] ?? 0),
            !empty($data['max_fee_cents']) ? (int)$data['max_fee_cents'] : null,
            $data['effective_from'] ?? date('Y-m-d'),
            !empty($data['effective_until']) ? $data['effective_until'] : null,
            !empty($data['active']) ? 1 : 0,
            $id,
        ]);
    }
}
