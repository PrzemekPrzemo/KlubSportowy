<?php

namespace App\Models;

/**
 * Audit log eksportów do federacji (register / update / license_renew / status_fetch).
 *
 * Każda operacja exportera (manualna z UI lub auto z CLI) tworzy wiersz —
 * pozwala administratorowi klubu zobaczyć historię i debugować błędy.
 */
class FederationExportLogModel extends ClubScopedModel
{
    protected string $table = 'federation_export_log';

    /**
     * Zaloguj nową operację — zwraca id wiersza.
     */
    public function logQueued(
        int $clubId,
        string $federationCode,
        ?int $memberId,
        string $operation,
        array $requestPayload = [],
        ?int $triggeredBy = null,
    ): int {
        return $this->insert([
            'club_id'         => $clubId,
            'federation_code' => $federationCode,
            'member_id'       => $memberId,
            'operation'       => $operation,
            'status'          => 'queued',
            'request_payload' => $requestPayload ? json_encode($requestPayload, JSON_UNESCAPED_UNICODE) : null,
            'triggered_by'    => $triggeredBy,
            'triggered_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    /** Oznacz operację jako zakończoną sukcesem. */
    public function markSuccess(int $logId, array $responsePayload = []): void
    {
        $this->update($logId, [
            'status'           => 'success',
            'response_payload' => $responsePayload ? json_encode($responsePayload, JSON_UNESCAPED_UNICODE) : null,
            'completed_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    /** Oznacz operację jako nieudaną. */
    public function markFailed(int $logId, string $errorMessage, array $responsePayload = []): void
    {
        $this->update($logId, [
            'status'           => 'failed',
            'error_message'    => mb_substr($errorMessage, 0, 1000),
            'response_payload' => $responsePayload ? json_encode($responsePayload, JSON_UNESCAPED_UNICODE) : null,
            'completed_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    /** Ostatnie N wpisów dla klubu (z opcjonalnym filtrem federacji). */
    public function recentForClub(int $clubId, ?string $federationCode = null, int $limit = 50): array
    {
        $sql = "SELECT * FROM federation_export_log WHERE club_id = ?";
        $params = [$clubId];
        if ($federationCode !== null) {
            $sql .= " AND federation_code = ?";
            $params[] = $federationCode;
        }
        $sql .= " ORDER BY triggered_at DESC LIMIT " . max(1, (int)$limit);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
