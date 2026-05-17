<?php

namespace App\Models;

/**
 * Link M:N opiekun <-> czlonek.
 */
class GuardianMemberModel extends ClubScopedModel
{
    protected string $table = 'guardian_members';

    public function linkGuardianToMember(
        int $guardianId,
        int $memberId,
        int $clubId,
        string $relationship = 'parent',
        bool $primaryGuardian = false,
        bool $canPay = true,
        bool $canConsent = true,
        ?int $invitedByUserId = null
    ): int {
        $allowed = ['parent', 'legal_guardian', 'grandparent', 'other'];
        if (!in_array($relationship, $allowed, true)) {
            $relationship = 'parent';
        }

        $stmt = $this->db->prepare(
            "INSERT INTO guardian_members
                (guardian_id, member_id, club_id, relationship,
                 primary_guardian, can_pay, can_consent, invited_by_user_id, invited_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                relationship       = VALUES(relationship),
                primary_guardian   = VALUES(primary_guardian),
                can_pay            = VALUES(can_pay),
                can_consent        = VALUES(can_consent),
                invited_by_user_id = VALUES(invited_by_user_id)"
        );
        $stmt->execute([
            $guardianId, $memberId, $clubId, $relationship,
            $primaryGuardian ? 1 : 0,
            $canPay ? 1 : 0,
            $canConsent ? 1 : 0,
            $invitedByUserId,
        ]);
        $id = (int)$this->db->lastInsertId();
        if ($id > 0) return $id;

        $stmt = $this->db->prepare(
            "SELECT id FROM guardian_members WHERE guardian_id = ? AND member_id = ? LIMIT 1"
        );
        $stmt->execute([$guardianId, $memberId]);
        return (int)$stmt->fetchColumn();
    }

    public function markAccepted(int $guardianId, int $memberId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE guardian_members SET accepted_at = NOW()
             WHERE guardian_id = ? AND member_id = ? AND accepted_at IS NULL"
        );
        $stmt->execute([$guardianId, $memberId]);
    }

    /**
     * Wszyscy podopieczni danego opiekuna (z join na members).
     * Multi-tenant: wymaga club_id na guardian_members.club_id ORAZ
     * members.club_id (defense-in-depth).
     */
    public function forGuardian(int $guardianId, int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT gm.*, m.first_name, m.last_name, m.member_number, m.birth_date,
                    m.photo_path, m.status AS member_status
             FROM guardian_members gm
             INNER JOIN members m
                     ON m.id = gm.member_id
                    AND m.club_id = gm.club_id
                    AND m.club_id = ?
             WHERE gm.guardian_id = ?
               AND gm.club_id = ?
             ORDER BY gm.primary_guardian DESC, m.last_name ASC, m.first_name ASC"
        );
        $stmt->execute([$clubId, $guardianId, $clubId]);
        return $stmt->fetchAll();
    }

    public function forMember(int $memberId, int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT gm.*, g.email, g.first_name AS g_first_name, g.last_name AS g_last_name,
                    g.phone AS g_phone, g.email_verified_at, g.last_login_at
             FROM guardian_members gm
             INNER JOIN guardians g
                     ON g.id = gm.guardian_id
                    AND g.club_id = gm.club_id
             WHERE gm.member_id = ?
               AND gm.club_id = ?
             ORDER BY gm.primary_guardian DESC, gm.invited_at ASC"
        );
        $stmt->execute([$memberId, $clubId]);
        return $stmt->fetchAll();
    }

    /**
     * Czy opiekun ma uprawnienia do dziecka (membership active w danym klubie)?
     */
    public function isLinked(int $guardianId, int $memberId, int $clubId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM guardian_members
             WHERE guardian_id = ? AND member_id = ? AND club_id = ?
             LIMIT 1"
        );
        $stmt->execute([$guardianId, $memberId, $clubId]);
        return (bool)$stmt->fetchColumn();
    }

    public function findLink(int $guardianId, int $memberId, int $clubId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM guardian_members
             WHERE guardian_id = ? AND member_id = ? AND club_id = ?
             LIMIT 1"
        );
        $stmt->execute([$guardianId, $memberId, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function unlink(int $guardianMemberId, int $clubId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM guardian_members WHERE id = ? AND club_id = ?"
        );
        return $stmt->execute([$guardianMemberId, $clubId]);
    }
}
