<?php

namespace App\Models;

use App\Helpers\Encryption;
use App\Models\Traits\EncryptsFields;

class MinorConsentModel extends ClubScopedModel
{
    use EncryptsFields;

    protected string $table = 'minor_consents';

    /** Dane opiekuna + PESEL = dane szczególnej kategorii. */
    protected static array $ENCRYPTED_FIELDS = [
        'guardian_phone', 'guardian_email', 'guardian_id_number', 'notes', 'document_path'
    ];

    public function forMember(int $memberId): ?array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM minor_consents
             WHERE club_id = ? AND member_id = ?
             LIMIT 1"
        );
        $stmt->execute([$clubId, $memberId]);
        return $this->decryptRow($stmt->fetch() ?: null);
    }

    public function upsert(int $memberId, array $data): void
    {
        $clubId = $this->clubId();
        $data['club_id']   = $clubId;
        $data['member_id'] = $memberId;
        // Szyfruj pola wrażliwe przed zapisem
        $data = $this->encryptFields($data);
        $stmt = $this->db->prepare(
            "INSERT INTO minor_consents (club_id, member_id, guardian_name, guardian_id_number,
                                          guardian_phone, guardian_email, photo_consent, media_consent,
                                          travel_consent, medical_decisions, signed_date, valid_until,
                                          document_path, signed_ip, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                guardian_name      = VALUES(guardian_name),
                guardian_id_number = VALUES(guardian_id_number),
                guardian_phone     = VALUES(guardian_phone),
                guardian_email     = VALUES(guardian_email),
                photo_consent      = VALUES(photo_consent),
                media_consent      = VALUES(media_consent),
                travel_consent     = VALUES(travel_consent),
                medical_decisions  = VALUES(medical_decisions),
                signed_date        = VALUES(signed_date),
                valid_until        = VALUES(valid_until),
                notes              = VALUES(notes)"
        );
        $stmt->execute([
            $clubId, $memberId,
            $data['guardian_name'] ?? '',
            $data['guardian_id_number'] ?? null,
            $data['guardian_phone'] ?? null,
            $data['guardian_email'] ?? null,
            (int)($data['photo_consent'] ?? 0),
            (int)($data['media_consent'] ?? 0),
            (int)($data['travel_consent'] ?? 0),
            (int)($data['medical_decisions'] ?? 0),
            $data['signed_date'] ?? date('Y-m-d'),
            $data['valid_until'] ?? null,
            $data['document_path'] ?? null,
            $data['signed_ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? null),
            $data['notes'] ?? null,
        ]);
    }

    /**
     * Aktywni małoletni (wg birth_date w members) bez podpisanej zgody.
     */
    public function minorsWithoutConsent(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT m.id, m.first_name, m.last_name, m.member_number, m.birth_date
             FROM members m
             LEFT JOIN minor_consents mc ON mc.member_id = m.id AND mc.club_id = m.club_id
             WHERE m.club_id = ?
               AND m.status = 'aktywny'
               AND m.birth_date IS NOT NULL
               AND TIMESTAMPDIFF(YEAR, m.birth_date, CURDATE()) < 18
               AND mc.id IS NULL
             ORDER BY m.last_name"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
