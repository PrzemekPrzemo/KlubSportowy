<?php

declare(strict_types=1);

namespace App\Helpers\Ranking;

use App\Helpers\Database;
use PDO;

/**
 * Orchestrator: pobiera uczestników/wyniki, wybiera strategię, UPSERTuje
 * sport_rankings. Respektuje club_id zapisane w turnieju/evencie — nie wymaga
 * aktywnego ClubContext (tryb cron-friendly).
 */
final class RankingEngine
{
    /**
     * @return array<int, array<string, mixed>> per-member summary
     */
    public static function recalculateForTournament(int $tournamentId): array
    {
        $db = Database::pdo();
        $stmt = $db->prepare("SELECT * FROM `tournaments` WHERE id = ? LIMIT 1");
        $stmt->execute([$tournamentId]);
        $tour = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tour) {
            return [];
        }
        $clubId   = (int)$tour['club_id'];
        $sportKey = (string)$tour['sport_key'];
        // tournaments has date_start, no season — derive year.
        $season = isset($tour['season']) && $tour['season'] !== null
            ? (string)$tour['season']
            : (string)(int)substr((string)($tour['date_start'] ?? date('Y-m-d')), 0, 4);

        $participants = self::participantsFromTournament($db, $tournamentId);
        if ($participants === []) {
            return [];
        }
        return self::applyStrategy($clubId, $sportKey, $season, $participants);
    }

    /**
     * Async wrapper: dla DUŻYCH turniejów (>100 uczestników) próbuje wepchnąć
     * przeliczenie do kolejki zadań. Jeśli kolejka niedostępna — fallback do
     * synchronicznego recalc (z error_log warning).
     *
     * Zwraca true gdy zaplanowano async, false gdy wykonano synchronicznie
     * (lub w razie błędu kolejki — wynik synchronicznego zostaje zapisany).
     */
    public static function recalculateForTournamentAsync(int $tournamentId): bool
    {
        // Próbujemy znaleźć infrastrukturę kolejki — graceful fallback.
        try {
            if (class_exists(\App\Helpers\Queue::class)
                && method_exists(\App\Helpers\Queue::class, 'push')) {
                /** @phpstan-ignore-next-line */
                \App\Helpers\Queue::push('ranking.recalculate_tournament', ['tournament_id' => $tournamentId]);
                return true;
            }
        } catch (\Throwable $e) {
            error_log('RankingEngine async push failed: ' . $e->getMessage());
        }
        // Fallback synchronous.
        self::recalculateForTournament($tournamentId);
        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function recalculateForEvent(int $eventId): array
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT e.id, e.club_id, e.event_date, s.`key` AS sport_key
             FROM `events` e
             LEFT JOIN `sports` s ON s.id = e.sport_id
             WHERE e.id = ? LIMIT 1"
        );
        $stmt->execute([$eventId]);
        $ev = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ev || empty($ev['sport_key'])) {
            return [];
        }
        $clubId   = (int)$ev['club_id'];
        $sportKey = (string)$ev['sport_key'];
        $season   = (string)(int)substr((string)$ev['event_date'], 0, 4);

        $participants = self::participantsFromEvent($db, $eventId);
        if ($participants === []) {
            return [];
        }
        return self::applyStrategy($clubId, $sportKey, $season, $participants);
    }

    /**
     * Pełne przeliczenie sezonu — kasuje istniejące rankingi i replayuje
     * wszystkie zakończone turnieje + eventy z danego sezonu chronologicznie.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function recalculateForSport(string $sportKey, ?string $season = null): array
    {
        $db = Database::pdo();
        $season ??= (string)(int)date('Y');

        // 1. Wyczyść ranking dla tego sezonu (tylko per-klub kluby których dotyczy).
        $clubsStmt = $db->prepare(
            "SELECT DISTINCT club_id FROM `tournaments`
             WHERE sport_key = ? AND YEAR(date_start) = ?"
        );
        $clubsStmt->execute([$sportKey, (int)$season]);
        $clubs = array_map('intval', array_column($clubsStmt->fetchAll(PDO::FETCH_ASSOC), 'club_id'));

        foreach ($clubs as $cid) {
            $db->prepare(
                "DELETE FROM `sport_rankings` WHERE club_id = ? AND sport_key = ? AND season = ?"
            )->execute([$cid, $sportKey, $season]);
        }

        $summary = [];

        // 2. Replay turniejów (status='finished' lub 'active') po dacie.
        $tStmt = $db->prepare(
            "SELECT id FROM `tournaments`
             WHERE sport_key = ? AND YEAR(date_start) = ?
               AND status IN ('finished','active')
             ORDER BY date_start ASC, id ASC"
        );
        $tStmt->execute([$sportKey, (int)$season]);
        foreach ($tStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $r = self::recalculateForTournament((int)$row['id']);
            $summary = self::mergeSummary($summary, $r);
        }

        // 3. Replay eventów (zakończonych) tego sportu.
        try {
            $eStmt = $db->prepare(
                "SELECT e.id FROM `events` e
                 JOIN `sports` s ON s.id = e.sport_id
                 WHERE s.`key` = ? AND YEAR(e.event_date) = ?
                   AND e.status = 'zakonczone'
                 ORDER BY e.event_date ASC, e.id ASC"
            );
            $eStmt->execute([$sportKey, (int)$season]);
            foreach ($eStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $r = self::recalculateForEvent((int)$row['id']);
                $summary = self::mergeSummary($summary, $r);
            }
        } catch (\Throwable) {
            // events table may not exist in some environments — ignore.
        }

        return array_values($summary);
    }

    // ---------------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------------

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function applyStrategy(int $clubId, string $sportKey, string $season, array $participants): array
    {
        $strategy = RankingStrategyFactory::forSport($sportKey);

        $memberIds = array_values(array_unique(array_map(
            static fn($p) => (int)($p['member_id'] ?? 0),
            $participants
        )));
        $memberIds = array_values(array_filter($memberIds, static fn($id) => $id > 0));
        if ($memberIds === []) {
            return [];
        }

        $current = self::currentRankings($clubId, $sportKey, $season, $memberIds);
        $context = ['sport_key' => $sportKey, 'season' => $season, 'club_id' => $clubId];

        $result = $strategy->recalculate($participants, $current, $context);
        self::persist($clubId, $sportKey, $season, $result);
        return $result;
    }

    /**
     * @param int[] $memberIds
     * @return array<int, array<string, mixed>>
     */
    private static function currentRankings(int $clubId, string $sportKey, string $season, array $memberIds): array
    {
        if ($memberIds === []) {
            return [];
        }
        $db = Database::pdo();
        $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
        $sql = "SELECT member_id, ranking_points AS points, competitions_count AS games_played, wins
                FROM `sport_rankings`
                WHERE club_id = ? AND sport_key = ? AND season = ?
                  AND member_id IN ($placeholders)";
        $stmt = $db->prepare($sql);
        $stmt->execute(array_merge([$clubId, $sportKey, $season], $memberIds));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private static function persist(int $clubId, string $sportKey, string $season, array $rows): void
    {
        if ($rows === []) {
            return;
        }
        $db = Database::pdo();

        // last_calculated_at jest opcjonalne — added by migration 057.
        $hasLastCalc = self::columnExists($db, 'sport_rankings', 'last_calculated_at');

        $sql = "INSERT INTO `sport_rankings`
                  (club_id, member_id, sport_key, season, ranking_points, competitions_count, wins"
                . ($hasLastCalc ? ", last_calculated_at" : "") . ")
                VALUES (?, ?, ?, ?, ?, ?, ?"
                . ($hasLastCalc ? ", NOW()" : "") . ")
                ON DUPLICATE KEY UPDATE
                  ranking_points     = VALUES(ranking_points),
                  competitions_count = VALUES(competitions_count),
                  wins               = VALUES(wins)"
                . ($hasLastCalc ? ", last_calculated_at = NOW()" : "");
        $stmt = $db->prepare($sql);

        foreach ($rows as $r) {
            $stmt->execute([
                $clubId,
                (int)$r['member_id'],
                $sportKey,
                $season,
                (int)$r['new_points'],
                (int)($r['games_played'] ?? 0),
                (int)($r['wins'] ?? 0),
            ]);
        }

        // Pozycje w rankingu — proste przepisanie wg ranking_points DESC.
        $rows2 = $db->prepare(
            "SELECT id FROM `sport_rankings`
             WHERE club_id = ? AND sport_key = ? AND season = ?
             ORDER BY ranking_points DESC, id ASC"
        );
        $rows2->execute([$clubId, $sportKey, $season]);
        $pos = 1;
        $upd = $db->prepare("UPDATE `sport_rankings` SET ranking_position = ? WHERE id = ?");
        foreach ($rows2->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $upd->execute([$pos++, (int)$row['id']]);
        }
    }

    /**
     * Sklada uczestników z `tournament_matches`: każdy uczestnik dostaje
     * `finishPlace` na podstawie rundy, w której odpadł (winner = 1,
     * przegrany finału = 2, półfinału = 3, itd.).
     *
     * @return array<int, array<string, mixed>>
     */
    private static function participantsFromTournament(PDO $db, int $tournamentId): array
    {
        // Zbierz mecze.
        $stmt = $db->prepare("SELECT * FROM `tournament_matches` WHERE tournament_id = ?");
        $stmt->execute([$tournamentId]);
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Zbierz uczestników (gwarantuje pełną listę nawet jeśli mecze niewypełnione).
        $partStmt = $db->prepare("SELECT member_id FROM `tournament_participants` WHERE tournament_id = ?");
        $partStmt->execute([$tournamentId]);
        $memberIds = array_map('intval', array_column($partStmt->fetchAll(PDO::FETCH_ASSOC), 'member_id'));

        if ($matches === []) {
            return [];
        }

        // Znajdź maksymalną rundę = finał.
        $maxRound = 0;
        foreach ($matches as $m) {
            $maxRound = max($maxRound, (int)$m['round']);
        }

        // Dla każdego członka: w której najwyższej rundzie wystąpił + czy wygrał finał.
        $place = []; // member_id => place
        $champion = null;
        foreach ($matches as $m) {
            $round = (int)$m['round'];
            foreach (['player1_id', 'player2_id'] as $col) {
                $pid = $m[$col] !== null ? (int)$m[$col] : 0;
                if ($pid <= 0) continue;
                if (!isset($place[$pid]) || $place[$pid] < $round) {
                    $place[$pid] = $round;
                }
            }
            if ($round === $maxRound && !empty($m['winner_id'])) {
                $champion = (int)$m['winner_id'];
            }
        }

        // Zamień najwyższą rundę na finishPlace.
        // Najwyższa runda (finał) → przegrany = 2, wygrany = 1.
        // Półfinał → 3-4, itd. Mapujemy: place = 2^(maxRound - lastRound) za przegranego
        // i 2^(maxRound-lastRound)/2 dla wygranego tej rundy (nieidealne, ale generyczne).
        $out = [];
        foreach ($memberIds as $mid) {
            $lastRound = $place[$mid] ?? 0;
            if ($lastRound === 0) {
                continue;
            }
            if ($mid === $champion) {
                $finish = 1;
            } else {
                // przegrany w rundzie $lastRound — pozycja zaczyna się od 2 dla finalisty,
                // 3 dla półfinalistów, 5 dla ćwierćfinalistów (2^(diff)+1).
                $diff = $maxRound - $lastRound;
                $finish = ($diff === 0) ? 2 : (int)pow(2, $diff) + 1;
            }
            $out[] = ['member_id' => $mid, 'finishPlace' => $finish];
        }
        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function participantsFromEvent(PDO $db, int $eventId): array
    {
        $stmt = $db->prepare(
            "SELECT member_id, score, place, extra
             FROM `event_results`
             WHERE event_id = ? AND member_id IS NOT NULL"
        );
        $stmt->execute([$eventId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $extra = [];
            if (!empty($r['extra'])) {
                $decoded = json_decode((string)$r['extra'], true);
                if (is_array($decoded)) $extra = $decoded;
            }
            $out[] = [
                'member_id'   => (int)$r['member_id'],
                'finishPlace' => $r['place'] !== null ? (int)$r['place'] : null,
                'score'       => $r['score'] !== null ? (float)$r['score'] : null,
                'time'        => isset($extra['time']) ? (float)$extra['time'] : null,
                'result'      => $extra['result'] ?? null,
            ];
        }
        return $out;
    }

    private static function columnExists(PDO $db, string $table, string $column): bool
    {
        try {
            $stmt = $db->prepare(
                "SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
            );
            $stmt->execute([$table, $column]);
            return (bool)$stmt->fetchColumn();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $acc
     * @param array<int, array<string, mixed>> $next
     * @return array<int, array<string, mixed>>
     */
    private static function mergeSummary(array $acc, array $next): array
    {
        foreach ($next as $row) {
            $mid = (int)($row['member_id'] ?? 0);
            if ($mid <= 0) continue;
            $acc[$mid] = $row; // overwrite — final state after this step
        }
        return $acc;
    }
}
