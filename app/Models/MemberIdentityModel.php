<?php

namespace App\Models;

/**
 * Global model for unified member identities (cross-club).
 * NOT club-scoped — links identities across clubs.
 */
class MemberIdentityModel extends BaseModel
{
    protected string $table = 'member_identities';

    /**
     * Find or create an identity by hashing identifiers.
     * Searches: email_hash first, then pesel_hash, then phone_hash.
     * Creates new identity if not found. Returns identity row.
     */
    public function findOrCreate(string $email, ?string $pesel, ?string $phone, string $displayName): array
    {
        $emailHash = hash('sha256', strtolower(trim($email)));

        // Search by email hash first
        $stmt = $this->db->prepare("SELECT * FROM member_identities WHERE identity_hash = ? LIMIT 1");
        $stmt->execute([$emailHash]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }

        // Search by PESEL hash
        if (!empty($pesel)) {
            $peselHash = hash('sha256', trim($pesel));
            $stmt = $this->db->prepare("SELECT * FROM member_identities WHERE identity_hash = ? LIMIT 1");
            $stmt->execute([$peselHash]);
            $row = $stmt->fetch();
            if ($row) {
                return $row;
            }
        }

        // Search by phone hash
        if (!empty($phone)) {
            $phoneHash = hash('sha256', preg_replace('/\s+/', '', $phone));
            $stmt = $this->db->prepare("SELECT * FROM member_identities WHERE identity_hash = ? LIMIT 1");
            $stmt->execute([$phoneHash]);
            $row = $stmt->fetch();
            if ($row) {
                return $row;
            }
        }

        // Create new identity (use email hash as primary)
        $id = $this->insert([
            'identity_hash' => $emailHash,
            'portal_email'  => strtolower(trim($email)),
            'display_name'  => $displayName,
        ]);

        return $this->findById($id);
    }

    /**
     * Link a member record to an identity.
     */
    public function linkMember(int $identityId, int $memberId): bool
    {
        $stmt = $this->db->prepare("UPDATE members SET identity_id = ? WHERE id = ?");
        return $stmt->execute([$identityId, $memberId]);
    }

    /**
     * Get all distinct clubs for a given identity.
     */
    public function clubsForIdentity(int $identityId): array
    {
        $sql = "SELECT DISTINCT c.id, c.name, c.short_name, c.city, c.is_active
                FROM members m
                JOIN clubs c ON c.id = m.club_id
                WHERE m.identity_id = ? AND m.status = 'aktywny' AND c.is_active = 1
                ORDER BY c.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$identityId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all member records linked to this identity.
     */
    public function allMemberships(int $identityId): array
    {
        $sql = "SELECT m.*, c.name AS club_name, c.city AS club_city
                FROM members m
                JOIN clubs c ON c.id = m.club_id
                WHERE m.identity_id = ?
                ORDER BY c.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$identityId]);
        return $stmt->fetchAll();
    }

    /**
     * Find identity by portal email.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM member_identities WHERE portal_email = ? LIMIT 1");
        $stmt->execute([strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Verify portal password.
     */
    public function verifyPassword(array $identity, string $password): bool
    {
        if (empty($identity['portal_password'])) {
            return false;
        }
        return password_verify($password, $identity['portal_password']);
    }

    /**
     * Set portal password for an identity.
     */
    public function setPassword(int $identityId, string $password): bool
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE member_identities SET portal_password = ? WHERE id = ?");
        return $stmt->execute([$hash, $identityId]);
    }

    /**
     * Touch last login timestamp.
     */
    public function touchLogin(int $identityId): bool
    {
        $stmt = $this->db->prepare("UPDATE member_identities SET portal_last_login = NOW() WHERE id = ?");
        return $stmt->execute([$identityId]);
    }
}
