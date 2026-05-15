<?php

namespace App\Models;

/**
 * Katalog dostepnych eventow ktore moga wyzwalac email.
 * Globalny (nie per-klub) — to lista mozliwosci, klub moze nadpisac
 * konkretny szablon przez email_templates.
 */
class EmailEventCatalogModel extends BaseModel
{
    protected string $table = 'email_event_catalog';

    /** Wszystkie aktywne eventy posortowane po category + sort_order. */
    public function listActive(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM `email_event_catalog`
             WHERE is_active = 1
             ORDER BY category, sort_order, name"
        );
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['available_variables'] = self::decodeVars($r['available_variables'] ?? null);
        }
        return $rows;
    }

    /** Eventy pogrupowane po kategorii. */
    public function listByCategory(): array
    {
        $grouped = [];
        foreach ($this->listActive() as $row) {
            $grouped[$row['category']][] = $row;
        }
        return $grouped;
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `email_event_catalog` WHERE code = ? AND is_active = 1 LIMIT 1"
        );
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['available_variables'] = self::decodeVars($row['available_variables'] ?? null);
        return $row;
    }

    private static function decodeVars(?string $json): array
    {
        if ($json === null || $json === '') return [];
        $d = json_decode($json, true);
        return is_array($d) ? $d : [];
    }
}
