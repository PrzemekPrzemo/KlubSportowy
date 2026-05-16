<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

/**
 * Model for `club_invoices` — sales invoices issued by the club (Phase 2).
 *
 * Multi-tenant: extends ClubScopedModel for automatic club_id scoping.
 * Numbering: atomic via `club_invoice_numbering` (INSERT ON DUPLICATE KEY +
 * LAST_INSERT_ID) — see {@see nextNumber()}.
 *
 * Status lifecycle:
 *   draft -> issued -> sent_ksef -> accepted_ksef | rejected_ksef
 *   draft|issued -> cancelled
 *
 * Phase 2 stops at `issued` + XML preview. Phase 3 will queue & dispatch.
 */
class ClubInvoiceModel extends ClubScopedModel
{
    protected string $table = 'club_invoices';

    /**
     * Lista faktur klubu z filtrami (status, member, daty, search) + paginacja.
     *
     * @param array{
     *   status?:?string, buyer_member_id?:?int, year?:?int,
     *   date_from?:?string, date_to?:?string, q?:?string
     * } $filters
     */
    public function listForClub(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $clubId = $this->clubId();
        $where  = [];
        $params = [];

        if ($clubId !== null) {
            $where[]  = 'i.club_id = ?';
            $params[] = $clubId;
        }
        if (!empty($filters['status'])) {
            $where[]  = 'i.status = ?';
            $params[] = (string)$filters['status'];
        }
        if (!empty($filters['buyer_member_id'])) {
            $where[]  = 'i.buyer_member_id = ?';
            $params[] = (int)$filters['buyer_member_id'];
        }
        if (!empty($filters['year'])) {
            $where[]  = 'YEAR(i.issue_date) = ?';
            $params[] = (int)$filters['year'];
        }
        if (!empty($filters['date_from'])) {
            $where[]  = 'i.issue_date >= ?';
            $params[] = (string)$filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'i.issue_date <= ?';
            $params[] = (string)$filters['date_to'];
        }
        if (!empty($filters['q'])) {
            $where[]  = '(i.invoice_number LIKE ? OR i.buyer_name LIKE ?)';
            $like     = '%' . str_replace(['%','_'], ['\\%','\\_'], (string)$filters['q']) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT i.*, m.first_name AS buyer_first_name, m.last_name AS buyer_last_name,
                       m.member_number
                  FROM club_invoices i
             LEFT JOIN members m ON m.id = i.buyer_member_id
                {$whereSql}
              ORDER BY i.issue_date DESC, i.id DESC";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Find invoice + items for given club. Returns null if outside scope.
     *
     * @return array<string,mixed>|null
     */
    public function findForClub(int $id, int $clubId): ?array
    {
        $st = $this->db->prepare(
            "SELECT * FROM club_invoices WHERE id = ? AND club_id = ? LIMIT 1"
        );
        $st->execute([$id, $clubId]);
        $row = $st->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Insert invoice header. club_id is auto-injected by ClubScopedModel.
     *
     * Returns the new ID. Caller is responsible for inserting items + calling
     * `recalculateTotals($id)` afterwards.
     */
    public function createDraft(array $data): int
    {
        $data['status'] = $data['status'] ?? 'draft';
        // For drafts, give a temporary unique number so UNIQUE(club_id, invoice_number)
        // doesn't bite on bulk creates. Replaced on issue().
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = 'DRAFT-' . bin2hex(random_bytes(4));
        }
        return $this->insert($data);
    }

    /**
     * Update existing invoice (only when status is `draft`). Caller MUST
     * verify scope first via {@see findForClub()}.
     */
    public function updateForClub(int $id, int $clubId, array $data): bool
    {
        unset($data['id'], $data['club_id'], $data['invoice_number'], $data['status']);
        if (empty($data)) {
            return true;
        }
        $set = implode(' = ?, ', array_map(static fn($c) => "`{$c}`", array_keys($data))) . ' = ?';
        $st  = $this->db->prepare(
            "UPDATE club_invoices SET {$set} WHERE id = ? AND club_id = ? AND status = 'draft'"
        );
        return $st->execute([...array_values($data), $id, $clubId]);
    }

    /**
     * Atomic next number for given club + year using the numbering ledger.
     * Format placeholders: {seq}, {year}, {month}.
     *
     * Mechanizm: INSERT ... ON DUPLICATE KEY UPDATE next_sequence=next_sequence+1
     * + LAST_INSERT_ID(next_sequence) zwraca wartość pre-incrementu, którą
     * następnie formatujemy. Bezpieczne w wyścigu (lock na PK rowie).
     */
    public function nextNumber(int $clubId, ?int $year = null, ?int $month = null): string
    {
        $year  = $year  ?? (int)date('Y');
        $month = $month ?? (int)date('n');

        $pdo = $this->db;
        // INSERT new row (seq starts at 1 → use 1 + return 1); on duplicate, increment.
        // LAST_INSERT_ID(expr) trick: on update returns the expression value.
        $st = $pdo->prepare(
            "INSERT INTO club_invoice_numbering (club_id, year, next_sequence)
                  VALUES (?, ?, 2)
             ON DUPLICATE KEY UPDATE next_sequence = LAST_INSERT_ID(next_sequence + 1)"
        );
        $st->execute([$clubId, $year]);
        $rowSeq = (int)$pdo->lastInsertId();
        // First row inserted → LAST_INSERT_ID is AUTO-INC value (PK is composite,
        // so LAST_INSERT_ID(...) ONLY works after the ON DUPLICATE branch).
        // Detect: rowCount() == 1 means INSERT happened (seq = 1).
        $seq = ($st->rowCount() === 1) ? 1 : $rowSeq;

        $fmt = $this->numberingFormat($clubId, $year);
        return strtr($fmt, [
            '{seq}'   => str_pad((string)$seq, 1, '0', STR_PAD_LEFT),
            '{year}'  => (string)$year,
            '{month}' => str_pad((string)$month, 2, '0', STR_PAD_LEFT),
        ]);
    }

    /**
     * Aktualnie skonfigurowany format numeru (admin-edytowalne).
     * Default: `FV/{seq}/{year}`.
     */
    public function numberingFormat(int $clubId, int $year): string
    {
        $st = $this->db->prepare(
            "SELECT format FROM club_invoice_numbering WHERE club_id = ? AND year = ?"
        );
        $st->execute([$clubId, $year]);
        $fmt = $st->fetchColumn();
        return $fmt !== false && $fmt !== null && $fmt !== ''
            ? (string)$fmt
            : 'FV/{seq}/{year}';
    }

    /**
     * Admin/zarzad — zmiana formatu numeru dla danego roku.
     * Tworzy wpis jesli nie istnieje, nie nadpisujac next_sequence.
     */
    public function setNumberingFormat(int $clubId, int $year, string $format): void
    {
        $format = mb_substr(trim($format), 0, 50);
        if ($format === '' || strpos($format, '{seq}') === false) {
            return; // {seq} musi byc, inaczej numeracja jest bezsensowna
        }
        $st = $this->db->prepare(
            "INSERT INTO club_invoice_numbering (club_id, year, format, next_sequence)
                  VALUES (?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE format = VALUES(format)"
        );
        $st->execute([$clubId, $year, $format]);
    }

    /**
     * Promote draft -> issued, assigning a real KSeF-ready number.
     * Atomic, idempotent: only acts on rows currently in `draft`.
     *
     * @return string|null Final invoice number on success, null otherwise.
     */
    public function issue(int $id, int $clubId): ?string
    {
        $inv = $this->findForClub($id, $clubId);
        if ($inv === null || $inv['status'] !== 'draft') {
            return null;
        }
        $year = (int)date('Y', strtotime((string)$inv['issue_date']));
        $num  = $this->nextNumber($clubId, $year);
        $st   = $this->db->prepare(
            "UPDATE club_invoices
                SET status = 'issued', invoice_number = ?
              WHERE id = ? AND club_id = ? AND status = 'draft'"
        );
        $st->execute([$num, $id, $clubId]);
        if ($st->rowCount() === 0) {
            return null;
        }
        return $num;
    }

    /**
     * Cancel invoice (only when not already sent to KSeF).
     */
    public function cancel(int $id, int $clubId): bool
    {
        $st = $this->db->prepare(
            "UPDATE club_invoices
                SET status = 'cancelled'
              WHERE id = ? AND club_id = ?
                AND status IN ('draft','issued')"
        );
        return $st->execute([$id, $clubId]) && $st->rowCount() > 0;
    }

    /**
     * Recalculate totals from items table.
     */
    public function recalculateTotals(int $invoiceId): void
    {
        $st = $this->db->prepare(
            "SELECT COALESCE(SUM(net_amount),0) AS net,
                    COALESCE(SUM(vat_amount),0) AS vat,
                    COALESCE(SUM(gross_amount),0) AS gross
               FROM club_invoice_items WHERE invoice_id = ?"
        );
        $st->execute([$invoiceId]);
        $r = $st->fetch();
        $up = $this->db->prepare(
            "UPDATE club_invoices SET total_net = ?, total_vat = ?, total_gross = ? WHERE id = ?"
        );
        $up->execute([
            (float)($r['net']   ?? 0),
            (float)($r['vat']   ?? 0),
            (float)($r['gross'] ?? 0),
            $invoiceId,
        ]);
    }

    /**
     * Aggregated stats for dashboard cards.
     *
     * @return array{count_total:int,count_paid:int,count_unpaid:int,
     *               total_gross:float,total_paid:float,total_outstanding:float}
     */
    public function statsForClub(int $clubId, ?int $year = null): array
    {
        $params = [$clubId];
        $yearSql = '';
        if ($year !== null) {
            $yearSql = ' AND YEAR(issue_date) = ?';
            $params[] = $year;
        }
        $sql = "SELECT
                  COUNT(*) AS cnt_total,
                  SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS cnt_paid,
                  SUM(CASE WHEN payment_status IN ('unpaid','partial') AND status <> 'cancelled' THEN 1 ELSE 0 END) AS cnt_unpaid,
                  COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total_gross ELSE 0 END), 0) AS total_gross,
                  COALESCE(SUM(payment_paid_amount), 0) AS total_paid
               FROM club_invoices
              WHERE club_id = ? {$yearSql}";
        $st = $this->db->prepare($sql);
        $st->execute($params);
        $r = $st->fetch() ?: [];
        $gross = (float)($r['total_gross'] ?? 0);
        $paid  = (float)($r['total_paid']  ?? 0);
        return [
            'count_total'       => (int)($r['cnt_total']  ?? 0),
            'count_paid'        => (int)($r['cnt_paid']   ?? 0),
            'count_unpaid'      => (int)($r['cnt_unpaid'] ?? 0),
            'total_gross'       => $gross,
            'total_paid'        => $paid,
            'total_outstanding' => max(0.0, $gross - $paid),
        ];
    }

    /**
     * Czy istnieje juz faktura wystawiona dla danej platnosci (deduplikacja).
     */
    public function existsForPayment(int $paymentId, int $clubId): bool
    {
        $st = $this->db->prepare(
            "SELECT 1 FROM club_invoices
              WHERE source_payment_id = ? AND club_id = ?
                AND status <> 'cancelled' LIMIT 1"
        );
        $st->execute([$paymentId, $clubId]);
        return (bool)$st->fetchColumn();
    }
}
