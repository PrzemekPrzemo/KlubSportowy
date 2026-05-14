<?php

namespace App\Models;

use App\Helpers\ClubContext;

/**
 * Abstrakcyjna klasa bazowa dla modeli powiązanych z klubem.
 *
 * Automatycznie filtruje zapytania po club_id z ClubContext.
 * Automatycznie dodaje club_id przy insercie.
 * Super admin może wyłączyć scope przez withoutScope().
 */
abstract class ClubScopedModel extends BaseModel
{
    private bool $scopeEnabled = true;
    private bool $bypassLogged = false;

    /**
     * Globalny "soft mute" auditu — uzywany przez kod, ktory swiadomie
     * pracuje cross-tenant w petli (np. cron, super-admin audit checks)
     * i nie chce zalewac tenant_access_log identycznymi wpisami.
     */
    private static bool $auditEnabled = true;

    public static function disableAudit(): void { self::$auditEnabled = false; }
    public static function enableAudit():  void { self::$auditEnabled = true; }

    protected function clubId(): ?int
    {
        if (!$this->scopeEnabled) {
            return null;
        }
        return ClubContext::current();
    }

    /**
     * Wylacz auto-filtr po club_id — uzywaj OSTROZNIE.
     *
     * Kazde wywolanie jest auditowane w tabeli `tenant_access_log`
     * (chyba ze audit zostal globalnie wylaczony przez disableAudit()).
     * Wzorzec inspirowany Hovera, ktore ma natywna izolacje DB-per-tenant.
     * U nas (shared schema) audit daje obserwowalnosc i pomaga wylapac
     * kod, ktory nieumyslnie omija scope.
     */
    public function withoutScope(): static
    {
        $this->scopeEnabled = false;
        $this->logBypassOnce('read');
        return $this;
    }

    public function withScope(): static
    {
        $this->scopeEnabled = true;
        $this->bypassLogged = false;
        return $this;
    }

    /**
     * Loguje fakt bypassu scope — raz na zycie instancji (per "withoutScope()" call site).
     * Bezpieczne wzgledem brakujacej tabeli (logBypass swap-em throw'i).
     */
    private function logBypassOnce(string $operation): void
    {
        if (!self::$auditEnabled || $this->bypassLogged) {
            return;
        }
        $this->bypassLogged = true;

        try {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
            // [0] logBypassOnce, [1] withoutScope/insert/update/etc, [2] CALLER
            $caller = $trace[2] ?? $trace[1] ?? [];
            $callerFile  = $caller['file']  ?? null;
            $callerLine  = $caller['line']  ?? null;
            $callerClass = $caller['class'] ?? null;

            $severity = ClubContext::isSuperAdmin() ? 'info' : 'warning';

            (new TenantAccessLogModel())->logBypass(
                $this->table,
                $operation,
                $callerFile,
                $callerLine,
                $callerClass,
                $severity,
                static::class
            );
        } catch (\Throwable) {
            // Audit nigdy nie crashuje requestu uzytkowego.
        }
    }

    public function findById(int $id): ?array
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            return parent::findById($id);
        }
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE id = ? AND club_id = ?"
        );
        $stmt->execute([$id, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAll(string $orderBy = 'id', string $dir = 'ASC'): array
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            return parent::findAll($orderBy, $dir);
        }
        $dir     = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $orderBy = preg_replace('/[^a-zA-Z0-9_]/', '', $orderBy);
        $stmt    = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE club_id = ? ORDER BY `{$orderBy}` {$dir}"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function delete(int $id): bool
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            $this->logBypassOnce('delete');
            return parent::delete($id);
        }
        $stmt = $this->db->prepare(
            "DELETE FROM `{$this->table}` WHERE id = ? AND club_id = ?"
        );
        return $stmt->execute([$id, $clubId]);
    }

    public function count(): int
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            return parent::count();
        }
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `{$this->table}` WHERE club_id = ?"
        );
        $stmt->execute([$clubId]);
        return (int)$stmt->fetchColumn();
    }

    public function insert(array $data): int
    {
        $clubId = $this->clubId();
        if ($clubId !== null && !isset($data['club_id'])) {
            $data['club_id'] = $clubId;
        }
        if ($clubId === null) {
            // Insert bez scope — kod jawnie zarzadza club_id (lub go nie podaje wcale).
            // Audit z severity warning, bo to operacja write bez izolacji.
            $this->logBypassOnce('write');
        }
        return parent::insert($data);
    }

    /**
     * Zaktualizuj wiersz egzekwując scope club_id.
     *
     * Bez scope (np. super-admin po withoutScope()) dziala identycznie
     * jak BaseModel::update(). Z aktywnym scope: WHERE id=? AND club_id=?
     * — zapobiega IDOR (cross-club update). Pole `club_id` w `$data` jest
     * ignorowane — klub A nie moze "przeniesc" rekordu do klubu B.
     */
    public function update(int $id, array $data): bool
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            $this->logBypassOnce('write');
            return parent::update($id, $data);
        }
        if (empty($data)) return true;
        unset($data['club_id']);
        if (empty($data)) return true;

        $set  = implode(' = ?, ', array_map(fn($c) => "`{$c}`", array_keys($data))) . ' = ?';
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET {$set} WHERE id = ? AND club_id = ?"
        );
        return $stmt->execute([...array_values($data), $id, $clubId]);
    }

    protected function clubWhere(): string
    {
        return $this->clubId() !== null ? ' AND club_id = ?' : '';
    }

    protected function clubParams(): array
    {
        $id = $this->clubId();
        return $id !== null ? [$id] : [];
    }
}
