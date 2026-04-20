<?php

namespace App\Models;

class TournamentParticipantModel extends BaseModel
{
    protected string $table = 'tournament_participants';

    /**
     * List participants for a tournament, joined with member data.
     */
    public function listForTournament(int $tournamentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT tp.*, m.first_name, m.last_name, m.member_number
             FROM tournament_participants tp
             JOIN members m ON m.id = tp.member_id
             WHERE tp.tournament_id = ?
             ORDER BY tp.seed ASC, m.last_name ASC"
        );
        $stmt->execute([$tournamentId]);
        return $stmt->fetchAll();
    }
}
