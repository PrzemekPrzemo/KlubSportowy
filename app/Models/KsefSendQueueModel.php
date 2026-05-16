<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;
use PDO;

/**
 * Model dla `ksef_send_queue` — kolejka wysylkowa faktur do KSeF (Phase 3).
 *
 * Klasa pomocnicza, NIE rozszerza ClubScopedModel zeby moc dzialac z poziomu
 * CLI workera (brak klubu w sesji). Wszystkie metody przyjmuja jawnie club_id
 * lub operuja na konkretnym queue_id (ktory ma club_id w wierszu).
 */
final class KsefSendQueueModel
{
    /**
     * Sekwencja exponential backoff: 1m, 5m, 30m, 2h, 12h.
     * Po 5 (max_attempts) probach status idzie na 'failed'.
     */
    public const RETRY_DELAYS_SECONDS = [60, 300, 1800, 7200, 43200];
    public const MAX_ATTEMPTS         = 5;

    /**
     * Enqueue invoice. Idempotent — przy duplikacie nic nie robi i zwraca null.
     *
     * @return int|null queue id (lub null jesli juz w kolejce)
     */
    public function enqueue(int $clubId, int $invoiceId): ?int
    {
        $pdo = Database::pdo();
        try {
            $st = $pdo->prepare(
                "INSERT INTO ksef_send_queue (club_id, invoice_id, status, attempts, queued_at)
                      VALUES (?, ?, 'queued', 0, NOW())"
            );
            $st->execute([$clubId, $invoiceId]);
            return (int)$pdo->lastInsertId();
        } catch (\PDOException $e) {
            // Duplicate (UNIQUE invoice_id) — zwroc null, caller niech zignoruje.
            if ((int)$e->errorInfo[1] === 1062) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $pdo = Database::pdo();
        $st  = $pdo->prepare("SELECT * FROM ksef_send_queue WHERE id = ? LIMIT 1");
        $st->execute([$id]);
        $row = $st->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByInvoice(int $invoiceId, int $clubId): ?array
    {
        $pdo = Database::pdo();
        $st  = $pdo->prepare(
            "SELECT * FROM ksef_send_queue WHERE invoice_id = ? AND club_id = ? LIMIT 1"
        );
        $st->execute([$invoiceId, $clubId]);
        $row = $st->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Wybiera batch zadan gotowych do przetworzenia: queued/retrying
     * z next_retry_at NULL lub w przeszlosci. Uzywa FOR UPDATE SKIP LOCKED
     * zeby kilka rownoleglych workerow nie zlapalo tej samej pozycji.
     *
     * MUSI byc wywolane wewnatrz transakcji + caller od razu MUSI zmienic
     * status na 'signing'/'sending'/etc., zeby zwolnic lock i nie zlapac
     * tego samego wpisu w nastepnym tickone.
     *
     * @return array<int,array<string,mixed>>
     */
    public function lockBatchForProcessing(int $limit = 10): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            "SELECT * FROM ksef_send_queue
              WHERE status IN ('queued','retrying','sending','awaiting_upo')
                AND (next_retry_at IS NULL OR next_retry_at <= NOW())
              ORDER BY queued_at ASC
              LIMIT {$limit}
              FOR UPDATE SKIP LOCKED"
        );
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Update stanu kolejki + dodatkowych pol.
     *
     * @param array<string,mixed> $fields
     */
    public function updateState(int $id, array $fields): void
    {
        if (empty($fields)) {
            return;
        }
        $pdo  = Database::pdo();
        $cols = array_keys($fields);
        $set  = implode(', ', array_map(static fn($c) => "`{$c}` = ?", $cols));
        $vals = array_values($fields);
        $vals[] = $id;
        $pdo->prepare("UPDATE ksef_send_queue SET {$set} WHERE id = ?")->execute($vals);
    }

    /**
     * Oznacz zadanie jako "retry": ustaw status, zwieksz attempts, ustaw next_retry_at.
     * Po przekroczeniu MAX_ATTEMPTS przechodzi na 'failed'.
     */
    public function markRetry(int $id, int $currentAttempts, string $errorMsg, ?string $errorCode = null): string
    {
        $newAttempts = $currentAttempts + 1;
        if ($newAttempts >= self::MAX_ATTEMPTS) {
            $this->updateState($id, [
                'status'             => 'failed',
                'attempts'           => $newAttempts,
                'last_error_code'    => $errorCode,
                'last_error_message' => mb_substr($errorMsg, 0, 5000),
                'next_retry_at'      => null,
                'ksef_session_token' => null, // czyscimy token na wyjsciu
            ]);
            return 'failed';
        }
        // Wybierz delay; jesli zabraknie, wez ostatni (clamp)
        $delayIdx = min($currentAttempts, count(self::RETRY_DELAYS_SECONDS) - 1);
        $delay    = self::RETRY_DELAYS_SECONDS[$delayIdx];
        $next     = date('Y-m-d H:i:s', time() + $delay);

        $this->updateState($id, [
            'status'             => 'retrying',
            'attempts'           => $newAttempts,
            'last_error_code'    => $errorCode,
            'last_error_message' => mb_substr($errorMsg, 0, 5000),
            'next_retry_at'      => $next,
        ]);
        return 'retrying';
    }

    /**
     * Lista wszystkich pozycji kolejki (super admin overview).
     *
     * @param array{status?:?string,club_id?:?int} $filters
     * @return array<int,array<string,mixed>>
     */
    public function listAll(array $filters = [], int $limit = 200): array
    {
        $where  = [];
        $params = [];
        if (!empty($filters['status'])) {
            $where[]  = 'q.status = ?';
            $params[] = (string)$filters['status'];
        }
        if (!empty($filters['club_id'])) {
            $where[]  = 'q.club_id = ?';
            $params[] = (int)$filters['club_id'];
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT q.*, c.name AS club_name, i.invoice_number, i.total_gross
                  FROM ksef_send_queue q
                  JOIN clubs         c ON c.id = q.club_id
                  JOIN club_invoices i ON i.id = q.invoice_id
                  {$whereSql}
              ORDER BY q.updated_at DESC
                 LIMIT " . (int)$limit;
        $pdo = Database::pdo();
        $st  = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll() ?: [];
    }

    /**
     * @return array{total:int,queued:int,signing:int,sending:int,awaiting:int,failed_24h:int,completed_24h:int,avg_processing_seconds:float}
     */
    public function stats(): array
    {
        $pdo = Database::pdo();
        $st  = $pdo->query(
            "SELECT
                COUNT(*) AS total,
                SUM(status='queued')      AS queued,
                SUM(status='signing')     AS signing,
                SUM(status='sending')     AS sending,
                SUM(status='awaiting_upo')AS awaiting,
                SUM(status='failed' AND updated_at >= NOW() - INTERVAL 1 DAY)    AS failed_24h,
                SUM(status='completed' AND upo_received_at >= NOW() - INTERVAL 1 DAY) AS completed_24h,
                COALESCE(AVG(TIMESTAMPDIFF(SECOND, queued_at, upo_received_at)), 0)   AS avg_secs
               FROM ksef_send_queue"
        );
        $r = $st !== false ? $st->fetch(PDO::FETCH_ASSOC) : [];
        return [
            'total'                  => (int)($r['total']         ?? 0),
            'queued'                 => (int)($r['queued']        ?? 0),
            'signing'                => (int)($r['signing']       ?? 0),
            'sending'                => (int)($r['sending']       ?? 0),
            'awaiting'               => (int)($r['awaiting']      ?? 0),
            'failed_24h'             => (int)($r['failed_24h']    ?? 0),
            'completed_24h'          => (int)($r['completed_24h'] ?? 0),
            'avg_processing_seconds' => (float)($r['avg_secs']    ?? 0),
        ];
    }

    /**
     * Force-retry (super admin): reset na queued z attempts=0.
     */
    public function forceRetry(int $id): void
    {
        $this->updateState($id, [
            'status'             => 'queued',
            'attempts'           => 0,
            'next_retry_at'      => null,
            'last_error_code'    => null,
            'last_error_message' => null,
            'ksef_session_token' => null,
        ]);
    }

    /**
     * Force-fail (super admin): manualne odrzucenie bez wysylki.
     */
    public function forceFail(int $id, string $reason): void
    {
        $this->updateState($id, [
            'status'             => 'failed',
            'last_error_code'    => 'admin_force_fail',
            'last_error_message' => mb_substr($reason, 0, 5000),
            'ksef_session_token' => null,
        ]);
    }
}
