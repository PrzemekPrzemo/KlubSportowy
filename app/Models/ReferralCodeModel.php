<?php

namespace App\Models;

/**
 * Migracja 081: club_referral_codes.
 *
 * Trzyma jeden aktywny kod afiliacyjny per klub (UNIQUE uniq_club).
 * Stara wersja kodu nadal pozostaje w tabeli z is_active=0 jesli klub
 * wygeneruje nowy (audyt + nie unieważnia historycznych rekordow
 * club_referrals.referral_code).
 */
class ReferralCodeModel extends BaseModel
{
    protected string $table = 'club_referral_codes';

    public function findForClub(int $clubId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
              WHERE club_id = ? AND is_active = 1
              ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE code = ? LIMIT 1"
        );
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findActiveByCode(string $code): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
              WHERE code = ? AND is_active = 1
              LIMIT 1"
        );
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function deactivateForClub(int $clubId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET is_active = 0 WHERE club_id = ?"
        );
        $stmt->execute([$clubId]);
    }

    public function incrementUsage(int $codeId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET times_used = times_used + 1 WHERE id = ?"
        );
        $stmt->execute([$codeId]);
    }
}
