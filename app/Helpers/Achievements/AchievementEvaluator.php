<?php

declare(strict_types=1);

namespace App\Helpers\Achievements;

use App\Helpers\Database;
use App\Helpers\EmailService;
use App\Helpers\PushService;
use PDO;
use Throwable;

/**
 * AchievementEvaluator — silnik gamification.
 *
 * Sprawdza progres zawodnika wzgledem aktywnych odznak (global + per-klub
 * custom) i przyznaje nowe. Idempotentny — UNIQUE KEY w member_achievements
 * zapobiega duplikatom.
 *
 * Wywolywany trigger-style (po treningu / wyniku turnieju / rejestracji)
 * oraz batch przez cli/evaluate_achievements.php (nightly).
 *
 * Konwencja criteria JSON:
 *   { "type": "<criterion_type>", ...params }
 *
 * Wspierane typy:
 *   - trainings_count           {count: N}      -> liczba treningow ze statusem 'obecny'
 *   - tournament_played         {}              -> >=1 turniej grany
 *   - tournament_place          {place: N}      -> kiedykolwiek zajal miejsce N
 *   - tournament_top            {n: N}          -> top N w turnieju z >N uczestnikami
 *   - tournaments_played_count  {count: N}      -> >=N turniejow
 *   - season_wins               {count: N}      -> >=N zwyciestw w roku biezacym
 *   - membership_years          {years: N}      -> >=N lat w klubie (od join_date)
 *   - perfect_month             {}              -> jakikolwiek miesiac z 100% obecnoscia (min 4 treningi)
 *   - training_streak           {count: N}      -> N obecnych z rzedu (bez nieobecnego)
 *   - referrals_count           {count: N}      -> >=N poleconych czlonkow
 *   - team_match_won            {}              -> wygrana w meczu druzynowym (turniej zespolowy)
 *   - belt_promotions_count     {count: N}      -> >=N promocji pasow
 *   - profile_complete          {}              -> wypelniony email + telefon + adres
 */
final class AchievementEvaluator
{
    /**
     * Sprawdza wszystkie aktywne achievements dla danego czlonka i
     * przyznaje te, ktore zostaly spelnione (a nie sa juz zdobyte).
     *
     * @param int         $memberId    ID czlonka
     * @param string|null $triggerType opcjonalny filtr po category (np. 'tournament')
     * @return array<int, array<string, mixed>> Lista nowo przyznanych odznak
     */
    public static function evaluateForMember(int $memberId, ?string $triggerType = null): array
    {
        $db = Database::pdo();

        $member = self::loadMember($db, $memberId);
        if ($member === null) {
            return [];
        }
        $clubId = (int)$member['club_id'];

        $catalog = self::activeCatalogForClub($db, $clubId, $triggerType);
        if ($catalog === []) {
            return [];
        }

        // Juz zdobyte — bedziemy pomijac.
        $earnedIds = self::earnedIds($db, $memberId);

        $newly = [];
        foreach ($catalog as $achievement) {
            $aid = (int)$achievement['id'];
            if (isset($earnedIds[$aid])) {
                continue;
            }
            $criteria = self::decodeCriteria($achievement['criteria'] ?? null);
            if ($criteria === null) {
                continue;
            }
            try {
                $passed = self::checkCriteria($memberId, $member, $criteria);
            } catch (Throwable $e) {
                error_log('AchievementEvaluator check failed for code=' . ($achievement['code'] ?? '?') . ': ' . $e->getMessage());
                continue;
            }
            if (!$passed) {
                continue;
            }
            $context = ['criteria' => $criteria, 'trigger' => $triggerType];
            if (self::award($clubId, $memberId, $aid, $context)) {
                $newly[] = $achievement;
                self::notify($clubId, $memberId, $achievement);
            }
        }

        return $newly;
    }

