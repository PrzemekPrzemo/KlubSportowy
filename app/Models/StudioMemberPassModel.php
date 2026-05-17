<?php

namespace App\Models;

use App\Sports\Studio\PassExhaustedException;
use PDO;

/**
 * Karnety zakupione przez zawodnika.
 *
 * Logika biznesowa:
 *   - purchase()       — atomowy zakup (insert pass + opcjonalnie payment_id)
 *   - consumeOne()     — transakcyjnie obniza classes_remaining, throw gdy 0
 *   - refundOne()      — zwiekszaja remaining (po anulacji rezerwacji w oknie)
 *   - activeForMember() — wybierz najlepszy aktywny pass dla zawodnika
 *
 * Multi-tenant: KAZDA operacja w obrebie club_id z kontekstu (chronione
 * przez ClubScopedModel + dodatkowe sprawdzenia w consumeOne()).
 */
class StudioMemberPassModel extends ClubScopedModel
{
    protected string $table = 'studio_member_passes';

    /**
     * Wybierz aktywny karnet dla zawodnika (najwczesniej waznosc).
     * Priorytetyzuje pass dopasowany do sportu (sport_key), fallback do any-sport.
     */
    public function activeForMember(int $memberId, ?string $sportKey = null): ?array
    {
        $clubId = $this->clubId();
        $today  = date('Y-m-d');

        $sql = "SELECT mp.*, pt.type AS pass_type, pt.sport_key AS pass_sport,
                       pt.name AS pass_name, pt.classes_count AS pass_classes_count
                FROM studio_member_passes mp
                JOIN studio_pass_types pt ON pt.id = mp.pass_type_id
                WHERE mp.member_id = ?
                  AND mp.status   = 'active'
                  AND mp.valid_from  <= ?
                  AND mp.valid_until >= ?";
        $params = [$memberId, $today, $today];

        if ($clubId !== null) {
            $sql .= " AND mp.club_id = ?";
            $params[] = $clubId;
        }
        if ($sportKey !== null) {
            $sql .= " AND (pt.sport_key IS NULL OR pt.sport_key = ?)";
            $params[] = $sportKey;
        }
        // Sort: sport-specific przed any-sport, potem najwczesniej wygasajacy
        $sql .= " ORDER BY (pt.sport_key IS NULL) ASC, mp.valid_until ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    /** Lista wszystkich karnetow zawodnika (active + history). */
    public function listForMember(int $memberId, ?string $status = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT mp.*, pt.name AS pass_name, pt.type AS pass_type,
                       pt.sport_key AS pass_sport
                FROM studio_member_passes mp
                JOIN studio_pass_types pt ON pt.id = mp.pass_type_id
                WHERE mp.member_id = ?";
        $params = [$memberId];
        if ($clubId !== null) {
            $sql .= " AND mp.club_id = ?";
            $params[] = $clubId;
        }
        if ($status !== null) {
            $sql .= " AND mp.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY mp.purchased_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Zakup karnetu — atomowy insert (+ opcjonalnie payment_id).
     * Zwraca id karnetu.
     */
    public function purchase(int $memberId, int $passTypeId, ?int $paymentId = null): int
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            throw new \RuntimeException('Purchase requires club context.');
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM studio_pass_types WHERE id = ? AND club_id = ? AND active = 1 LIMIT 1"
        );
        $stmt->execute([$passTypeId, $clubId]);
        $type = $stmt->fetch();
        if (!$type) {
            throw new \InvalidArgumentException('Pass type not found / inactive in this club.');
        }

        $validityDays = (int)$type['validity_days'];
        $validFrom    = date('Y-m-d');
        $validUntil   = date('Y-m-d', strtotime("+{$validityDays} days"));
        $classesTotal = $type['type'] === 'unlimited_period' ? null : (int)$type['classes_count'];

        return $this->insert([
            'club_id'           => $clubId,
            'member_id'         => $memberId,
            'pass_type_id'      => $passTypeId,
            'valid_from'        => $validFrom,
            'valid_until'       => $validUntil,
            'classes_total'     => $classesTotal,
            'classes_remaining' => $classesTotal,
            'status'            => 'active',
            'payment_id'        => $paymentId,
        ]);
    }

    /**
     * Zuzyj 1 wejscie z karnetu w transakcji.
     * - SELECT ... FOR UPDATE
     * - Walidacja statusu + waznosci + remaining > 0
     * - UPDATE classes_remaining = classes_remaining - 1
     * - Jesli spadlo do 0 → status = 'exhausted'
     * - Audit: tenant_access_log (informacyjnie)
     *
     * unlimited_period (classes_total = NULL) — tylko walidacja waznosci,
     * bez dekrementu.
     *
     * @throws PassExhaustedException
     */
    public function consumeOne(int $passId): void
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            throw new \RuntimeException('consumeOne requires club context.');
        }

