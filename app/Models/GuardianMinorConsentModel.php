<?php

namespace App\Models;

/**
 * Granularne zgody RODO art. 8 — per opiekun-dziecko-typ.
 *
 * Idempotentne: ponowne grant tego samego (guardian,member,type) aktualizuje
 * rekord (revoked_at=NULL, granted=1, granted_at=NOW()).
 * Multi-tenant: kazdy zapis WERYFIKUJE club_id na guardian i member.
 */
class GuardianMinorConsentModel extends ClubScopedModel
{
    protected string $table = 'guardian_minor_consents';

    public const TYPES = [
        'data_processing',
        'image_use',
        'training_participation',
        'tournament_participation',
        'medical_treatment',
        'communication_email',
        'communication_sms',
    ];

    public static function labelFor(string $type): string
    {
        return match ($type) {
            'data_processing'          => 'Przetwarzanie danych osobowych',
            'image_use'                => 'Wykorzystanie wizerunku',
            'training_participation'   => 'Udzial w treningach',
            'tournament_participation' => 'Udzial w turniejach / zawodach',
            'medical_treatment'        => 'Decyzje medyczne w sytuacji awaryjnej',
            'communication_email'      => 'Komunikacja e-mail',
            'communication_sms'        => 'Komunikacja SMS',
            default                    => $type,
        };
    }

    public static function isValidType(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    /**
     * Udziela zgody (lub re-grantuje wczesniej wycofana).
     * Cross-tenant guard: club_id MUSI byc spojny w guardian, member i wpisie.
     */
    public function grantConsent(
        int $guardianId,
        int $memberId,
        int $clubId,
        string $consentType,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $notes = null
    ): int {
        if (!self::isValidType($consentType)) {
            throw new \InvalidArgumentException("Nieznany typ zgody: {$consentType}");
        }
        $this->assertConsistentTenant($guardianId, $memberId, $clubId);

        if ($userAgent !== null && strlen($userAgent) > 500) {
            $userAgent = substr($userAgent, 0, 500);
        }
        if ($ipAddress !== null && strlen($ipAddress) > 45) {
            $ipAddress = substr($ipAddress, 0, 45);
        }

        $stmt = $this->db->prepare(
            "INSERT INTO guardian_minor_consents
                (guardian_id, member_id, club_id, consent_type, granted,
                 granted_at, revoked_at, ip_address, user_agent, notes)
             VALUES (?, ?, ?, ?, 1, NOW(), NULL, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                granted    = 1,
                granted_at = NOW(),
                revoked_at = NULL,
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                notes      = VALUES(notes)"
        );
        $stmt->execute([
            $guardianId, $memberId, $clubId, $consentType,
            $ipAddress, $userAgent, $notes,
        ]);
        $id = (int)$this->db->lastInsertId();
        if ($id > 0) return $id;

        $stmt = $this->db->prepare(
            "SELECT id FROM guardian_minor_consents
             WHERE guardian_id = ? AND member_id = ? AND consent_type = ?"
        );
        $stmt->execute([$guardianId, $memberId, $consentType]);
        return (int)$stmt->fetchColumn();
    }

    public function revokeConsent(
        int $guardianId,
        int $memberId,
        int $clubId,
        string $consentType,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): bool {
        if (!self::isValidType($consentType)) {
            throw new \InvalidArgumentException("Nieznany typ zgody: {$consentType}");
        }
        $this->assertConsistentTenant($guardianId, $memberId, $clubId);

        if ($userAgent !== null && strlen($userAgent) > 500) {
            $userAgent = substr($userAgent, 0, 500);
        }
        if ($ipAddress !== null && strlen($ipAddress) > 45) {
            $ipAddress = substr($ipAddress, 0, 45);
        }

        $stmt = $this->db->prepare(
            "INSERT INTO guardian_minor_consents
                (guardian_id, member_id, club_id, consent_type, granted,
                 granted_at, revoked_at, ip_address, user_agent)
             VALUES (?, ?, ?, ?, 0, NOW(), NOW(), ?, ?)
             ON DUPLICATE KEY UPDATE
                granted    = 0,
                revoked_at = NOW(),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent)"
        );
        return $stmt->execute([
            $guardianId, $memberId, $clubId, $consentType,
            $ipAddress, $userAgent,
        ]);
    }

    /**
     * Wszystkie zgody dla dziecka, dostepne dla danego opiekuna.
     */
    public function consentsForMember(int $guardianId, int $memberId, int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM guardian_minor_consents
             WHERE guardian_id = ? AND member_id = ? AND club_id = ?"
        );
        $stmt->execute([$guardianId, $memberId, $clubId]);
        $rows = $stmt->fetchAll();

        $byType = [];
        foreach ($rows as $r) {
            $byType[$r['consent_type']] = $r;
        }

        $out = [];
        foreach (self::TYPES as $t) {
            $out[$t] = $byType[$t] ?? [
                'consent_type' => $t,
                'granted'      => 0,
                'granted_at'   => null,
                'revoked_at'   => null,
            ];
        }
        return $out;
    }

    /**
     * Czy konkretna zgoda jest aktualnie udzielona?
     * Uzywane gdy klub chce sprawdzic czy moze publikowac zdjecia / wysylac SMS.
     */
    public function isGranted(int $memberId, string $consentType, int $clubId): bool
    {
        if (!self::isValidType($consentType)) return false;
        $stmt = $this->db->prepare(
            "SELECT 1 FROM guardian_minor_consents
             WHERE member_id = ? AND consent_type = ? AND club_id = ?
               AND granted = 1 AND revoked_at IS NULL
             LIMIT 1"
        );
        $stmt->execute([$memberId, $consentType, $clubId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Defense-in-depth: zarowno guardian.club_id jak i member.club_id muszą
     * byc zgodne z $clubId — chroni przed cross-tenant attack jesli atakujacy
     * przeslebialby memberId nalezacy do innego klubu.
     */
    private function assertConsistentTenant(int $guardianId, int $memberId, int $clubId): void
    {
        $stmt = $this->db->prepare(
            "SELECT (SELECT club_id FROM guardians WHERE id = ?) AS g_club,
                    (SELECT club_id FROM members   WHERE id = ?) AS m_club"
        );
        $stmt->execute([$guardianId, $memberId]);
        $row = $stmt->fetch();

        if (!$row || (int)($row['g_club'] ?? 0) !== $clubId || (int)($row['m_club'] ?? 0) !== $clubId) {
            throw new \RuntimeException('Cross-tenant consent operation blocked.');
        }
    }
}
