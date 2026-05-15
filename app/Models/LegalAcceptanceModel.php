<?php

namespace App\Models;

/**
 * Audytowy log akceptacji dokumentów prawnych (legal_acceptances).
 *
 * Każdy zapis: user_id|club_id|member_id (1 z 3 lub anon), document_id, kontekst,
 * IP, User-Agent, znacznik czasu.
 */
class LegalAcceptanceModel extends BaseModel
{
    protected string $table = 'legal_acceptances';

    public const CONTEXTS = ['registration', 'onboarding', 'renewal', 'member_signup', 'login_required'];

    /**
     * Records one acceptance.
     * Returns the inserted row id.
     */
    public function record(array $data): int
    {
        $context = $data['context'] ?? 'registration';
        if (!in_array($context, self::CONTEXTS, true)) {
            throw new \InvalidArgumentException('Nieznany kontekst akceptacji: ' . $context);
        }

        $stmt = $this->db->prepare(
            "INSERT INTO legal_acceptances
                (user_id, club_id, member_id, document_id, ip_address, user_agent, context, accepted_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            !empty($data['user_id'])    ? (int)$data['user_id']    : null,
            !empty($data['club_id'])    ? (int)$data['club_id']    : null,
            !empty($data['member_id'])  ? (int)$data['member_id']  : null,
            (int)($data['document_id'] ?? 0),
            $data['ip_address'] ?? null,
            isset($data['user_agent']) ? mb_substr((string)$data['user_agent'], 0, 500) : null,
            $context,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Has user already accepted current version of given document type?
     */
    public function hasAcceptedCurrent(int $userId, string $docType, string $locale = 'pl'): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1
               FROM legal_acceptances la
               JOIN legal_documents ld ON ld.id = la.document_id
              WHERE la.user_id = ?
                AND ld.doc_type = ?
                AND ld.locale = ?
                AND ld.is_current = 1
              LIMIT 1"
        );
        $stmt->execute([$userId, $docType, $locale]);
        return (bool)$stmt->fetchColumn();
    }

    /** All acceptances for a user (chronologically, newest first). */
    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT la.*, ld.doc_type, ld.version, ld.title
               FROM legal_acceptances la
               JOIN legal_documents ld ON ld.id = la.document_id
              WHERE la.user_id = ?
              ORDER BY la.accepted_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** All acceptances for a club. */
    public function forClub(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT la.*, ld.doc_type, ld.version, ld.title
               FROM legal_acceptances la
               JOIN legal_documents ld ON ld.id = la.document_id
              WHERE la.club_id = ?
              ORDER BY la.accepted_at DESC"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