        $db = $this->db;
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "SELECT * FROM studio_member_passes
                 WHERE id = ? AND club_id = ?"
                . $this->forUpdateClause()
            );
            $stmt->execute([$passId, $clubId]);
            $pass = $stmt->fetch();

            if (!$pass) {
                throw new PassExhaustedException('Karnet nie istnieje lub nie nalezy do tego klubu.');
            }
            if ($pass['status'] !== 'active') {
                throw new PassExhaustedException('Karnet nie jest aktywny (status: ' . $pass['status'] . ').');
            }
            $today = date('Y-m-d');
            if ($pass['valid_until'] < $today) {
                // Zaktualizuj status (commit, zeby zmiana nie zostala cofnieta przez rollback w catch).
                $u = $db->prepare("UPDATE studio_member_passes SET status='expired' WHERE id=? AND club_id=?");
                $u->execute([$passId, $clubId]);
                $db->commit();
                throw new PassExhaustedException('Karnet wygasl: ' . $pass['valid_until']);
            }

            // unlimited_period → bez dekrementu (classes_total IS NULL)
            if ($pass['classes_total'] === null) {
                $db->commit();
                $this->softAudit('consume_unlimited', $passId);
                return;
            }

            $remaining = (int)$pass['classes_remaining'];
            if ($remaining <= 0) {
                $u = $db->prepare("UPDATE studio_member_passes SET status='exhausted' WHERE id=? AND club_id=?");
                $u->execute([$passId, $clubId]);
                $db->commit();
                throw new PassExhaustedException('Karnet wyczerpany (brak pozostalych wejsc).');
            }

            $newRemaining = $remaining - 1;
            $newStatus    = $newRemaining === 0 ? 'exhausted' : 'active';
            $u = $db->prepare(
                "UPDATE studio_member_passes
                 SET classes_remaining = ?, status = ?
                 WHERE id = ? AND club_id = ?"
            );
            $u->execute([$newRemaining, $newStatus, $passId, $clubId]);

            $db->commit();
            $this->softAudit('consume', $passId);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cofnij zuzycie (refund) — uzywane przy anulacji rezerwacji w oknie.
     * Zwieksza classes_remaining o 1, ustawia status=active jesli byl exhausted.
     */
    public function refundOne(int $passId): void
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            throw new \RuntimeException('refundOne requires club context.');
        }
        $db = $this->db;
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "SELECT * FROM studio_member_passes WHERE id = ? AND club_id = ?"
                . $this->forUpdateClause()
            );
            $stmt->execute([$passId, $clubId]);
            $pass = $stmt->fetch();
            if (!$pass) {
                $db->commit();
                return;
            }
            if ($pass['classes_total'] === null) {
                // unlimited — nic do oddania
                $db->commit();
                return;
            }
            $total = (int)$pass['classes_total'];
            $rem   = (int)$pass['classes_remaining'];
            $new   = min($total, $rem + 1);
            $newStatus = $pass['status'] === 'exhausted' && $new > 0 ? 'active' : $pass['status'];
            $u = $db->prepare(
                "UPDATE studio_member_passes
                 SET classes_remaining = ?, status = ?
                 WHERE id = ? AND club_id = ?"
            );
            $u->execute([$new, $newStatus, $passId, $clubId]);
            $db->commit();
            $this->softAudit('refund', $passId);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /** Statystyki dla raportu admina. */
    public function stats(?string $sportKey = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT mp.status,
                       COUNT(*)                 AS cnt,
                       SUM(pt.price_cents)      AS revenue_cents
                FROM studio_member_passes mp
                JOIN studio_pass_types pt ON pt.id = mp.pass_type_id
                WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND mp.club_id = ?"; $params[] = $clubId; }
        if ($sportKey !== null) {
            $sql .= " AND (pt.sport_key IS NULL OR pt.sport_key = ?)";
            $params[] = $sportKey;
        }
        $sql .= " GROUP BY mp.status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $out = ['active' => 0, 'exhausted' => 0, 'expired' => 0, 'refunded' => 0, 'revenue_cents' => 0];
        foreach ($stmt->fetchAll() as $r) {
            $out[$r['status']] = (int)$r['cnt'];
            $out['revenue_cents'] += (int)$r['revenue_cents'];
        }
        return $out;
    }

    /** FOR UPDATE tylko dla MySQL — SQLite (testy) tego nie wspiera. */
    private function forUpdateClause(): string
    {
        try {
            $driver = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME);
            return $driver === 'mysql' ? ' FOR UPDATE' : '';
        } catch (\Throwable) {
            return '';
        }
    }

    /** Audit informacyjny — nigdy nie wywala kodu. */
    private function softAudit(string $op, int $passId): void
    {
        try {
            $log = new TenantAccessLogModel();
            $log->logBypass(
                $this->table,
                'pass_' . $op,
                __FILE__,
                __LINE__,
                static::class,
                'info',
                'pass_id=' . $passId
            );
        } catch (\Throwable) {
            // Audit nie moze blokowac kodu uzytkowego.
        }
    }
}
