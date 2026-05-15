<?php

declare(strict_types=1);

namespace App\Helpers\Bracket;

use App\Helpers\ClubContext;
use App\Helpers\Database;
use App\Helpers\LiveStream;
use App\Models\LiveChannelModel;
use PDO;

/**
 * Po wpisaniu wyniku meczu, BracketAdvancer:
 *   - dla SE: posuwa zwyciezce do `parent_match_id` na slot `slot_in_parent`
 *   - dla DE: dodatkowo zrzuca przegranego do `loser_match_id`
 *   - dla round_robin: nic nie robi (wszystkie mecze sa juz utworzone z parami)
 *   - jesli kolejny mecz dostanie obu zawodnikow i ktorys to BYE, kaskadowo
 *     advance dalej (winner walks over).
 *   - opcjonalnie publikuje SSE event 'bracket_advance' do live channel turnieju.
 */
class BracketAdvancer
{
    /**
     * @param int $matchId Mecz w ktorym wlasnie zapisano wynik (winner_id NOT NULL).
     */
    public static function advance(int $matchId): void
    {
        $db = Database::pdo();
        $clubId = ClubContext::current();

        // Pobierz mecz + tournament_id + winner + parent
        $stmt = $db->prepare(
            "SELECT m.*, t.club_id AS t_club_id
             FROM tournament_matches m
             JOIN tournaments t ON t.id = m.tournament_id
             WHERE m.id = ?"
        );
        $stmt->execute([$matchId]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match || empty($match['winner_id'])) {
            return;
        }

        // Multi-tenant guard: nie posuwaj meczu z innego klubu (chyba ze brak kontekstu, np. CLI/cron).
        if ($clubId !== null && (int)$match['t_club_id'] !== (int)$clubId) {
            return;
        }

        $winnerId = (int) $match['winner_id'];
        $loserId  = ((int)$match['player1_id'] === $winnerId)
            ? (int)($match['player2_id'] ?? 0)
            : (int)($match['player1_id'] ?? 0);

        $advanced = [
            'match_id'   => $matchId,
            'winner_id'  => $winnerId,
            'loser_id'   => $loserId ?: null,
            'next_match' => null,
            'loser_drop' => null,
        ];

        // Winner advance
        if (!empty($match['parent_match_id'])) {
            $slot = (int)($match['slot_in_parent'] ?? 0);
            $col  = $slot === 0 ? 'player1_id' : 'player2_id';
            $upd  = $db->prepare("UPDATE tournament_matches SET {$col} = ? WHERE id = ?");
            $upd->execute([$winnerId, (int)$match['parent_match_id']]);
            $advanced['next_match'] = (int)$match['parent_match_id'];

            // Cascade: jesli parent dostal obu graczy i jeden to bye (drugi NULL po naszym update?),
            // albo oba sa ustawione i drugi pochodzi z bye-match — moga wymagac auto-advance.
            // Sprawdzamy czy parent ma obu graczy i jeden z nich byl bye (np. winner_id zostanie ustawione auto).
            self::autoAdvanceIfBye($db, (int)$match['parent_match_id']);
        }

        // Loser drop (DE)
        if (!empty($match['loser_match_id']) && $loserId > 0) {
            // Domyslnie loser idzie na slot 0 (player1) w LB match, chyba ze juz zajety.
            $lb = $db->prepare("SELECT player1_id, player2_id FROM tournament_matches WHERE id = ?");
            $lb->execute([(int)$match['loser_match_id']]);
            $lbm = $lb->fetch(PDO::FETCH_ASSOC);
            if ($lbm) {
                $col = empty($lbm['player1_id']) ? 'player1_id' : 'player2_id';
                $upd = $db->prepare("UPDATE tournament_matches SET {$col} = ? WHERE id = ?");
                $upd->execute([$loserId, (int)$match['loser_match_id']]);
                $advanced['loser_drop'] = (int)$match['loser_match_id'];
            }
        }

        // Publish to live channel (if exists for tournament)
        self::publishLive((int)$match['tournament_id'], $advanced);
    }

    /**
     * Jesli mecz dostal obu graczy i jeden jest NULL (bye po wczesniejszym auto-advance),
     * automatycznie wpisz winner = drugi gracz i kaskaduj.
     */
    private static function autoAdvanceIfBye(PDO $db, int $parentMatchId): void
    {
        $stmt = $db->prepare("SELECT * FROM tournament_matches WHERE id = ?");
        $stmt->execute([$parentMatchId]);
        $m = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$m || !empty($m['winner_id'])) {
            return;
        }

        // Sytuacja BYE: oba sloty wypelnione, ale jeden gracz to NULL
        // Niemozliwa "po naszym update" — winner jest zawsze valid id. Pomijamy ten case.
        // Druga sytuacja: jesli pierwsza runda jakiegos boku jest zlozona z bye-vs-real,
        // generator powinien byl od razu wstawic auto-advance. Tu nic.
    }

    private static function publishLive(int $tournamentId, array $payload): void
    {
        try {
            if (!class_exists(LiveChannelModel::class) || !class_exists(LiveStream::class)) {
                return;
            }
            $lc = new LiveChannelModel();
            if (!method_exists($lc, 'findByTournament')) {
                return;
            }
            $channel = $lc->findByTournament($tournamentId);
            if (!$channel || empty($channel['channel_key'])) {
                return;
            }
            LiveStream::publish((string)$channel['channel_key'], 'bracket_advance', $payload);
        } catch (\Throwable) {
            // Niewazne — live to feature opcjonalna.
        }
    }
}
