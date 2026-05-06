<?php

namespace App\Models;

/**
 * Q.2 — Katalog dostępnych addonow do dokupienia przez klub.
 *
 * Master Admin zarządza katalogiem (CRUD przez /admin/platform/addons).
 * Klub widzi tylko aktywne (is_active = 1) na stronie /club/subscription/addons.
 */
class AddonCatalogModel extends BaseModel
{
    protected string $table = 'addon_catalog';

    public const CATEGORY_MEMBERS = 'members';
    public const CATEGORY_SPORTS  = 'sports';
    public const CATEGORY_SMS     = 'sms';
    public const CATEGORY_DOMAIN  = 'domain';
    public const CATEGORY_SUPPORT = 'support';
    public const CATEGORY_OTHER   = 'other';

    public static array $CATEGORIES = [
        self::CATEGORY_MEMBERS => 'Limity zawodników',
        self::CATEGORY_SPORTS  => 'Limity sekcji sportowych',
        self::CATEGORY_SMS     => 'Pakiety SMS',
        self::CATEGORY_DOMAIN  => 'Domena',
        self::CATEGORY_SUPPORT => 'Wsparcie',
        self::CATEGORY_OTHER   => 'Inne',
    ];

    /** Lista aktywnych addonow do wyświetlenia klubowi (sortowane). */
    public function listActive(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM addon_catalog
              WHERE is_active = 1
              ORDER BY sort_order ASC, id ASC"
        );
        return $stmt->fetchAll();
    }

    /** Pobiera addon po kodzie (np. 'extra_members_50'). */
    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM addon_catalog WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Lista addonow zgrupowanych per kategoria (dla UI z sekcjami). */
    public function listGroupedByCategory(): array
    {
        $items = $this->listActive();
        $grouped = [];
        foreach ($items as $item) {
            $cat = $item['category'] ?? 'other';
            $grouped[$cat] = $grouped[$cat] ?? [];
            $grouped[$cat][] = $item;
        }
        return $grouped;
    }
}
