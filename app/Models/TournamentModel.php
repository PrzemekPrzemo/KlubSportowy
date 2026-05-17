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
     * Slugify text — polskie znaki -> ASCII, lowercase, [a-z0-9-].
     * Wzorzec ten sam co MemberModel::slugify (DRY) — duplikacja zalezy od tego,
     * ze MemberModel jest ClubScopedModel i nie chcemy wciagac jego scope tylko po
     * pomocnika tekstowego.
     */
    public static function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $map  = [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
            'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
        ];
        $text = strtr($text, $map);
        $text = preg_replace('/[^\x20-\x7E]/u', '', $text) ?? $text;
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-');
    }

    /**
     * Wygeneruj globalnie unikalny slug dla publicznej strony live.
     *
     * Format: <slugify(name)>-<6 char random>. Cap 80 znakow (limit kolumny).
     * Retry przy kolizji (UNIQUE constraint w bazie).
     */
    public function generatePublicLiveSlug(int $tournamentId): string
    {
        $stmt = $this->db->prepare("SELECT name FROM tournaments WHERE id = ? LIMIT 1");
        $stmt->execute([$tournamentId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException("Tournament $tournamentId not found");
        }

        $base = self::slugify((string)$row['name']);
        if ($base === '') {
            $base = 'turniej';
        }
        // 80 - 1 (-) - 6 (suffix) = 73 max base length
        if (strlen($base) > 73) {
            $base = rtrim(substr($base, 0, 73), '-');
        }

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $suffix    = strtolower(bin2hex(random_bytes(3))); // 6 hex char
            $candidate = $base . '-' . $suffix;
            $check = $this->db->prepare(
                "SELECT 1 FROM tournaments WHERE public_live_slug = ? AND id <> ? LIMIT 1"
            );
            $check->execute([$candidate, $tournamentId]);
            if (!$check->fetch()) {
                return $candidate;
            }
        }
        throw new \RuntimeException('Could not generate unique public_live_slug');
    }

    /**
     * Lookup turnieju po publicznym slug (BEZ scope club_id — slug jest globalny).
     * Zwraca null gdy brak lub gdy public_live_enabled=0 (defense in depth).
     */
    public function findByPublicSlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tournaments
             WHERE public_live_slug = ? AND public_live_enabled = 1
             LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Wszystkie mecze turnieju (publiczne — bez PII, tylko player_id/scores).
     * Sortowanie: po rundach + match_number (jak zwykla drabinka).
     */
    public function publicMatches(int $tournamentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT tm.id, tm.round, tm.match_number, tm.player1_id, tm.player2_id,
                    tm.winner_id, tm.score1, tm.score2, tm.scheduled_at,
                    m1.first_name AS p1_first, m1.last_name AS p1_last,
                    m2.first_name AS p2_first, m2.last_name AS p2_last
             FROM tournament_matches tm
             LEFT JOIN members m1 ON m1.id = tm.player1_id
             LEFT JOIN members m2 ON m2.id = tm.player2_id
             WHERE tm.tournament_id = ?
             ORDER BY tm.round ASC, tm.match_number ASC"
        );
        $stmt->execute([$tournamentId]);
        return $stmt->fetchAll();
    }

    /**
     * Najnowsze mecze ze zmianami (winner_id IS NOT NULL) — feed live.
     */
    public function recentResultsForLive(int $tournamentId, int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->db->prepare(
            "SELECT tm.id, tm.round, tm.match_number, tm.player1_id, tm.player2_id,
                    tm.winner_id, tm.score1, tm.score2,
                    m1.first_name AS p1_first, m1.last_name AS p1_last,
                    m2.first_name AS p2_first, m2.last_name AS p2_last
             FROM tournament_matches tm
             LEFT JOIN members m1 ON m1.id = tm.player1_id
             LEFT JOIN members m2 ON m2.id = tm.player2_id
             WHERE tm.tournament_id = ? AND tm.winner_id IS NOT NULL
             ORDER BY tm.id DESC
             LIMIT {$limit}"
        );
        $stmt->execute([$tournamentId]);
        return $stmt->fetchAll();
    }

    /**
     * Mecze utworzone/zaktualizowane po lastId — uzywane przez SSE stream
     * do wysylania tylko inkrementalnych aktualizacji. Tu uproszczone: bierzemy
     * mecze z id > lastId LUB z winner_id ustawionym a id <= lastId (revisits).
     * Dla pierwszej iteracji: id > lastId wystarczy.
     */
    public function matchesSinceId(int $tournamentId, int $lastId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $stmt = $this->db->prepare(
            "SELECT tm.id, tm.round, tm.match_number, tm.player1_id, tm.player2_id,
                    tm.winner_id, tm.score1, tm.score2, tm.scheduled_at,
                    m1.first_name AS p1_first, m1.last_name AS p1_last,
                    m2.first_name AS p2_first, m2.last_name AS p2_last
             FROM tournament_matches tm
             LEFT JOIN members m1 ON m1.id = tm.player1_id
             LEFT JOIN members m2 ON m2.id = tm.player2_id
             WHERE tm.tournament_id = ? AND tm.id > ?
             ORDER BY tm.id ASC
             LIMIT {$limit}"
        );
        $stmt->execute([$tournamentId, $lastId]);
        return $stmt->fetchAll();
    }

    /**
     * Najwyzsze tournament_matches.id dla turnieju + ostatni "modified" timestamp
     * jako dowod ze cokolwiek sie zmienilo (do prymitywnego diff-a w SSE).
     */
    public function maxMatchIdAndUpdated(int $tournamentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(MAX(id), 0) AS max_id,
                    SUM(CASE WHEN winner_id IS NOT NULL THEN 1 ELSE 0 END) AS finished_count,
                    COUNT(*) AS total
             FROM tournament_matches WHERE tournament_id = ?"
        );
        $stmt->execute([$tournamentId]);
        $row = $stmt->fetch();
        return [
            'max_id'         => (int)($row['max_id'] ?? 0),
            'finished_count' => (int)($row['finished_count'] ?? 0),
            'total'          => (int)($row['total'] ?? 0),
        ];
    }

    /**
     * Standings dla round_robin: zliczamy zwyciestwa per gracz w turnieju.
     */
    public function standingsForLive(int $tournamentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.id AS member_id, m.first_name, m.last_name,
                    SUM(CASE WHEN tm.winner_id = m.id THEN 1 ELSE 0 END) AS wins,
                    SUM(CASE WHEN tm.winner_id IS NOT NULL
                              AND tm.winner_id <> m.id
                              AND (tm.player1_id = m.id OR tm.player2_id = m.id)
                              THEN 1 ELSE 0 END) AS losses,
                    COUNT(DISTINCT CASE
                        WHEN tm.player1_id = m.id OR tm.player2_id = m.id THEN tm.id END) AS played
             FROM tournament_participants tp
             JOIN members m ON m.id = tp.member_id
             LEFT JOIN tournament_matches tm
                    ON tm.tournament_id = tp.tournament_id
                   AND (tm.player1_id = m.id OR tm.player2_id = m.id)
             WHERE tp.tournament_id = ?
             GROUP BY m.id, m.first_name, m.last_name
             ORDER BY wins DESC, losses ASC, m.last_name ASC"
        );
        $stmt->execute([$tournamentId]);
        return $stmt->fetchAll();
    }

    /**
     * Nadchodzace mecze (winner_id IS NULL i obaj gracze ustawieni).
     */
    public function upcomingMatchesForLive(int $tournamentId, int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->db->prepare(
            "SELECT tm.id, tm.round, tm.match_number, tm.player1_id, tm.player2_id,
                    tm.scheduled_at,
                    m1.first_name AS p1_first, m1.last_name AS p1_last,
                    m2.first_name AS p2_first, m2.last_name AS p2_last
             FROM tournament_matches tm
             LEFT JOIN members m1 ON m1.id = tm.player1_id
             LEFT JOIN members m2 ON m2.id = tm.player2_id
             WHERE tm.tournament_id = ?
               AND tm.winner_id IS NULL
               AND tm.player1_id IS NOT NULL
               AND tm.player2_id IS NOT NULL
             ORDER BY tm.round ASC, tm.match_number ASC
             LIMIT {$limit}"
        );
        $stmt->execute([$tournamentId]);
        return $stmt->fetchAll();
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
