<?php

namespace App\Models;

use PDO;

/**
 * Ledger zebranych platform fees. Wpis tworzony przez webhook
 * po success płatności online.
 */
class PlatformFeeChargeModel extends BaseModel
{
    protected string $table = 'platform_fee_charges';

    public function record(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table}
              (club_id, payment_id, online_payment_id, provider, transaction_id,
               gross_amount_cents, platform_fee_cents, club_net_amount_cents, currency)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            (int)$data['club_id'],
            !empty($data['payment_id']) ? (int)$data['payment_id'] : null,
            !empty($data['online_payment_id']) ? (int)$data['online_payment_id'] : null,
            (string)$data['provider'],
            (string)$data['transaction_id'],
            (int)$data['gross_amount_cents'],
            (int)$data['platform_fee_cents'],
            (int)$data['club_net_amount_cents'],
            (string)($data['currency'] ?? 'PLN'),
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Raport platform fees zebranych w danym okresie.
     *
     * @return array{rows:array, total_gross:int, total_fees:int, total_net:int}
     */
    public function report(?string $fromYmd = null, ?string $toYmd = null, ?int $clubId = null): array
    {
        $where  = [];
        $params = [];
        if ($fromYmd) { $where[] = 'charged_at >= ?'; $params[] = $fromYmd . ' 00:00:00'; }
        if ($toYmd)   { $where[] = 'charged_at <= ?'; $params[] = $toYmd   . ' 23:59:59'; }
        if ($clubId)  { $where[] = 'pfc.club_id = ?'; $params[] = $clubId; }

        $sql = "SELECT pfc.*, c.name AS club_name
                  FROM {$this->table} pfc
                  LEFT JOIN clubs c ON c.id = pfc.club_id";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY charged_at DESC LIMIT 500';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tg = 0; $tf = 0; $tn = 0;
        foreach ($rows as $r) {
            $tg += (int)$r['gross_amount_cents'];
            $tf += (int)$r['platform_fee_cents'];
            $tn += (int)$r['club_net_amount_cents'];
        }
        return [
            'rows'        => $rows,
            'total_gross' => $tg,
            'total_fees'  => $tf,
            'total_net'   => $tn,
        ];
    }
}
