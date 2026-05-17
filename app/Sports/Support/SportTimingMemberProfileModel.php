<?php

namespace App\Sports\Support;

use App\Models\ClubScopedModel;

/**
 * Profile zawodnika per timing-sport (PB, specialty, sport-specific metadata).
 * Tabela: sport_timing_member_profiles. Multi-tenant strict (club_id).
 */
class SportTimingMemberProfileModel extends ClubScopedModel
{
    protected string $table = 'sport_timing_member_profiles';

    public function findForMemberSport(int $memberId, string $sportKey): ?array
    {
        $clubId = $this->clubId();
        $stmt   = $this->db->prepare(
            "SELECT * FROM sport_timing_member_profiles
             WHERE member_id = ? AND sport_key = ? AND club_id = ?
             LIMIT 1"
        );
        $stmt->execute([$memberId, $sportKey, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsert(int $memberId, string $sportKey, array $data): void
    {
        $clubId = $this->clubId();
        $existing = $this->findForMemberSport($memberId, $sportKey);
        if ($existing) {
            $this->update((int)$existing['id'], $data);
            return;
        }
        $data['member_id'] = $memberId;
        $data['sport_key'] = $sportKey;
        $data['club_id']   = $clubId;
        $this->insert($data);
    }

    public function listForClubSport(string $sportKey): array
    {
        $clubId = $this->clubId();
        $stmt   = $this->db->prepare(
            "SELECT p.*, m.first_name, m.last_name, m.member_number
             FROM sport_timing_member_profiles p
             JOIN members m ON m.id = p.member_id
             WHERE p.club_id = ? AND p.sport_key = ?
             ORDER BY m.last_name, m.first_name"
        );
        $stmt->execute([$clubId, $sportKey]);
        return $stmt->fetchAll();
    }
}
