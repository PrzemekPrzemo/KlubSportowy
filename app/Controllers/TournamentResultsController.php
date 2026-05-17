<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Feature;
use App\Helpers\LiveStream;
use App\Helpers\NotificationDispatcher;
use App\Helpers\Pdf\TournamentProtocolPdf;
use App\Helpers\PdfHelper;
use App\Helpers\Ranking\RankingEngine;
use App\Helpers\Session;
use App\Helpers\SportModuleLoader;
use App\Models\SportModel;
use App\Models\TournamentModel;
use PDO;

/**
 * Flow wpisywania wyników turnieju + auto-recalc rankingu.
 *
 * - GET  /tournaments/:id/results          → formularz dla sędziego (sport-aware)
 * - POST /tournaments/:id/results/save     → zapis + RankingEngine::recalculateForTournament
 * - GET  /admin/tournaments/pending        → dashboard sędziego (turnieje do uzupełnienia)
 * - GET  /tournaments/:id/protocol-pdf     → protokół PDF z wynikami
 */
class TournamentResultsController extends BaseController
{
    /** Próg dużego turnieju — powyżej tej liczby uczestników recalc oznaczamy jako "long-running". */
    private const ASYNC_THRESHOLD = 100;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad', 'trener', 'admin', 'sedzia']);
    }

    /**
     * GET /tournaments/:id/results
     */
    public function form(string $id): void
    {
        $tournamentId = (int)$id;
        $tournament   = $this->loadTournamentForClub($tournamentId);
        if ($tournament === null) {
            Session::flash('error', 'Turniej nie został znaleziony lub nie należy do tego klubu.');
            $this->redirect('tournaments');
        }

        $participants = $this->loadParticipants($tournamentId);
        $matches      = $this->loadMatches($tournamentId);
        $sport        = (new SportModel())->findByKey((string)$tournament['sport_key']) ?? [];
        $sportType    = $this->detectSportType($tournament['sport_key'], $sport);
        $sports       = SportModuleLoader::load();

        $this->render('tournaments/results_form', [
            'title'            => 'Wyniki turnieju: ' . $tournament['name'],
            'tournament'       => $tournament,
            'participants'     => $participants,
            'matches'          => $matches,
            'sport'            => $sport,
            'sportType'        => $sportType,
            'sports'           => $sports,
            'asyncThreshold'   => self::ASYNC_THRESHOLD,
        ]);
    }

    /**
     * POST /tournaments/:id/results/save
     *
     * Akceptowane payloady (zależnie od sportType):
     *   - 'time' sports (best_time): participants[member_id][time_ms], participants[member_id][place]
     *   - 'fight'/'mind' (elo, bracket): matches[match_id][winner_id], matches[match_id][score1/2]
     *   - 'team' (league_points): matches[match_id][score1/2], matches[match_id][winner_id]
     *   - dodatkowo participants[member_id][score] — generyczny score per uczestnik (UPSERT do tournament_participants.score jeśli istnieje)
     */
    public function save(string $id): void
    {
        Csrf::verify();
        $tournamentId = (int)$id;
        $tournament   = $this->loadTournamentForClub($tournamentId);
        if ($tournament === null) {
            Session::flash('error', 'Turniej nie istnieje.');
            $this->redirect('tournaments');
        }

        $db = Database::pdo();
        $savedCount = 0;

        // 1. Aktualizacje per-match (drabinki / sporty walki / drużynowe).
        $postMatches = $_POST['matches'] ?? [];
        if (is_array($postMatches)) {
            foreach ($postMatches as $matchId => $row) {
                $matchId = (int)$matchId;
                if ($matchId <= 0 || !is_array($row)) continue;

                // Walidacja: mecz musi należeć do tego turnieju.
                $check = $db->prepare("SELECT id FROM tournament_matches WHERE id = ? AND tournament_id = ? LIMIT 1");
                $check->execute([$matchId, $tournamentId]);
                if (!$check->fetchColumn()) continue;

                $score1   = isset($row['score1']) ? trim((string)$row['score1']) : '';
                $score2   = isset($row['score2']) ? trim((string)$row['score2']) : '';
                $winnerId = isset($row['winner_id']) && $row['winner_id'] !== '' ? (int)$row['winner_id'] : null;

                // Pomijaj puste wpisy (idempotentność).
                if ($score1 === '' && $score2 === '' && $winnerId === null) continue;

                $upd = $db->prepare(
                    "UPDATE tournament_matches
                        SET winner_id = ?, score1 = ?, score2 = ?
                      WHERE id = ?"
                );
                $upd->execute([
                    $winnerId,
                    $score1 !== '' ? $score1 : null,
                    $score2 !== '' ? $score2 : null,
                    $matchId,
                ]);

                // Loser = eliminated.
                if ($winnerId !== null) {
                    $m = $db->prepare("SELECT player1_id, player2_id FROM tournament_matches WHERE id = ?");
                    $m->execute([$matchId]);
                    $row2 = $m->fetch(PDO::FETCH_ASSOC);
                    if ($row2) {
                        $loserId = ((int)$row2['player1_id'] === $winnerId)
                            ? $row2['player2_id']
                            : $row2['player1_id'];
                        if ($loserId) {
                            $db->prepare(
                                "UPDATE tournament_participants
                                    SET eliminated = 1
                                  WHERE tournament_id = ? AND member_id = ?"
                            )->execute([$tournamentId, (int)$loserId]);
                        }
                    }
                }
                $savedCount++;
            }
        }

        // 2. Aktualizacje per-participant (sporty czasowe / generic place+score).
        $postParticipants = $_POST['participants'] ?? [];
        if (is_array($postParticipants)) {
            $hasPlace = $this->columnExists($db, 'tournament_participants', 'place');
            $hasScore = $this->columnExists($db, 'tournament_participants', 'score');
            $hasTime  = $this->columnExists($db, 'tournament_participants', 'time_ms');

            foreach ($postParticipants as $memberId => $row) {
                $memberId = (int)$memberId;
                if ($memberId <= 0 || !is_array($row)) continue;

                $place  = isset($row['place'])   && $row['place']   !== '' ? (int)$row['place'] : null;
                $score  = isset($row['score'])   && $row['score']   !== '' ? (float)$row['score'] : null;
                $timeMs = isset($row['time_ms']) && $row['time_ms'] !== '' ? (int)$row['time_ms'] : null;

                if ($place === null && $score === null && $timeMs === null) continue;

                $sets   = [];
                $params = [];
                if ($hasPlace) { $sets[] = "place = ?";   $params[] = $place; }
                if ($hasScore) { $sets[] = "score = ?";   $params[] = $score; }
                if ($hasTime)  { $sets[] = "time_ms = ?"; $params[] = $timeMs; }

                if ($sets !== []) {
                    $params[] = $tournamentId;
                    $params[] = $memberId;
                    $sql = "UPDATE tournament_participants SET " . implode(', ', $sets)
                         . " WHERE tournament_id = ? AND member_id = ?";
                    $db->prepare($sql)->execute($params);
                }
                $savedCount++;
            }
        }

        // 3. Optional: mark tournament finished.
        $justMarkedFinished = false;
        if (!empty($_POST['mark_finished'])) {
            $db->prepare("UPDATE tournaments SET status = 'finished' WHERE id = ?")
               ->execute([$tournamentId]);
            $justMarkedFinished = true;
        }

        // 3a. Auto-publish PDF protokol (best-effort) gdy turniej zostal
        // wlasnie zakonczony. Failure nie blokuje glownego zapisu wynikow.
        if ($justMarkedFinished) {
            try {
                (new \App\Helpers\Tournaments\ProtocolPublisher())->publish($tournamentId);
            } catch (\Throwable $e) {
                error_log("Protocol publish failed for tournament {$tournamentId}: " . $e->getMessage());
            }
        }

        // 4. Recalc ranking (synchronous for now — note threshold).
        $participantCount = (int)$db->query(
            "SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = " . $tournamentId
        )->fetchColumn();

        try {
            $result = RankingEngine::recalculateForTournament($tournamentId);
            $recalculatedFor = count($result);
        } catch (\Throwable $e) {
            error_log("RankingEngine failed for tournament {$tournamentId}: " . $e->getMessage());
            $recalculatedFor = 0;
        }

        // Webhook: tournament.finished — tylko jesli oznaczony jako finished.
        if (!empty($_POST['mark_finished'])) {
            try {
                $clubId = \App\Helpers\ClubContext::current();
                if ($clubId !== null) {
                    \App\Helpers\Webhooks\WebhookDispatcher::publish((int)$clubId, 'tournament.finished', [
                        'tournament_id'      => $tournamentId,
                        'tournament_name'    => $tournament['name'] ?? null,
                        'participants_count' => $participantCount,
                        'recalculated_for'   => $recalculatedFor,
                    ]);
                }
            } catch (\Throwable $e) {
                error_log('Webhook publish tournament.finished failed: ' . $e->getMessage());
            }
        }

        // 5. Live push (opcjonalnie).
        $this->maybePushLive($tournament, $postParticipants);

        // 6. Notifications (jeśli finished).
        if (!empty($_POST['mark_finished'])) {
            $this->maybeNotifyParticipants($tournament);
        }

        $msg = "Zapisano {$savedCount} wyników. Ranking przeliczony dla {$recalculatedFor} członków.";
        if ($participantCount > self::ASYNC_THRESHOLD) {
            $msg .= " (Duży turniej — {$participantCount} uczestników; rozważ async w przyszłości.)";
        }
        Session::flash('success', $msg);
        $this->redirect('tournaments/' . $tournamentId . '/results');
    }

    /**
     * GET /admin/tournaments/pending
     * Lista turniejów w toku / zakończonych do uzupełnienia wyników.
     */
    public function pending(): void
    {
        $db = Database::pdo();
        $clubId = ClubContext::current();

        // Turnieje 'active'/'finished' tego klubu — z liczbą uczestników bez wyniku (eliminated=0 i brak wpisu)
        // i z liczbą meczy bez winner_id.
        $stmt = $db->prepare(
            "SELECT t.*,
                    COUNT(DISTINCT tp.id) AS participants_total,
                    SUM(CASE WHEN tm.id IS NOT NULL AND tm.winner_id IS NULL
                             AND tm.player1_id IS NOT NULL AND tm.player2_id IS NOT NULL THEN 1 ELSE 0 END) AS open_matches
               FROM tournaments t
          LEFT JOIN tournament_participants tp ON tp.tournament_id = t.id
          LEFT JOIN tournament_matches tm     ON tm.tournament_id = t.id
              WHERE t.club_id = ?
                AND t.status IN ('active','finished')
           GROUP BY t.id
           ORDER BY t.date_start DESC, t.id DESC"
        );
        $stmt->execute([$clubId]);
        $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sports = SportModuleLoader::load();

        $this->render('admin/tournaments/pending', [
            'title'       => 'Turnieje oczekujące na wpisanie wyników',
            'tournaments' => $tournaments,
            'sports'      => $sports,
        ]);
    }

    /**
     * GET /tournaments/:id/protocol-pdf
     */
    public function protocolPdf(string $id): void
    {
        $tournamentId = (int)$id;
        $tournament   = $this->loadTournamentForClub($tournamentId);
        if ($tournament === null) {
            Session::flash('error', 'Turniej nie istnieje.');
            $this->redirect('tournaments');
        }

        $participants = $this->loadParticipants($tournamentId);
        $matches      = $this->loadMatches($tournamentId);
        $sport        = (new SportModel())->findByKey((string)$tournament['sport_key']) ?? [];

        $clubId  = (int)$tournament['club_id'];
        $header  = PdfHelper::getClubHeader($clubId);

        $html = TournamentProtocolPdf::generate([
            'tournament'    => $tournament,
            'participants'  => $participants,
            'matches'       => $matches,
            'sport'         => $sport,
            'club_header'   => $header,
            'system_footer' => PdfHelper::getSystemFooter(),
        ]);

        $filename = 'protokol-turnieju-' . $tournamentId . '.pdf';
        PdfHelper::renderToPdf($html, $filename, 'P');
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Internals
    // ─────────────────────────────────────────────────────────────────────

    private function loadTournamentForClub(int $id): ?array
    {
        $db = Database::pdo();
        $clubId = ClubContext::current();
        $stmt = $db->prepare("SELECT * FROM tournaments WHERE id = ? AND club_id = ? LIMIT 1");
        $stmt->execute([$id, $clubId]);
        $t = $stmt->fetch(PDO::FETCH_ASSOC);
        return $t ?: null;
    }

    private function loadParticipants(int $tournamentId): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT tp.*, m.first_name, m.last_name, m.member_number
               FROM tournament_participants tp
               JOIN members m ON m.id = tp.member_id
              WHERE tp.tournament_id = ?
           ORDER BY tp.seed ASC, m.last_name ASC"
        );
        $stmt->execute([$tournamentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function loadMatches(int $tournamentId): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT tm.*,
                    CONCAT(m1.last_name, ' ', m1.first_name) AS player1_name,
                    CONCAT(m2.last_name, ' ', m2.first_name) AS player2_name
               FROM tournament_matches tm
          LEFT JOIN members m1 ON m1.id = tm.player1_id
          LEFT JOIN members m2 ON m2.id = tm.player2_id
              WHERE tm.tournament_id = ?
           ORDER BY tm.round ASC, tm.match_number ASC"
        );
        $stmt->execute([$tournamentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Wykrywa typ sportu na podstawie sport_key + sports.team_sport.
     * Zwraca jedną z: 'team' | 'fight' | 'time' | 'mind' | 'generic'.
     */
    private function detectSportType(string $sportKey, array $sportRow): string
    {
        $key = strtolower(trim($sportKey));
        $teamSports  = ['football', 'basketball', 'volleyball', 'handball', 'futsal', 'rugby', 'baseball'];
        $fightSports = ['judo', 'boxing', 'boks', 'mma', 'karate', 'taekwondo', 'wrestling', 'bjj'];
        $timeSports  = ['athletics', 'swimming', 'rollerskating', 'cycling', 'rowing', 'shooting', 'skiing'];
        $mindSports  = ['chess', 'bridge', 'checkers'];

        if (in_array($key, $teamSports, true))  return 'team';
        if (in_array($key, $fightSports, true)) return 'fight';
        if (in_array($key, $timeSports, true))  return 'time';
        if (in_array($key, $mindSports, true))  return 'mind';

        // Fallback po team_sport: drużynowe → team, indywidualne → generic.
        if (!empty($sportRow['team_sport'])) return 'team';
        return 'generic';
    }

    private function columnExists(PDO $db, string $table, string $column): bool
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
     * Publish update na kanał Live SSE (jeśli klub ma feature live_score i kanał istnieje).
     */
    private function maybePushLive(array $tournament, array $postParticipants): void
    {
        try {
            if (!Feature::enabled('live_score')) return;
            $channel = $tournament['live_channel'] ?? null;
            if (!$channel) {
                // konwencja: kanał = "tournament:{id}"
                $channel = 'tournament:' . (int)$tournament['id'];
            }
            $payload = [
                'tournament_id' => (int)$tournament['id'],
                'updates'       => array_keys($postParticipants ?: []),
                'ts'            => time(),
            ];
            LiveStream::publish((string)$channel, 'result_update', $payload);
        } catch (\Throwable) {
            // Brak kanału / feature — ciche pominięcie.
        }
    }

    /**
     * Wysyła powiadomienie do uczestników o zakończeniu turnieju.
     * Jezeli protokol ma wlaczony public share, dodatkowo wysyla email
     * z linkiem do PDF (template: tournament_finished_protocol).
     */
    private function maybeNotifyParticipants(array $tournament): void
    {
        $clubId = (int)$tournament['club_id'];
        $tournamentId = (int)$tournament['id'];

        try {
            if (class_exists(NotificationDispatcher::class)) {
                NotificationDispatcher::notifyClubMembers($clubId, 'tournament_finished', [
                    'tournament_name' => $tournament['name'],
                    'tournament_id'   => $tournamentId,
                ]);
            }
        } catch (\Throwable) {
            // ignore
        }

        // Email z linkiem do PDF protokolu — tylko gdy public share jest wlaczony.
        try {
            $protocol = (new \App\Models\TournamentProtocolModel())->latestForTournament($tournamentId);
            if (!$protocol || (int)($protocol['public_share_enabled'] ?? 0) !== 1) {
                return;
            }
            $slug = $protocol['public_share_slug'] ?? null;
            if (!$slug) return;

            $shareUrl = function_exists('url') ? url('protocols/' . $slug) : '/protocols/' . $slug;

            // Pobierz email uczestnikow.
            $stmt = Database::pdo()->prepare(
                "SELECT m.first_name, m.email
                   FROM tournament_participants tp
                   JOIN members m ON m.id = tp.member_id
                  WHERE tp.tournament_id = ? AND m.email IS NOT NULL AND m.email <> ''"
            );
            $stmt->execute([$tournamentId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
                try {
                    \App\Helpers\EmailService::queueFromTemplate(
                        $clubId,
                        'tournament_finished_protocol',
                        (string)$p['email'],
                        [
                            'member.first_name' => (string)($p['first_name'] ?? ''),
                            'tournament.name'   => (string)($tournament['name'] ?? ''),
                            'tournament.date'   => (string)($tournament['date_start'] ?? ''),
                            'share_url'         => $shareUrl,
                        ]
                    );
                } catch (\Throwable) {
                    // skip individual failure
                }
            }
        } catch (\Throwable $e) {
            error_log('tournament_finished_protocol notify failed: ' . $e->getMessage());
        }
    }
}
