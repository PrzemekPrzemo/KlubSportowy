<?php

namespace App\Models;

class TournamentParticipantModel extends BaseModel
{
    protected string $table = 'tournament_participants';

    /**
     * Register a member for a tournament. Returns [bool $success, string $message].
     */
    public function registerMember(int $tournamentId, int $memberId, int $clubId): array
    {
        // Check tournament exists, is open, and belongs to club
        $stmt = $this->db->prepare(
            "SELECT * FROM tournaments WHERE id = ? AND club_id = ? AND status IN ('planowany','otwarty') LIMIT 1"
        );
        $stmt->execute([$tournamentId, $clubId]);
        $tournament = $stmt->fetch();
        if (!$tournament) {
            return [false, 'Turniej nie istnieje lub rejestracja jest zamknięta.'];
        }

        // Check if already registered
        $exist = $this->db->prepare(
            "SELECT id FROM tournament_participants WHERE tournament_id = ? AND member_id = ? LIMIT 1"
        );
        $exist->execute([$tournamentId, $memberId]);
        if ($exist->fetch()) {
            return [false, 'Jesteś już zgłoszony/a do tego turnieju.'];
        }

        // Check max_participants
        if (!empty($tournament['max_participants'])) {
            $count = $this->db->prepare("SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = ?");
            $count->execute([$tournamentId]);
            if ((int)$count->fetchColumn() >= (int)$tournament['max_participants']) {
                return [false, 'Brak wolnych miejsc w turnieju.'];
            }
        }

        $this->insert([
            'tournament_id' => $tournamentId,
            'member_id'     => $memberId,
            'status'        => 'zgłoszony',
        ]);
        return [true, 'Zgłoszenie zostało przyjęte.'];
    }

    public function withdrawMember(int $tournamentId, int $memberId): void
    {
        $this->db->prepare(
            "DELETE FROM tournament_participants WHERE tournament_id = ? AND member_id = ?"
        )->execute([$tournamentId, $memberId]);
    }

    public function statusForMember(int $tournamentId, int $memberId): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT status FROM tournament_participants WHERE tournament_id = ? AND member_id = ? LIMIT 1"
        );
        $stmt->execute([$tournamentId, $memberId]);
        $row = $stmt->fetch();
        return $row ? $row['status'] : null;
    }

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
