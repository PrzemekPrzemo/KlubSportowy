<?php

namespace App\Sports\Gymnastics\Models;

use App\Models\ClubScopedModel;

class GymnasticsMinorModel extends ClubScopedModel
{
    protected string $table = 'gymnastics_minor_consents';

    public function consentForMember(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM gymnastics_minor_consents WHERE club_id = ? AND member_id = ? LIMIT 1"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetch() ?: null;
    }

    public function setConsent(array $data): void
    {
        $clubId   = $this->clubId();
        $memberId = (int)$data['member_id'];

        $existing = $this->consentForMember($memberId);
        if ($existing) {
            $stmt = $this->db->prepare(
                "UPDATE gymnastics_minor_consents
                 SET guardian_name=?, guardian_phone=?, photo_consent=?, media_consent=?, signed_date=?, notes=?
                 WHERE club_id=? AND member_id=?"
            );
            $stmt->execute([
                $data['guardian_name'],
                $data['guardian_phone'] ?? null,
                $data['photo_consent'] ? 1 : 0,
                $data['media_consent'] ? 1 : 0,
                $data['signed_date'] ?? null,
                $data['notes'] ?? null,
                $clubId,
                $memberId,
            ]);
        } else {
            $this->insert([
                'member_id'     => $memberId,
                'guardian_name' => $data['guardian_name'],
                'guardian_phone'=> $data['guardian_phone'] ?? null,
                'photo_consent' => $data['photo_consent'] ? 1 : 0,
                'media_consent' => $data['media_consent'] ? 1 : 0,
                'signed_date'   => $data['signed_date'] ?? null,
                'notes'         => $data['notes'] ?? null,
            ]);
        }
    }

    public function listWithMembers(): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, m.first_name, m.last_name, m.member_number
             FROM gymnastics_minor_consents c
             JOIN members m ON m.id = c.member_id
             WHERE c.club_id = ?
             ORDER BY m.last_name, m.first_name"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }
}
