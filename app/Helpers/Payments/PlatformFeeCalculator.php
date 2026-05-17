<?php

namespace App\Helpers\Payments;

use App\Helpers\Database;
use PDO;

/**
 * Oblicza platform fee (prowizja ClubDesk) dla danej transakcji online.
 *
 * Hierarchia reguł (pierwsza pasująca wygrywa):
 *   1. `platform_fee_rules.scope = 'club_override'` z club_id = $clubId
 *   2. `platform_fee_rules.scope = 'plan'` z plan_code = plan klubu
 *      (z subscription_plans przez club_subscriptions.plan_id)
 *   3. `platform_fee_rules.scope = 'global'`
 *   4. Hard fallback — 2% bez fixed (gdy migracja nie odpaliła)
 *
 * Filtrowanie: active=1 ORAZ effective_from <= dzisiaj ORAZ
 *              (effective_until IS NULL OR effective_until >= dzisiaj).
 *
 * Math:
 *   fee = round( gross * fee_percent / 100 ) + fee_fixed_cents
 *   fee = clamp(fee, min_fee_cents, max_fee_cents ?? +∞)
 *   fee = min(fee, gross)  // never larger than transaction itself
 *
 * Rounding: half-up (PHP_ROUND_HALF_UP).
 */
class PlatformFeeCalculator
{
    /** Hard fallback gdy nie ma żadnej reguły w bazie. */
    private const DEFAULT_PERCENT = 2.0;

    /** Wstrzykiwany pdo (opcjonalne dla testów); jeżeli null — Database::pdo(). */
    public function __construct(private readonly ?PDO $pdo = null)
    {
    }

    /**
     * Oblicz platform fee dla transakcji.
     *
     * @param int $clubId    ID klubu (merchanta)
     * @param int $grossCents kwota brutto w groszach/centach
     * @param string $currency  ISO 4217 (info dla UI; logika nie zależy od waluty)
     * @return array{fee_cents:int, club_net_cents:int, rule_id:?int, rule_scope:?string, fee_percent:?float, fee_fixed_cents:?int}
     */
    public function calculate(int $clubId, int $grossCents, string $currency = 'PLN'): array
    {
        if ($grossCents <= 0) {
            return [
                'fee_cents'      => 0,
                'club_net_cents' => 0,
                'rule_id'        => null,
                'rule_scope'     => null,
                'fee_percent'    => null,
                'fee_fixed_cents'=> null,
            ];
        }

        $rule = $this->resolveRule($clubId);

        $percent = $rule['fee_percent'] ?? self::DEFAULT_PERCENT;
        $fixed   = (int)($rule['fee_fixed_cents'] ?? 0);
        $minFee  = (int)($rule['min_fee_cents'] ?? 0);
        $maxFee  = isset($rule['max_fee_cents']) && $rule['max_fee_cents'] !== null
            ? (int)$rule['max_fee_cents'] : null;

        // round half-up
        $feeCents = (int)round(($grossCents * (float)$percent) / 100.0, 0, PHP_ROUND_HALF_UP) + $fixed;

        // clamp min/max
        if ($feeCents < $minFee) {
            $feeCents = $minFee;
        }
        if ($maxFee !== null && $feeCents > $maxFee) {
            $feeCents = $maxFee;
        }

        // never exceed transaction
        if ($feeCents > $grossCents) {
            $feeCents = $grossCents;
        }
        if ($feeCents < 0) {
            $feeCents = 0;
        }

        return [
            'fee_cents'       => $feeCents,
            'club_net_cents'  => $grossCents - $feeCents,
            'rule_id'         => isset($rule['id']) ? (int)$rule['id'] : null,
            'rule_scope'      => $rule['scope'] ?? null,
            'fee_percent'     => isset($rule['fee_percent']) ? (float)$rule['fee_percent'] : (float)self::DEFAULT_PERCENT,
            'fee_fixed_cents' => $fixed,
        ];
    }

    /**
     * Pobierz najbardziej specyficzną aktywną regułę dla klubu.
     * Zwraca tablicę z platform_fee_rules lub null gdy brak.
     */
    private function resolveRule(int $clubId): ?array
    {
        $pdo = $this->pdo ?? Database::pdo();

        $today = date('Y-m-d');
        $whereDate = "active = 1
                      AND effective_from <= :today
                      AND (effective_until IS NULL OR effective_until >= :today2)";

        // 1) club_override
        $stmt = $pdo->prepare(
            "SELECT * FROM platform_fee_rules
              WHERE scope = 'club_override' AND club_id = :club_id AND $whereDate
              ORDER BY effective_from DESC, id DESC LIMIT 1"
        );
        $stmt->execute([':club_id' => $clubId, ':today' => $today, ':today2' => $today]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;

        // 2) plan
        $planCode = $this->planCodeForClub($clubId);
        if ($planCode !== null) {
            $stmt = $pdo->prepare(
                "SELECT * FROM platform_fee_rules
                  WHERE scope = 'plan' AND plan_code = :code AND $whereDate
                  ORDER BY effective_from DESC, id DESC LIMIT 1"
            );
            $stmt->execute([':code' => $planCode, ':today' => $today, ':today2' => $today]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) return $row;
        }

        // 3) global
        $stmt = $pdo->prepare(
            "SELECT * FROM platform_fee_rules
              WHERE scope = 'global' AND $whereDate
              ORDER BY effective_from DESC, id DESC LIMIT 1"
        );
        $stmt->execute([':today' => $today, ':today2' => $today]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Pobierz plan_code (subscription_plans.code) klubu via club_subscriptions.
     * Null gdy brak subskrypcji albo schemat nie istnieje (test env).
     */
    private function planCodeForClub(int $clubId): ?string
    {
        $pdo = $this->pdo ?? Database::pdo();
        try {
            $stmt = $pdo->prepare(
                "SELECT sp.code
                   FROM club_subscriptions cs
                   JOIN subscription_plans sp ON sp.id = cs.plan_id
                  WHERE cs.club_id = :club_id LIMIT 1"
            );
            $stmt->execute([':club_id' => $clubId]);
            $code = $stmt->fetchColumn();
            return $code !== false ? (string)$code : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
