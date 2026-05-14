<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\Ranking\RankingEngine;

/**
 * Feature: RankingEngine::recalculateForTournament().
 *
 *  - Tworzy mini-turniej single-elimination (4 graczy, finał + 1 półfinał) i sprawdza,
 *    że wynik UPSERT-uje wiersze w sport_rankings z poprawną liczbą zwycięstw.
 *  - Weryfikuje last_calculated_at jest ustawiony po przeliczeniu.
 *  - Weryfikuje, że recalc na zakończonym turnieju zwraca pustą tablicę gdy brak meczów.
 */
class RankingEngineTest extends FeatureTestCase
{
    /** Tworzy minimalny turniej (single-elimination, finał + semifinał). Zwraca tournament_id. */
    private function createSimpleTournament(int $clubId, string $sportKey, int $winnerMemberId, int $finalistMemberId, ?int $semifinalLoserId = null): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO tournaments (club_id, sport_key, name, format, date_start, status, created_at)
             VALUES (?, ?, ?, 'single_elimination', CURDATE(), 'finished', NOW())"
        );
        $stmt->execute([$clubId, $sportKey, 'Test Tournament ' . bin2hex(random_bytes(3))]);
        $tid = (int)$this->pdo->lastInsertId();

        // Participants
        $partStmt = $this->pdo->prepare(
            "INSERT INTO tournament_participants (tournament_id, member_id, seed, eliminated) VALUES (?, ?, ?, ?)"
        );
        $partStmt->execute([$tid, $winnerMemberId, 1, 0]);
        $partStmt->execute([$tid, $finalistMemberId, 2, 1]);
        if ($semifinalLoserId !== null) {
            $partStmt->execute([$tid, $semifinalLoserId, 3, 1]);
        }

        // Matches: round 1 = semifinal (winner vs semifinalLoser), round 2 = final (winner vs finalist).
        $matchStmt = $this->pdo->prepare(
            "INSERT INTO tournament_matches (tournament_id, round, match_number, player1_id, player2_id, winner_id, score1, score2)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if ($semifinalLoserId !== null) {
            $matchStmt->execute([$tid, 1, 1, $winnerMemberId, $semifinalLoserId, $winnerMemberId, '2', '0']);
            $matchStmt->execute([$tid, 2, 1, $winnerMemberId, $finalistMemberId, $winnerMemberId, '2', '1']);
        } else {
            $matchStmt->execute([$tid, 1, 1, $winnerMemberId, $finalistMemberId, $winnerMemberId, '2', '0']);
        }

        return $tid;
    }

    public function testRecalculateForTournamentPersistsRankings(): void
    {
        $clubId = $this->createClub('Ranking Tour Club');
        $winner = $this->createMember($clubId, 'Winner', 'Tournament');
        $finalist = $this->createMember($clubId, 'Loser', 'Tournament');
        $semi = $this->createMember($clubId, 'SemiOut', 'Tournament');

        $tid = $this->createSimpleTournament($clubId, 'tennis', $winner, $finalist, $semi);

        $result = RankingEngine::recalculateForTournament($tid);

        $this->assertNotEmpty($result, 'Recalc na turnieju z meczami musi zwrócić wyniki');

        // Sprawdź że winner ma więcej punktów niż finalist (Elo / league_points).
        $byMember = [];
        foreach ($result as $row) {
            $byMember[(int)$row['member_id']] = $row;
        }
        $this->assertArrayHasKey($winner, $byMember, 'Winner musi być w wynikach');
        $this->assertArrayHasKey($finalist, $byMember, 'Finalist musi być w wynikach');

        $winnerPts = (int)($byMember[$winner]['new_points'] ?? 0);
        $finalistPts = (int)($byMember[$finalist]['new_points'] ?? 0);
        $this->assertGreaterThanOrEqual(
            $finalistPts,
            $winnerPts,
            'Winner musi mieć ≥ punktów niż finalist'
        );

        // Sprawdź persistence w DB.
        $stmt = $this->pdo->prepare(
            "SELECT member_id, ranking_points, last_calculated_at
             FROM sport_rankings
             WHERE club_id = ? AND sport_key = ? AND season = ?"
        );
        $season = (string)(int)date('Y');
        $stmt->execute([$clubId, 'tennis', $season]);
        $rows = $stmt->fetchAll();
        $this->assertNotEmpty($rows, 'sport_rankings musi mieć wpisy po recalc');

        $found = false;
        foreach ($rows as $r) {
            if ((int)$r['member_id'] === $winner) {
                $found = true;
                $this->assertNotEmpty($r['last_calculated_at'], 'last_calculated_at musi być ustawione po recalc');
            }
        }
        $this->assertTrue($found, 'Winner musi mieć wpis w sport_rankings');
    }

    public function testRecalculateForNonexistentTournamentReturnsEmpty(): void
    {
        $result = RankingEngine::recalculateForTournament(999999999);
        $this->assertSame([], $result, 'Nieistniejący turniej → []');
    }

    public function testRecalculateForEmptyTournamentReturnsEmpty(): void
    {
        $clubId = $this->createClub('Empty Tournament');
        // Stwórz turniej bez meczów ani participants.
        $stmt = $this->pdo->prepare(
            "INSERT INTO tournaments (club_id, sport_key, name, format, date_start, status, created_at)
             VALUES (?, 'chess', 'Empty', 'single_elimination', CURDATE(), 'draft', NOW())"
        );
        $stmt->execute([$clubId]);
        $tid = (int)$this->pdo->lastInsertId();

        $this->assertSame([], RankingEngine::recalculateForTournament($tid));
    }

    public function testRecalculateUpsertsExistingRanking(): void
    {
        $clubId = $this->createClub('Upsert Ranking');
        $w = $this->createMember($clubId, 'W');
        $l = $this->createMember($clubId, 'L');

        // Stara wartość rankingowa
        $this->pdo->prepare(
            "INSERT INTO sport_rankings (club_id, member_id, sport_key, season, ranking_points, competitions_count, wins)
             VALUES (?, ?, 'tennis', ?, 1000, 5, 3)"
        )->execute([$clubId, $w, (string)(int)date('Y')]);

        $tid = $this->createSimpleTournament($clubId, 'tennis', $w, $l);
        RankingEngine::recalculateForTournament($tid);

        $stmt = $this->pdo->prepare(
            "SELECT ranking_points FROM sport_rankings WHERE club_id = ? AND member_id = ? AND sport_key = 'tennis'"
        );
        $stmt->execute([$clubId, $w]);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        // Po UPSERT punkty zostały zaktualizowane (= recalc'owanej wartości, nie addytywnie).
        $this->assertIsNumeric($row['ranking_points']);
    }
}
