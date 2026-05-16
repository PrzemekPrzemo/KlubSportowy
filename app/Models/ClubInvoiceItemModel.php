<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Model for `club_invoice_items` — items per club_invoice.
 *
 * Not club-scoped directly (lives behind invoice_id), but every caller
 * MUST first verify the parent invoice via ClubInvoiceModel::findForClub().
 */
class ClubInvoiceItemModel extends BaseModel
{
    protected string $table = 'club_invoice_items';

    /**
     * @return array<int, array<string,mixed>>
     */
    public function listForInvoice(int $invoiceId): array
    {
        $st = $this->db->prepare(
            "SELECT * FROM club_invoice_items
              WHERE invoice_id = ?
           ORDER BY position ASC, id ASC"
        );
        $st->execute([$invoiceId]);
        return $st->fetchAll() ?: [];
    }

    /**
     * Wstaw wszystkie pozycje atomowo (transakcja). Najpierw kasujemy
     * istniejace pozycje, potem wstawiamy nowe — uzywane tez przy edycji.
     *
     * Kazdy item:
     *   description, quantity, unit, unit_price_net, vat_rate
     *   (+ opcjonalnie pkwiu, gtu_code)
     *
     * Net/VAT/gross wyliczamy w PHP (banker's rounding do 2 miejsc).
     *
     * @param array<int, array<string,mixed>> $items
     */
    public function replaceAll(int $invoiceId, array $items): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM club_invoice_items WHERE invoice_id = ?")
                     ->execute([$invoiceId]);

            $stmt = $this->db->prepare(
                "INSERT INTO club_invoice_items
                    (invoice_id, position, description, quantity, unit,
                     unit_price_net, vat_rate, net_amount, vat_amount, gross_amount,
                     pkwiu, gtu_code)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $pos = 0;
            foreach ($items as $it) {
                $pos++;
                $desc = trim((string)($it['description'] ?? ''));
                if ($desc === '') {
                    continue;
                }
                $qty   = max(0.0, (float)($it['quantity'] ?? 1));
                $unit  = mb_substr((string)($it['unit'] ?? 'szt.'), 0, 20);
                $price = (float)($it['unit_price_net'] ?? 0);
                $vatR  = (float)($it['vat_rate'] ?? 23);

                $net   = round($qty * $price, 2);
                // Specjalne stawki (ZW = -1, NP = -2) → 0 VAT
                $vat   = $vatR >= 0 ? round($net * ($vatR / 100), 2) : 0.0;
                $gross = round($net + $vat, 2);

                $stmt->execute([
                    $invoiceId,
                    $pos,
                    mb_substr($desc, 0, 500),
                    $qty,
                    $unit,
                    $price,
                    $vatR,
                    $net,
                    $vat,
                    $gross,
                    !empty($it['pkwiu'])    ? mb_substr((string)$it['pkwiu'],    0, 20) : null,
                    !empty($it['gtu_code']) ? mb_substr((string)$it['gtu_code'], 0, 5)  : null,
                ]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
