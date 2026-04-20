<?php

namespace App\Models;

class TournamentModel extends ClubScopedModel
{
    protected string $table = 'tournaments';

    /**
     * List tournaments for the current club, with participant count.
     * Optionally filter by sport_key.
     */
    public function listForClub(?string $sportKey = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT t.*,
                       COUNT(tp.id) AS participant_count
                FROM tournaments t
                LEFT JOIN tournament_participants tp ON tp.tournament_id = t.id
                WHERE t.club_id = ?";
        $params = [$clubId];

        if ($sportKey !== null) {
            $sql .= " AND t.sport_key = ?";
            $params[] = $sportKey;
        }

        $sql .= " GROUP BY t.id ORDER BY t.date_start DESC, t.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get a tournament with its participants (including member data).
     */
    public function withParticipants(int $id): ?array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM tournaments WHERE id = ? AND club_id = ?"
        );
        $stmt->execute([$id, $clubId]);
        $tournament = $stmt->fetch();
        if (!$tournament) {
            return null;
        }

        $stmt2 = $this->db->prepare(
            "SELECT tp.*, m.first_name, m.last_name, m.member_number
             FROM tournament_participants tp
             JOIN members m ON m.id = tp.member_id
             WHERE tp.tournament_id = ?
             ORDER BY tp.seed ASC, m.last_name ASC"
        );
        $stmt2->execute([$id]);
        $tournament['participants'] = $stmt2->fetchAll();

        return $tournament;
    }

    /**
     * Fetch all matches for a tournament with player/winner names.
     */
    public function brackets(int $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT tm.*,
                    CONCAT(m1.last_name, ' ', m1.first_name) AS player1_name,
                    CONCAT(m2.last_name, ' ', m2.first_name) AS player2_name,
                    CONCAT(mw.last_name, ' ', mw.first_name) AS winner_name
             FROM tournament_matches tm
             LEFT JOIN members m1 ON m1.id = tm.player1_id
             LEFT JOIN members m2 ON m2.id = tm.player2_id
             LEFT JOIN members mw ON mw.id = tm.winner_id
             WHERE tm.tournament_id = ?
             ORDER BY tm.round ASC, tm.match_number ASC"
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    /**
     * Generate single-elimination bracket for a tournament.
     * Shuffles participants, pads with BYE (NULL) if not a power of 2.
     */
    public function generateBracket(int $tournamentId): void
    {
        // Fetch participants
        $stmt = $this->db->prepare(
            "SELECT member_id FROM tournament_participants WHERE tournament_id = ? ORDER BY RAND()"
        );
        $stmt->execute([$tournamentId]);
        $participants = array_column($stmt->fetchAll(), 'member_id');

        $count = count($participants);
        if ($count < 2) {
            return;
        }

        // Pad to next power of 2
        $slots = (int)pow(2, ceil(log($count, 2)));
        while (count($participants) < $slots) {
            $participants[] = null; // BYE
        }

        // Shuffle
        shuffle($participants);

        // Delete existing matches
        $this->db->prepare("DELETE FROM tournament_matches WHERE tournament_id = ?")
                 ->execute([$tournamentId]);

        // Create round 1 matches
        $matchNumber = 1;
        for ($i = 0; $i < $slots; $i += 2) {
            $this->db->prepare(
                "INSERT INTO tournament_matches
                    (tournament_id, round, match_number, player1_id, player2_id)
                 VALUES (?, 1, ?, ?, ?)"
            )->execute([
                $tournamentId,
                $matchNumber,
                $participants[$i],
                $participants[$i + 1],
            ]);
            $matchNumber++;
        }

        // Set tournament status to active
        $this->db->prepare("UPDATE tournaments SET status = 'active' WHERE id = ?")
                 ->execute([$tournamentId]);
    }

    /**
     * Record match result: set winner, scores, mark loser as eliminated.
     */
    public function recordResult(int $matchId, int $winnerId, string $score1, string $score2): void
    {
        // Update match
        $this->db->prepare(
            "UPDATE tournament_matches
             SET winner_id = ?, score1 = ?, score2 = ?
             WHERE id = ?"
        )->execute([$winnerId, $score1, $score2, $matchId]);

        // Determine loser: fetch match players
        $stmt = $this->db->prepare(
            "SELECT tournament_id, player1_id, player2_id FROM tournament_matches WHERE id = ?"
        );
        $stmt->execute([$matchId]);
        $match = $stmt->fetch();

        if (!$match) {
            return;
        }

        $loserId = ((int)$match['player1_id'] === $winnerId)
            ? $match['player2_id']
            : $match['player1_id'];

        if ($loserId) {
            $this->db->prepare(
                "UPDATE tournament_participants
                 SET eliminated = 1
                 WHERE tournament_id = ? AND member_id = ?"
            )->execute([$match['tournament_id'], $loserId]);
        }
    }
}
