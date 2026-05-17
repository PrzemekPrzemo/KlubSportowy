<?php

namespace App\Sports\Support;

use App\Models\ClubScopedModel;

/**
 * Profile zawodnika dla strength sportów (strongman/powerlifting/weightlifting).
 * Tabela: sport_strength_member (PK = member_id). Multi-tenant.
 */
class SportStrengthMemberModel extends ClubScopedModel
{
    protected string $table = 'sport_strength_member';

    public function findForMember(int $memberId): ?array
    {
        $clubId = $this->clubId();
        $stmt   = $this->db->prepare(
            "SELECT * FROM sport_strength_member WHERE member_id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([$memberId, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsert(int $memberId, string $sportKey, array $data): void
    {
        $clubId   = $this->clubId();
        $existing = $this->findForMember($memberId);
        if ($existing) {
            $set    = [];
            $params = [];
            foreach ($data as $k => $v) {
                $set[]    = "`{$k}` = ?";
                $params[] = $v;
            }
            $params[] = $memberId;
            $params[] = $clubId;
            $stmt = $this->db->prepare(
                "UPDATE sport_strength_member SET " . implode(', ', $set)
                . " WHERE member_id = ? AND club_id = ?"
            );
            $stmt->execute($params);
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
            "SELECT s.*, m.first_name, m.last_name, m.member_number
             FROM sport_strength_member s
             JOIN members m ON m.id = s.member_id
             WHERE s.club_id = ? AND s.sport_key = ?
             ORDER BY s.total_pb_kg DESC, m.last_name"
        );
        $stmt->execute([$clubId, $sportKey]);
        return $stmt->fetchAll();
    }
}
