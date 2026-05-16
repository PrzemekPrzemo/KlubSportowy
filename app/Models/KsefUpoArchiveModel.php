<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

/**
 * Model dla `ksef_upo_archive` — przechowuje UPO (Urzedowe Potwierdzenie Odbioru)
 * zwrocone przez KSeF dla zaakceptowanej faktury.
 *
 * Per-invoice, UNIQUE(invoice_id). Multi-tenant via club_id + FK CASCADE.
 */
final class KsefUpoArchiveModel
{
    /**
     * @return int id rekordu
     */
    public function archive(
        int    $invoiceId,
        int    $clubId,
        string $upoXmlPath,
        string $ksefReference,
        string $acquisitionTimestamp,
        string $documentHash
    ): int {
        $pdo = Database::pdo();
        $st  = $pdo->prepare(
            "INSERT INTO ksef_upo_archive
                 (invoice_id, club_id, upo_xml_path, ksef_reference, acquisition_timestamp, document_hash)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                 upo_xml_path = VALUES(upo_xml_path),
                 ksef_reference = VALUES(ksef_reference),
                 acquisition_timestamp = VALUES(acquisition_timestamp),
                 document_hash = VALUES(document_hash)"
        );
        $st->execute([$invoiceId, $clubId, $upoXmlPath, $ksefReference, $acquisitionTimestamp, $documentHash]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findForInvoice(int $invoiceId, int $clubId): ?array
    {
        $pdo = Database::pdo();
        $st  = $pdo->prepare(
            "SELECT * FROM ksef_upo_archive WHERE invoice_id = ? AND club_id = ? LIMIT 1"
        );
        $st->execute([$invoiceId, $clubId]);
        $row = $st->fetch();
        return $row === false ? null : $row;
    }
}
