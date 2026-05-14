<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Master katalog feature flags (boolean włącz/wyłącz per plan).
 *
 * Master Admin zarządza katalogiem przez /admin/platform/feature-flags.
 * NIE mylić z addons (boostery limitów ilościowych) — patrz AddonCatalogModel.
 */
class FeatureFlagCatalogModel extends BaseModel
{
    protected string $table = 'feature_flags_catalog';

    /** Wszystkie flagi posortowane do listy w UI. */
    public function listAll(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM feature_flags_catalog
              ORDER BY sort_order ASC, id ASC"
        );
        return $stmt->fetchAll();
    }

    /** Tylko aktywne flagi. */
    public function listActive(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM feature_flags_catalog
              WHERE is_active = 1
              ORDER BY sort_order ASC, id ASC"
        );
        return $stmt->fetchAll();
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM feature_flags_catalog WHERE code = ? LIMIT 1");
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Wszystkie znane kody planów (z `subscription_plans`) — używane w UI
     * do edycji `default_in_plan` (macierz checkbox plan × flag).
     */
    public function listPlanCodes(): array
    {
        $stmt = $this->db->query("SELECT code FROM subscription_plans ORDER BY sort_order ASC");
        return array_map(fn($r) => (string)$r['code'], $stmt->fetchAll());
    }
}
