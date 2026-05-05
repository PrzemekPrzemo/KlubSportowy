<?php

namespace App\Models;

/**
 * Zniżki klubowe — % lub kwota stała, warunki JSON.
 *
 * Pełna izolacja per klub przez ClubScopedModel — każdy klub ma własne
 * zniżki niewidoczne dla innych klubów (egzekwowane WHERE club_id=?).
 *
 * Typy zniżek:
 *   - percent       — procent (0-100), value=20.00 → -20%
 *   - fixed_amount  — kwota stała (PLN), value=50.00 → -50zł
 *
 * Warunki auto-stosowania (conditions JSON):
 *   {"min_active_sports": 2}            — multi-sport (zawodnik w >=2 sekcjach)
 *   {"age_max": 18}                     — junior (do 18. roku życia)
 *   {"age_min": 60}                     — senior
 *   {"family_min_members": 2}           — rabat rodzinny (>=2 czl. tej rodziny)
 *   {"role": "scholarship"}             — stypendysta
 *
 * Stackable (is_stackable=1) → można łączyć z innymi zniżkami.
 */
class FeeDiscountModel extends ClubScopedModel
{
    protected string $table = 'fee_discounts';

    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED   = 'fixed_amount';

    public static array $TYPES = [
        self::TYPE_PERCENT => 'Procent (%)',
        self::TYPE_FIXED   => 'Kwota stała (PLN)',
    ];

    /**
     * Lista wszystkich zniżek klubu (aktywnych + nieaktywnych) z paginacją.
     */
    public function listForClub(?bool $onlyActive = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT * FROM `{$this->table}` WHERE club_id = ?";
        $params = [$clubId];
        if ($onlyActive !== null) {
            $sql .= " AND is_active = ?";
            $params[] = $onlyActive ? 1 : 0;
        }
        $sql .= " ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Pobierz zniżkę po code (unikalna per klub).
     */
    public function findByCode(string $code): ?array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE club_id = ? AND code = ? LIMIT 1"
        );
        $stmt->execute([$clubId, $code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Aktywne + ważne czasowo (valid_from/valid_to) zniżki na dziś.
     * Używane przez logikę auto-stosowania na liście dostępnych dla zawodnika.
     */
    public function activeOnDate(string $date): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE club_id = ?
               AND is_active = 1
               AND (valid_from IS NULL OR valid_from <= ?)
               AND (valid_to   IS NULL OR valid_to   >= ?)
             ORDER BY name"
        );
        $stmt->execute([$clubId, $date, $date]);
        return $stmt->fetchAll();
    }

    /**
     * Oblicz kwotę zniżki dla danej brutto. Helper kalkulacyjny:
     * percent      → grossAmount * (value/100)
     * fixed_amount → min(value, grossAmount)
     */
    public static function calculateDiscountAmount(array $discount, float $grossAmount): float
    {
        $type = $discount['discount_type'] ?? self::TYPE_PERCENT;
        $value = (float)($discount['value'] ?? 0);
        if ($type === self::TYPE_PERCENT) {
            return round($grossAmount * ($value / 100), 2);
        }
        return min($value, $grossAmount);
    }
}
