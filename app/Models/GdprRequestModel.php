<?php

namespace App\Models;

use PDO;

/**
 * GDPR requests (art. 17 right-to-forget, art. 20 data portability).
 *
 * Tabela: gdpr_requests (migracja 077).
 *
 * Wszystkie metody sa scoped per-member i per-club. Cross-tenant
 * validation: zawsze pass club_id i member_id z sesji (MemberAuth).
 */
class GdprRequestModel extends BaseModel
{
    protected string $table = 'gdpr_requests';

    public const TYPES = [
        'export'              => 'Eksport danych (art. 20)',
        'delete'              => 'Usuniecie konta (art. 17)',
        'rectify'             => 'Sprostowanie danych (art. 16)',
        'restrict_processing' => 'Ograniczenie przetwarzania (art. 18)',
        'object'              => 'Sprzeciw wobec przetwarzania (art. 21)',
        'portability'         => 'Przeniesienie danych (art. 20)',
    ];

    public const STATUSES = [
        'pending'     => 'Oczekuje potwierdzenia',
        'in_progress' => 'W trakcie realizacji',
        'completed'   => 'Zrealizowana',
        'rejected'    => 'Odrzucona',
    ];

    /**
     * Utwarza nowa prosbe z confirmation_token (24h validity).
     *
     * @return array{id:int, token:string}
     */
    public function createRequest(
        int $clubId,
        int $memberId,
        string $type,
        ?string $reason = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): array {
        if (!array_key_exists($type, self::TYPES)) {
            throw new \InvalidArgumentException('Nieprawidlowy typ prosby GDPR: ' . $type);
        }

        $token = bin2hex(random_bytes(32)); // 64 chars hex
        $expiresAt = date('Y-m-d H:i:s', time() + 24 * 3600);

        $id = $this->insert([
            'club_id'                       => $clubId,
            'member_id'                     => $memberId,
            'request_type'                  => $type,
            'status'                        => 'pending',
            'reason'                        => $reason ? mb_substr($reason, 0, 500) : null,
            'confirmation_token'            => $token,
            'confirmation_token_expires_at' => $expiresAt,
            'ip_address'                    => $ip ? mb_substr($ip, 0, 45) : null,
            'user_agent'                    => $userAgent ? mb_substr($userAgent, 0, 500) : null,
        ]);

        return ['id' => $id, 'token' => $token];
    }

    /**
     * Znajduje prosbe po confirmation_token (jesli niewygasly).
     */
    public function findByToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE confirmation_token = ?
               AND confirmation_token_expires_at >= NOW()
               AND status = 'pending'
             LIMIT 1"
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Potwierdza prosbe: status pending -> in_progress, czysci token.
     */
    public function confirm(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}`
             SET status = 'in_progress',
                 confirmed_at = NOW(),
                 confirmation_token = NULL,
                 confirmation_token_expires_at = NULL
             WHERE id = ? AND status = 'pending'"
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Marker realizacji prosby.
     */
    public function markCompleted(int $id, ?int $processedBy = null, ?string $notes = null, ?string $exportPath = null): bool
    {
        $expiresAt = $exportPath ? date('Y-m-d H:i:s', time() + 7 * 86400) : null;
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}`
             SET status = 'completed',
                 processed_at = NOW(),
                 processed_by = ?,
                 notes = COALESCE(?, notes),
                 export_file_path = ?,
                 export_file_expires_at = ?
             WHERE id = ?"
        );
        $stmt->execute([$processedBy, $notes, $exportPath, $expiresAt, $id]);
        return $stmt->rowCount() > 0;
    }

    public function markRejected(int $id, ?int $processedBy, string $notes): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}`
             SET status = 'rejected',
                 processed_at = NOW(),
                 processed_by = ?,
                 notes = ?
             WHERE id = ?"
        );
        $stmt->execute([$processedBy, $notes, $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Lista prosb czlonka — scoped per club_id + member_id.
     */
    public function listForMember(int $memberId, int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE member_id = ? AND club_id = ?
             ORDER BY requested_at DESC"
        );
        $stmt->execute([$memberId, $clubId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista prosb dla klubu (admin panel).
     */
    public function listForClub(int $clubId, ?string $status = null, int $limit = 100): array
    {
        $sql = "SELECT r.*, m.first_name, m.last_name, m.email, m.member_number
                FROM `{$this->table}` r
                JOIN members m ON m.id = r.member_id
                WHERE r.club_id = ?";
        $params = [$clubId];
        if ($status !== null && array_key_exists($status, self::STATUSES)) {
            $sql .= " AND r.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY r.requested_at DESC LIMIT " . max(1, min(500, $limit));
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cross-tenant guard: pobierz prosbe TYLKO jesli nalezy do (club_id, member_id).
     */
    public function findOwnedBy(int $id, int $memberId, int $clubId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE id = ? AND member_id = ? AND club_id = ?
             LIMIT 1"
        );
        $stmt->execute([$id, $memberId, $clubId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Cross-tenant guard (admin variant): pobierz prosbe nalezaca do klubu.
     */
    public function findForClub(int $id, int $clubId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, m.first_name, m.last_name, m.email, m.member_number
             FROM `{$this->table}` r
             JOIN members m ON m.id = r.member_id
             WHERE r.id = ? AND r.club_id = ?
             LIMIT 1"
        );
        $stmt->execute([$id, $clubId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
