<?php

namespace App\Models;

/**
 * Per-member E2E key material for the messenger.
 *
 * IMPORTANT: passphrase_hash here serves ONLY for client-side passphrase
 * verification (so we can refuse decryption attempts with a wrong passphrase
 * without round-tripping to other devices). The actual AES key is derived
 * client-side via PBKDF2/HKDF in the browser and is NEVER sent to the server.
 *
 * As such, the server cannot decrypt any E2E-encrypted message body — by design.
 */
class MessengerMemberKeyModel extends BaseModel
{
    protected string $table = 'messenger_member_keys';

    public function findForMember(int $memberId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM messenger_member_keys WHERE member_id = ? LIMIT 1");
        $stmt->execute([$memberId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Save / update passphrase hash + optional recovery phrase (already encrypted client-side or
     * server-side via Encryption::encryptForClub by the caller).
     */
    public function upsert(int $memberId, string $passphraseHash, ?string $recoveryEncrypted = null): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO messenger_member_keys
                (member_id, passphrase_hash, recovery_phrase_encrypted, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                passphrase_hash = VALUES(passphrase_hash),
                recovery_phrase_encrypted = COALESCE(VALUES(recovery_phrase_encrypted), recovery_phrase_encrypted),
                updated_at = NOW()
        ");
        $stmt->execute([$memberId, $passphraseHash, $recoveryEncrypted]);
    }

    public function recordAttempt(int $memberId, bool $ok): void
    {
        if ($ok) {
            $stmt = $this->db->prepare("
                UPDATE messenger_member_keys
                SET setup_attempts = 0, last_attempt_at = NOW()
                WHERE member_id = ?
            ");
            $stmt->execute([$memberId]);
            return;
        }
        $stmt = $this->db->prepare("
            UPDATE messenger_member_keys
            SET setup_attempts = setup_attempts + 1, last_attempt_at = NOW()
            WHERE member_id = ?
        ");
        $stmt->execute([$memberId]);
    }

    public function disable(int $memberId): void
    {
        $stmt = $this->db->prepare("DELETE FROM messenger_member_keys WHERE member_id = ?");
        $stmt->execute([$memberId]);
    }
}