    /**
     * Bulk evaluation dla calego klubu — uzywane przez cron nightly.
     *
     * @return array{members_evaluated:int, awards_total:int}
     */
    public static function evaluateAllInClub(int $clubId): array
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT id FROM `members` WHERE club_id = ? AND status = 'aktywny'"
        );
        $stmt->execute([$clubId]);
        $ids = array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id'));

        $awards = 0;
        foreach ($ids as $mid) {
            $new = self::evaluateForMember($mid);
            $awards += count($new);
        }
        return [
            'members_evaluated' => count($ids),
            'awards_total'      => $awards,
        ];
    }

    // ============================================================
    // Per-criteria checkers
    // ============================================================

    /**
     * Dispatch criteria -> dedykowany checker.
     *
     * @param array<string, mixed> $member  wiersz members
     * @param array<string, mixed> $criteria zdekodowane criteria JSON
     */
    private static function checkCriteria(int $memberId, array $member, array $criteria): bool
    {
        $type = (string)($criteria['type'] ?? '');
        return match ($type) {
            'trainings_count'          => self::checkTrainingsCount($memberId, $criteria),
            'tournament_played'        => self::checkTournamentPlayed($memberId),
            'tournament_place'         => self::checkTournamentPlace($memberId, $criteria),
            'tournament_top'           => self::checkTournamentTop($memberId, $criteria),
            'tournaments_played_count' => self::checkTournamentsPlayedCount($memberId, $criteria),
            'season_wins'              => self::checkSeasonWins($memberId, $criteria),
            'membership_years'         => self::checkMembershipYears($member, $criteria),
            'perfect_month'            => self::checkPerfectMonth($memberId),
            'training_streak'          => self::checkTrainingStreak($memberId, $criteria),
            'referrals_count'          => self::checkReferralsCount($memberId, $criteria),
            'team_match_won'           => self::checkTeamMatchWon($memberId),
            'belt_promotions_count'    => self::checkBeltPromotionsCount($memberId, $criteria),
            'profile_complete'         => self::checkProfileComplete($member),
            default                    => false,
        };
    }

    private static function checkTrainingsCount(int $memberId, array $criteria): bool
    {
        $need = max(1, (int)($criteria['count'] ?? 1));
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM `training_attendees`
             WHERE member_id = ? AND status = 'obecny'"
        );
        $stmt->execute([$memberId]);
        return (int)$stmt->fetchColumn() >= $need;
    }

    private static function checkTournamentPlayed(int $memberId): bool
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM `tournament_participants` WHERE member_id = ?"
        );
        $stmt->execute([$memberId]);
        return (int)$stmt->fetchColumn() >= 1;
    }

    /**
     * Wykorzystuje finishPlace obliczony przez RankingEngine z bracketu
     * tournament_matches: champion = winner_id finalu.
     */
    private static function checkTournamentPlace(int $memberId, array $criteria): bool
    {
        $needPlace = max(1, (int)($criteria['place'] ?? 1));

        $db = Database::pdo();
        // Lista turniejow finished, w ktorych member uczestniczyl.
        $stmt = $db->prepare(
            "SELECT t.id
             FROM tournaments t
             JOIN tournament_participants tp ON tp.tournament_id = t.id
             WHERE tp.member_id = ? AND t.status = 'finished'"
        );
        $stmt->execute([$memberId]);
        $tIds = array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id'));
        if ($tIds === []) {
            return false;
        }

        foreach ($tIds as $tid) {
            $place = self::resolveTournamentPlace($tid, $memberId);
            if ($place !== null && $place === $needPlace) {
                return true;
            }
        }
        return false;
    }

    private static function checkTournamentTop(int $memberId, array $criteria): bool
    {
        $n = max(1, (int)($criteria['n'] ?? 10));

        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT t.id,
                    (SELECT COUNT(*) FROM tournament_participants WHERE tournament_id = t.id) AS players
             FROM tournaments t
             JOIN tournament_participants tp ON tp.tournament_id = t.id
             WHERE tp.member_id = ? AND t.status = 'finished'"
        );
        $stmt->execute([$memberId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $players = (int)$r['players'];
            if ($players < $n) {
                continue; // tylko turnieje z >=N uczestnikami
            }
            $place = self::resolveTournamentPlace((int)$r['id'], $memberId);
            if ($place !== null && $place <= $n) {
                return true;
            }
        }
        return false;
    }

    private static function checkTournamentsPlayedCount(int $memberId, array $criteria): bool
    {
        $need = max(1, (int)($criteria['count'] ?? 1));
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT tournament_id) FROM tournament_participants WHERE member_id = ?"
        );
        $stmt->execute([$memberId]);
        return (int)$stmt->fetchColumn() >= $need;
    }

    private static function checkSeasonWins(int $memberId, array $criteria): bool
    {
        $need = max(1, (int)($criteria['count'] ?? 1));
        $year = (int)date('Y');
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT t.id
             FROM tournaments t
             JOIN tournament_participants tp ON tp.tournament_id = t.id
             WHERE tp.member_id = ? AND t.status = 'finished'
               AND YEAR(t.date_start) = ?"
        );
        $stmt->execute([$memberId, $year]);
        $wins = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $place = self::resolveTournamentPlace((int)$r['id'], $memberId);
            if ($place === 1) {
                $wins++;
                if ($wins >= $need) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function checkMembershipYears(array $member, array $criteria): bool
    {
        $needYears = max(1, (int)($criteria['years'] ?? 1));
        $joinDate = $member['join_date'] ?? null;
        if (!$joinDate) {
            return false;
        }
        try {
            $join = new \DateTimeImmutable((string)$joinDate);
        } catch (Throwable) {
            return false;
        }
        $now  = new \DateTimeImmutable('now');
        $diff = $join->diff($now);
        return $diff->y >= $needYears;
    }

    /**
     * Czy istnieje JAKIKOLWIEK miesiac, w ktorym czlonek mial 100% obecnosci
     * (przy minimum 4 zapisanych treningach w tym miesiacu).
     */
    private static function checkPerfectMonth(int $memberId): bool
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT DATE_FORMAT(t.start_time, '%Y-%m') AS ym,
                    COUNT(*) AS total,
                    SUM(CASE WHEN ta.status = 'obecny' THEN 1 ELSE 0 END) AS present
             FROM training_attendees ta
             JOIN trainings t ON t.id = ta.training_id
             WHERE ta.member_id = ?
               AND ta.status IN ('obecny','nieobecny','spozniony')
             GROUP BY ym
             HAVING total >= 4 AND present = total"
        );
        $stmt->execute([$memberId]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * N obecnych z rzedu (po start_time treningu). Sprawdza po prostu czy
     * istnieje taki streak w historii (kiedykolwiek).
     */
    private static function checkTrainingStreak(int $memberId, array $criteria): bool
    {
        $need = max(2, (int)($criteria['count'] ?? 5));
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT ta.status
             FROM training_attendees ta
             JOIN trainings t ON t.id = ta.training_id
             WHERE ta.member_id = ?
               AND ta.status IN ('obecny','nieobecny')
             ORDER BY t.start_time ASC"
        );
        $stmt->execute([$memberId]);
        $streak = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $st) {
            if ($st === 'obecny') {
                $streak++;
                if ($streak >= $need) {
                    return true;
                }
            } else {
                $streak = 0;
            }
        }
        return false;
    }

    /**
     * Polecenia — best-effort: szuka kolumny referred_by w members.
     * Gdy kolumna nie istnieje, kryterium niespelnione (graceful).
     */
    private static function checkReferralsCount(int $memberId, array $criteria): bool
    {
        $need = max(1, (int)($criteria['count'] ?? 1));
        $db = Database::pdo();
        try {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM `members` WHERE `referred_by` = ?"
            );
            $stmt->execute([$memberId]);
            return (int)$stmt->fetchColumn() >= $need;
        } catch (Throwable) {
            // referred_by kolumna nie istnieje (jeszcze) — kryterium nieaktywne.
            return false;
        }
    }

    /**
     * Mecz druzynowy wygrany — heurystyka: jakikolwiek tournament_match,
     * w ktorym member jest player1/player2 i jest winnerem, w turnieju
     * format round_robin (interpretacja: turnieje druzynowe / ligowe).
     */
    private static function checkTeamMatchWon(int $memberId): bool
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT 1
             FROM tournament_matches m
             JOIN tournaments t ON t.id = m.tournament_id
             WHERE m.winner_id = ? AND t.format = 'round_robin'
             LIMIT 1"
        );
        $stmt->execute([$memberId]);
        return $stmt->fetchColumn() !== false;
    }

    private static function checkBeltPromotionsCount(int $memberId, array $criteria): bool
    {
        $need = max(1, (int)($criteria['count'] ?? 1));
        $db = Database::pdo();
        // belt_promotions / member_belts — sprobuj obu konwencji.
        foreach (['belt_promotions', 'member_belts'] as $table) {
            try {
                $stmt = $db->prepare(
                    "SELECT COUNT(*) FROM `{$table}` WHERE member_id = ?"
                );
                $stmt->execute([$memberId]);
                $cnt = (int)$stmt->fetchColumn();
                if ($cnt >= $need) {
                    return true;
                }
            } catch (Throwable) {
                continue;
            }
        }
        return false;
    }

    private static function checkProfileComplete(array $member): bool
    {
        return !empty($member['email'])
            && !empty($member['phone'])
            && !empty($member['address_city']);
    }

    // ============================================================
    // Helpers
    // ============================================================

    /**
     * Wyznacza miejsce zawodnika w turnieju na podstawie bracket.
     * Powiela logike RankingEngine::participantsFromBracket() w trybie
     * single-member, aby unikac sztywnej zaleznosci.
     */
    private static function resolveTournamentPlace(int $tournamentId, int $memberId): ?int
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT round, player1_id, player2_id, winner_id
             FROM tournament_matches
             WHERE tournament_id = ?
             ORDER BY round ASC, match_number ASC"
        );
        $stmt->execute([$tournamentId]);
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($matches === []) {
            return null;
        }
        $maxRound = 0;
        $champion = null;
        $lastRound = 0;
        $participated = false;
        foreach ($matches as $m) {
            $r = (int)$m['round'];
            $maxRound = max($maxRound, $r);
            $p1 = (int)($m['player1_id'] ?? 0);
            $p2 = (int)($m['player2_id'] ?? 0);
            if ($p1 === $memberId || $p2 === $memberId) {
                $participated = true;
                if ($r > $lastRound) {
                    $lastRound = $r;
                }
            }
        }
        if (!$participated) {
            return null;
        }
        // Champion = winner_id najwyzszej rundy.
        foreach ($matches as $m) {
            if ((int)$m['round'] === $maxRound && !empty($m['winner_id'])) {
                $champion = (int)$m['winner_id'];
            }
        }
        if ($champion === $memberId) {
            return 1;
        }
        $diff = $maxRound - $lastRound;
        return $diff === 0 ? 2 : ((int)pow(2, $diff) + 1);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function loadMember(PDO $db, int $memberId): ?array
    {
        $stmt = $db->prepare(
            "SELECT id, club_id, first_name, last_name, email, phone, address_city,
                    join_date, status
             FROM `members` WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$memberId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Aktywny katalog: global (club_id NULL) + custom danego klubu.
     * Jesli klub ma custom z takim samym kodem jak global -> override:
     * uzywamy wersji per-klub (UNIQUE KEY (club_id, code) na to pozwala).
     *
     * @return array<int, array<string, mixed>>
     */
    private static function activeCatalogForClub(PDO $db, int $clubId, ?string $categoryFilter): array
    {
        $sql = "SELECT * FROM `achievement_catalog`
                WHERE is_active = 1
                  AND (club_id IS NULL OR club_id = :club)";
        $params = ['club' => $clubId];
        if ($categoryFilter !== null && $categoryFilter !== '') {
            $sql .= " AND category = :cat";
            $params['cat'] = $categoryFilter;
        }
        $sql .= " ORDER BY sort_order ASC, id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Override: jesli istnieje per-klub achievement z tym samym code,
        // pomin global (member dostaje wersje custom).
        $byCode = [];
        foreach ($rows as $r) {
            $code = (string)$r['code'];
            // Custom wygrywa nad global.
            if (isset($byCode[$code]) && $byCode[$code]['club_id'] !== null) {
                continue;
            }
            $byCode[$code] = $r;
        }
        return array_values($byCode);
    }

    /**
     * @return array<int, bool> achievement_id => true
     */
    private static function earnedIds(PDO $db, int $memberId): array
    {
        $stmt = $db->prepare(
            "SELECT achievement_id FROM `member_achievements` WHERE member_id = ?"
        );
        $stmt->execute([$memberId]);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $aid) {
            $out[(int)$aid] = true;
        }
        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function decodeCriteria(mixed $json): ?array
    {
        if (!is_string($json) || $json === '') {
            return null;
        }
        try {
            $data = json_decode($json, true, 8, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }
        return is_array($data) ? $data : null;
    }

    /**
     * Wstawia rekord member_achievements (idempotentnie dzieki UNIQUE).
     * Zwraca true gdy faktycznie nowy rekord powstal.
     */
    private static function award(int $clubId, int $memberId, int $achievementId, array $context): bool
    {
        $db = Database::pdo();
        try {
            $stmt = $db->prepare(
                "INSERT IGNORE INTO `member_achievements`
                   (club_id, member_id, achievement_id, earned_at, context)
                 VALUES (?, ?, ?, NOW(), ?)"
            );
            $stmt->execute([
                $clubId,
                $memberId,
                $achievementId,
                json_encode($context, JSON_UNESCAPED_UNICODE),
            ]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            error_log('AchievementEvaluator award failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notification: in-app (member_notifications) + email + push (FCM).
     * Wszystkie best-effort, bledy nie blokuja przyznania.
     */
    private static function notify(int $clubId, int $memberId, array $achievement): void
    {
        $name = (string)($achievement['name'] ?? 'Odznaka');
        $icon = (string)($achievement['icon'] ?? '🏆');
        $title = "{$icon} Zdobyłeś odznakę: {$name}!";
        $body  = (string)($achievement['description'] ?? 'Brawo! Sprawdz swoje osiagniecia w portalu.');

        // 1. In-app notification.
        try {
            $db = Database::pdo();
            $stmt = $db->prepare(
                "INSERT INTO `member_notifications`
                   (club_id, member_id, type, title, body, link, created_at)
                 VALUES (?, ?, 'general', ?, ?, ?, NOW())"
            );
            $stmt->execute([$clubId, $memberId, $title, $body, '/portal/achievements']);
        } catch (Throwable $e) {
            error_log('Achievement in-app notif failed: ' . $e->getMessage());
        }

        // 2. Email (best-effort, tylko gdy member ma email).
        try {
            $db = Database::pdo();
            $stmt = $db->prepare("SELECT email, first_name, last_name FROM members WHERE id = ? LIMIT 1");
            $stmt->execute([$memberId]);
            $m = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($m && !empty($m['email'])) {
                $emailBody = "Witaj " . ($m['first_name'] ?? '') . ",\n\n"
                    . "Gratulacje! Zdobyles wlasnie nowa odznake:\n\n"
                    . "  {$icon}  {$name}\n"
                    . "  {$body}\n\n"
                    . "Zobacz wszystkie swoje osiagniecia w portalu zawodnika.\n";
                EmailService::queue(
                    $clubId,
                    (string)$m['email'],
                    "Nowa odznaka: {$name}",
                    $emailBody,
                    trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? ''))
                );
            }
        } catch (Throwable $e) {
            error_log('Achievement email failed: ' . $e->getMessage());
        }

        // 3. Push (FCM, jesli token zarejestrowany).
        try {
            if (class_exists(PushService::class) && method_exists(PushService::class, 'sendToMember')) {
                PushService::sendToMember(
                    $memberId,
                    $title,
                    $body,
                    [
                        'type' => 'achievement',
                        'link' => '/portal/achievements',
                        'achievement_code' => (string)($achievement['code'] ?? ''),
                    ]
                );
            }
        } catch (Throwable $e) {
            error_log('Achievement push failed: ' . $e->getMessage());
        }
    }
}
