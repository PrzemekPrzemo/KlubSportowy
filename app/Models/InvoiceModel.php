<?php

namespace App\Models;

class InvoiceModel extends BaseModel
{
    protected string $table = 'billing_invoices';

    public function listAll(?int $clubId, ?string $status, ?string $from, ?string $to, int $page = 1, int $perPage = 30): array
    {
        $where = [];
        $params = [];

        if ($clubId !== null) {
            $where[] = 'i.club_id = ?';
            $params[] = $clubId;
        }
        if ($status !== null && $status !== '') {
            $where[] = 'i.status = ?';
            $params[] = $status;
        }
        if ($from !== null && $from !== '') {
            $where[] = 'i.issue_date >= ?';
            $params[] = $from;
        }
        if ($to !== null && $to !== '') {
            $where[] = 'i.issue_date <= ?';
            $params[] = $to;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT i.*, c.name AS club_name, c.nip AS club_nip
                FROM billing_invoices i
                LEFT JOIN clubs c ON c.id = i.club_id
                {$whereSql}
                ORDER BY i.issue_date DESC, i.id DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function sumByStatus(): array
    {
        $stmt = $this->db->query(
            "SELECT status, COUNT(*) AS c, COALESCE(SUM(total), 0) AS total
             FROM billing_invoices GROUP BY status"
        );
        $out = ['draft' => ['c' => 0, 'total' => 0], 'issued' => ['c' => 0, 'total' => 0],
                'paid' => ['c' => 0, 'total' => 0], 'cancelled' => ['c' => 0, 'total' => 0]];
        foreach ($stmt->fetchAll() as $r) {
            $out[$r['status']] = ['c' => (int)$r['c'], 'total' => (float)$r['total']];
        }
        return $out;
    }

    public function getNextNumber(): string
    {
        $year = date('Y');
        $prefix = "FV/{$year}/";
        $stmt = $this->db->prepare(
            "SELECT number FROM billing_invoices WHERE number LIKE ? ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetchColumn();
        $next = 1;
        if ($last && preg_match('#/(\d+)$#', (string)$last, $m)) {
            $next = (int)$m[1] + 1;
        }
        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    public function createForAdmin(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO billing_invoices (club_id, number, issue_date, due_date, total, status, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            (int)$data['club_id'],
            (string)$data['number'],
            (string)$data['issue_date'],
            (string)$data['due_date'],
            (float)$data['total'],
            (string)$data['status'],
            $data['notes'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function markPaid(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE billing_invoices SET status = 'paid', paid_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    public function markCancelled(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE billing_invoices SET status = 'cancelled' WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    public function findWithClub(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT i.*, c.name AS club_name, c.nip AS club_nip, c.city AS club_city, c.address AS club_address
             FROM billing_invoices i
             LEFT JOIN clubs c ON c.id = i.club_id
             WHERE i.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
