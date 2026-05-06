<?php

namespace App\Models;

/**
 * Q.2 — Aktywne subskrypcje addonow per klub.
 *
 * Status:
 *   - active     — działa, boost-uje limity
 *   - cancelled  — klub anulował (działa do valid_until)
 *   - suspended  — np. brak płatności (admin manualnie reaktywuje)
 *   - expired    — automatycznie po valid_until
 *
 * Dodanie addona: ClubAddonModel::subscribe($clubId, $addonCode, $quantity).
 * Anulowanie: ::cancel($id) — zostaje active do valid_until, potem expired.
 */
class ClubAddonModel extends BaseModel
{
    protected string $table = 'club_addons';

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_EXPIRED   = 'expired';

    /**
     * Aktywuj addon dla klubu. Zwraca id nowo utworzonego wpisu.
     * Snapshot ceny w momencie zakupu — późniejsze zmiany cen nie wpływają.
     */
    public function subscribe(int $clubId, string $addonCode, int $quantity = 1, ?string $validUntil = null): int
    {
        $catalog = (new AddonCatalogModel())->findByCode($addonCode);
        if (!$catalog) {
            throw new \InvalidArgumentException("Addon '{$addonCode}' nie istnieje w katalogu.");
        }
        if (!$catalog['is_active']) {
            throw new \InvalidArgumentException("Addon '{$addonCode}' jest nieaktywny.");
        }

        $price = (float)$catalog['monthly_price'] * max(1, $quantity);
        $stmt = $this->db->prepare(
            "INSERT INTO club_addons
                (club_id, addon_id, quantity, monthly_price, status, valid_from, valid_until, auto_renew)
             VALUES (?, ?, ?, ?, 'active', CURDATE(), ?, 1)"
        );
        $stmt->execute([
            $clubId,
            (int)$catalog['id'],
            $quantity,
            $price,
            $validUntil,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Anuluj addon — zostaje aktywny do końca valid_until (nie traci od razu).
     * Gdy auto_renew był true, ustawia false i status='cancelled'.
     */
    public function cancel(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE club_addons
                SET status = 'cancelled', auto_renew = 0,
                    valid_until = COALESCE(valid_until, DATE_ADD(CURDATE(), INTERVAL 30 DAY))
              WHERE id = ? AND status IN ('active','suspended')"
        );
        return $stmt->execute([$id]);
    }

    /** Reaktywuj wcześniej anulowany addon (jeśli wciąż valid_until). */
    public function reactivate(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE club_addons
                SET status = 'active', auto_renew = 1
              WHERE id = ? AND status = 'cancelled'
                AND (valid_until IS NULL OR valid_until >= CURDATE())"
        );
        return $stmt->execute([$id]);
    }

    /**
     * CRON-friendly: oznacz wygasłe addon-y jako expired.
     * Wywoływane np. raz dziennie z cli/cron_addons.php.
     */
    public function expireOverdue(): int
    {
        $stmt = $this->db->prepare(
            "UPDATE club_addons
                SET status = 'expired'
              WHERE status IN ('active','cancelled')
                AND valid_until IS NOT NULL
                AND valid_until < CURDATE()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Sumaryczny miesięczny koszt addonów klubu (dla "kup teraz" total + faktur).
     */
    public function monthlyCostForClub(int $clubId): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(monthly_price), 0) FROM club_addons
              WHERE club_id = ? AND status = 'active'
                AND (valid_until IS NULL OR valid_until >= CURDATE())"
        );
        $stmt->execute([$clubId]);
        return (float)$stmt->fetchColumn();
    }
}
