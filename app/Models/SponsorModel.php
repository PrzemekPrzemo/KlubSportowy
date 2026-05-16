<?php

namespace App\Models;

/**
 * Sponsorzy per-klub.
 *
 * Multi-tenant: WSZYSTKIE zapytania filtrujemy explicit po club_id —
 * nie polegamy na global scope (brak ClubScopedModel zachowania tu),
 * bo część operacji uruchamiana jest z CLI (expiry cron) bez kontekstu klubu.
 */
class SponsorModel extends BaseModel
{
    protected string $table = 'sponsors';

    /**
     * Lista wszystkich sponsorów klubu (panel admin) — z sortowaniem
     * by display_weight, potem tier ordinal (platinum first), potem name.
     */
    public function forClub(int $clubId): array
    {
        $sql = "SELECT *,
                       FIELD(tier,'platinum','gold','silver','bronze','partner') AS tier_ord,
                       DATEDIFF(contract_end, CURDATE()) AS days_to_expiry
                FROM sponsors
                WHERE club_id = ?
                ORDER BY active DESC, tier_ord ASC, display_weight ASC, name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    /**
     * Aktywne sponsorzy do wyświetlenia w danym kontekście (portal/email/landing).
     *
     * Filtry:
     *   - active=1
     *   - display_in_X=1 dla danego kontekstu
     *   - kontrakt aktywny TODAY (start IS NULL OR start<=today) AND (end IS NULL OR end>=today)
     *
     * Sortowanie: display_weight ASC (mniejszy waga = wyzej), potem RAND() dla rotacji.
     */
    public function activeForClub(int $clubId, string $context, int $limit = 5): array
    {
        $col = match ($context) {
            'portal'  => 'display_in_portal',
            'email'   => 'display_in_emails',
            'landing' => 'display_in_portal', // landing reuses portal flag
            default   => 'display_in_portal',
        };

        $sql = "SELECT *
                FROM sponsors
                WHERE club_id = ?
                  AND active = 1
                  AND `{$col}` = 1
                  AND (contract_start IS NULL OR contract_start <= CURDATE())
                  AND (contract_end   IS NULL OR contract_end   >= CURDATE())
                ORDER BY display_weight ASC, RAND()
                LIMIT " . max(1, (int)$limit);

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    /**
     * Best-effort INSERT do sponsor_exposures (nie throwed, nie blokuje renderu).
     */
    public function recordExposure(int $sponsorId, string $context, ?int $memberId = null): void
    {
        if (!in_array($context, ['portal_view', 'email_view', 'landing_view'], true)) {
            return;
        }
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO sponsor_exposures (sponsor_id, context, member_id) VALUES (?, ?, ?)"
            );
            $stmt->execute([$sponsorId, $context, $memberId]);
        } catch (\Throwable $e) {
            error_log('SponsorModel::recordExposure failed: ' . $e->getMessage());
        }
    }

    /**
     * Sponsorzy z kontraktem kończącym się w ciągu $days dni (LICZONE OD DZIŚ włącznie).
     * Używane przez UI dashboard ("kończące się") oraz expiry alerts cron.
     */
    public function expiringSoon(int $clubId, int $days = 30): array
    {
        $sql = "SELECT *,
                       DATEDIFF(contract_end, CURDATE()) AS days_left
                FROM sponsors
                WHERE club_id = ?
                  AND active = 1
                  AND contract_end IS NOT NULL
                  AND contract_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY contract_end ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId, $days]);
        return $stmt->fetchAll();
    }

    /**
     * Cross-club lookup — używane TYLKO przez cli/sponsors_expiry_alerts.php.
     * Zwraca sponsorów z contract_end == CURDATE()+$daysExact.
     */
    public function expiringExactlyInDays(int $daysExact): array
    {
        $sql = "SELECT s.*, c.name AS club_name, c.email AS club_email,
                       DATEDIFF(s.contract_end, CURDATE()) AS days_left
                FROM sponsors s
                JOIN clubs c ON c.id = s.club_id
                WHERE s.active = 1
                  AND s.contract_end IS NOT NULL
                  AND s.contract_end = DATE_ADD(CURDATE(), INTERVAL ? DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$daysExact]);
        return $stmt->fetchAll();
    }

    /**
     * Statystyki dla index page: liczba aktywnych, kończących się, łączna wartość.
     */
    public function statsForClub(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) AS active_cnt,
                SUM(CASE WHEN active = 1
                          AND contract_end IS NOT NULL
                          AND contract_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                         THEN 1 ELSE 0 END) AS expiring_soon_cnt,
                COALESCE(SUM(CASE WHEN active = 1
                                   AND (contract_end IS NULL OR contract_end >= CURDATE())
                                  THEN contract_value ELSE 0 END), 0) AS total_value
             FROM sponsors
             WHERE club_id = ?"
        );
        $stmt->execute([$clubId]);
        $row = $stmt->fetch();
        return [
            'total'             => (int)($row['total'] ?? 0),
            'active'            => (int)($row['active_cnt'] ?? 0),
            'expiring_soon'     => (int)($row['expiring_soon_cnt'] ?? 0),
            'total_value'       => (float)($row['total_value'] ?? 0),
        ];
    }

    /**
     * Sprawdź czy sponsor należy do danego klubu — używane przed update/delete
     * dla wymuszenia tenant boundary.
     */
    public function findByIdForClub(int $id, int $clubId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM sponsors WHERE id = ? AND club_id = ?");
        $stmt->execute([$id, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Update z club-id guard — UPDATE wymaga AND club_id = ? żeby
     * inny tenant nie mógł zmodyfikować cudzych danych nawet jak zna ID.
     */
    public function updateForClub(int $id, int $clubId, array $data): bool
    {
        if (empty($data)) return true;
        $set = implode(' = ?, ', array_map(fn($c) => "`{$c}`", array_keys($data))) . ' = ?';
        $stmt = $this->db->prepare("UPDATE sponsors SET {$set} WHERE id = ? AND club_id = ?");
        return $stmt->execute([...array_values($data), $id, $clubId]);
    }

    /**
     * Delete z club-id guard.
     */
    public function deleteForClub(int $id, int $clubId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM sponsors WHERE id = ? AND club_id = ?");
        return $stmt->execute([$id, $clubId]);
    }

    /**
     * Sprawdź czy alert danego typu został już wysłany dla sponsora.
     */
    public function alertAlreadySent(int $sponsorId, string $alertType): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM sponsor_alert_log WHERE sponsor_id = ? AND alert_type = ? LIMIT 1"
        );
        $stmt->execute([$sponsorId, $alertType]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Zarejestruj wysłany alert (idempotency dla expiry cron).
     */
    public function logAlert(int $sponsorId, string $alertType): void
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT IGNORE INTO sponsor_alert_log (sponsor_id, alert_type) VALUES (?, ?)"
            );
            $stmt->execute([$sponsorId, $alertType]);
        } catch (\Throwable $e) {
            error_log('SponsorModel::logAlert failed: ' . $e->getMessage());
        }
    }
}
