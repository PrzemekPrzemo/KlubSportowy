<?php

namespace App\Sports\Support;

use App\Models\ClubScopedModel;

/**
 * Pojedyncze podejścia (squat/bench/deadlift/strongman event).
 * Tabela: sport_strength_attempts. Multi-tenant strict.
 */
class SportStrengthAttemptModel extends ClubScopedModel
{
    protected string $table = 'sport_strength_attempts';

    public function listForMember(int $memberId, string $sportKey, ?string $liftType = null, int $limit = 100): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT * FROM sport_strength_attempts
                   WHERE club_id = ? AND member_id = ? AND sport_key = ?";
        $params = [$clubId, $memberId, $sportKey];
        if ($liftType !== null && $liftType !== '') {
            $sql      .= " AND lift_type = ?";
            $params[] = $liftType;
        }
        $sql .= " ORDER BY attempted_at DESC LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listForTournament(int $tournamentId, string $sportKey): array
    {
        $clubId = $this->clubId();
        $stmt   = $this->db->prepare(
            "SELECT a.*, m.first_name, m.last_name, m.member_number
             FROM sport_strength_attempts a
             JOIN members m ON m.id = a.member_id
             WHERE a.club_id = ? AND a.tournament_id = ? AND a.sport_key = ?
             ORDER BY a.lift_type, a.attempt_number, a.attempted_at"
        );
        $stmt->execute([$clubId, $tournamentId, $sportKey]);
        return $stmt->fetchAll();
    }

    /**
     * Leaderboard turnieju — najwyższe success-attempts per zawodnik (suma).
     */
    public function tournamentScoreboard(int $tournamentId, string $sportKey): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT a.member_id, m.first_name, m.last_name,
                          SUM(CASE WHEN a.success = 1 THEN COALESCE(a.weight_kg,0) ELSE 0 END) AS total_kg,
                          COUNT(*) AS attempts
                   FROM sport_strength_attempts a
                   JOIN members m ON m.id = a.member_id
                   WHERE a.club_id = ? AND a.tournament_id = ? AND a.sport_key = ?
                   GROUP BY a.member_id, m.first_name, m.last_name
                   ORDER BY total_kg DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId, $tournamentId, $sportKey]);
        return $stmt->fetchAll();
    }

    public function personalBests(int $memberId, string $sportKey): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT lift_type, MAX(weight_kg) AS best_kg
                   FROM sport_strength_attempts
                   WHERE club_id = ? AND member_id = ? AND sport_key = ? AND success = 1
                   GROUP BY lift_type
                   ORDER BY lift_type";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId, $memberId, $sportKey]);
        return $stmt->fetchAll();
    }

    public function insertScoped(array $data): int
    {
        $clubId = $this->clubId();
        if ($clubId !== null) {
            $data['club_id'] = $clubId;
        }
        return $this->insert($data);
    }

    public function deleteInClub(int $id): bool
    {
        $clubId = $this->clubId();
        $stmt   = $this->db->prepare(
            "DELETE FROM sport_strength_attempts WHERE id = ? AND club_id = ?"
        );
        return $stmt->execute([$id, $clubId]);
    }
}
