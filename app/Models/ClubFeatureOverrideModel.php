<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Feature;

/**
 * Override flag per klub (Master Admin tool).
 *
 * Pozwala admin platformy:
 *   - włączyć feature flag dla konkretnego klubu mimo że plan klubu jej nie ma
 *     (np. trial Pro feature dla Basic clienta na 30 dni),
 *   - wyłączyć feature flag dla klubu mimo że plan ją zawiera
 *     (np. tymczasowo dla naruszenia regulaminu).
 *
 * Override jest opcjonalny — jeśli brak override, używamy default z catalog.
 */
class ClubFeatureOverrideModel extends BaseModel
{
    protected string $table = 'club_feature_overrides';

    /**
     * Wszystkie override-y dla klubu (włącznie z wygasłymi, do UI Master Admin).
     */
    public function listForClub(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT cfo.*, ffc.name AS feature_name, ffc.category AS feature_category
               FROM club_feature_overrides cfo
          LEFT JOIN feature_flags_catalog ffc ON ffc.code = cfo.feature_code
              WHERE cfo.club_id = ?
              ORDER BY cfo.feature_code ASC"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    /** Konkretny override (klub + flaga) — null jeśli brak. */
    public function find(int $clubId, string $featureCode): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM club_feature_overrides
              WHERE club_id = ? AND feature_code = ? LIMIT 1"
        );
        $stmt->execute([$clubId, $featureCode]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Ustawia override (upsert).
     *
     * @param int    $clubId
     * @param string $featureCode
     * @param bool   $enabled
     * @param string|null $reason
     * @param string|null $expiresAt DATETIME 'Y-m-d H:i:s' lub null = trwale
     * @param int|null    $createdBy users.id admina
     */
    public function set(
        int $clubId,
        string $featureCode,
        bool $enabled,
        ?string $reason = null,
        ?string $expiresAt = null,
        ?int $createdBy = null
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO club_feature_overrides
                (club_id, feature_code, enabled, reason, expires_at, created_by)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                 enabled    = VALUES(enabled),
                 reason     = VALUES(reason),
                 expires_at = VALUES(expires_at),
                 created_by = VALUES(created_by)"
        );
        $stmt->execute([
            $clubId,
            $featureCode,
            $enabled ? 1 : 0,
            $reason,
            $expiresAt,
            $createdBy,
        ]);
        Feature::clearCache();
    }

    /** Usuwa override (klub wraca do domyślnej wartości z planu). */
    public function clear(int $clubId, string $featureCode): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM club_feature_overrides
              WHERE club_id = ? AND feature_code = ?"
        );
        $ok = $stmt->execute([$clubId, $featureCode]);
        Feature::clearCache();
        return $ok;
    }

    /** Cleanup: usuwa wygasłe override-y (do CRON). */
    public function purgeExpired(): int
    {
        $stmt = $this->db->prepare(
            "DELETE FROM club_feature_overrides
              WHERE expires_at IS NOT NULL AND expires_at <= NOW()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }
}
