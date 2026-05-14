<?php

namespace App\Helpers;

use App\Helpers\Cache;
use App\Helpers\ClubContext;
use App\Helpers\Database;

/**
 * Cross-sport stats aggregator.
 *
 * Buduje jednolity widok aktywnosci czlonka klubu po wszystkich
 * dyscyplinach (USP multi-sport). Agreguje dane z:
 *   - member_sports (lista sekcji)
 *   - trainings + training_attendees (frekwencja)
 *   - events + event_results (wyniki)
 *   - tournaments + tournament_participants (turnieje)
 *   - sport_rankings (rankingi sezonowe)
 *
 * Per-klub: zapytania filtrowane przez `ClubContext::current()`.
 *
 * Cache: result `forMember()` cache'owany TTL 5min przez `Cache` helper
 * (Redis/file). Klucz: `member_cross_stats:{$memberId}:club:{$clubId}`.
 * Dodatkowo request-scope memo dla powtarzajacych sie wywolan w jednym
 * requeście (np. dashboard + sidebar widget).
 */
class CrossSportStats
{
    /** @var array<string, array> request-scope memo */
    private static array $memo = [];

    /**
     * Zwraca summary aktywnosci czlonka po wszystkich sportach.
     *
     * @return array{
     *   sports: list<array{sport_key:string, sport_label:string, icon:?string, color:?string,
     *                      trainings_count:int, events_count:int, tournaments_count:int,
     *                      rankings: list<array{season:string, points:int, position:?int}>}>,
     *   totals: array{total_trainings:int, total_events:int, total_tournaments:int,
     *                 active_seasons:int, sports_count:int, total_wins:int},
     *   recent_activity: list<array{type:string, date:string, label:string,
     *                               sport_key:?string, sport_label:?string}>,
     *   highlights: list<array{label:string, value:string, sport_key:?string}>,
     *   monthly_chart: array{labels: list<string>, datasets: list<array>}
     * }
     */
    public static function forMember(int $memberId): array
    {
        $clubId = (int)(ClubContext::current() ?? 0);
        $memoKey = $memberId . ':' . $clubId;
        if (isset(self::$memo[$memoKey])) {
            return self::$memo[$memoKey];
        }

        $cacheKey = "member_cross_stats:{$memberId}:club:{$clubId}";
        if (class_exists(Cache::class)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                self::$memo[$memoKey] = $cached;
                return $cached;
            }
        }

        $db = Database::pdo();

        // 1. Lista sportow czlonka (per-klub)
        $sportsRows = self::fetchMemberSports($db, $memberId, $clubId);

        $sports = [];
        $totalTrainings = 0;
        $totalEvents = 0;
        $totalTournaments = 0;
        $totalWins = 0;
        $seasonSet = [];

        foreach ($sportsRows as $row) {
            $sportKey   = (string)$row['sport_key'];
            $sportLabel = (string)($row['sport_name'] ?? $sportKey);

            $trainingsCount = self::countTrainingsForSport($db, $memberId, $clubId, (int)$row['club_sport_id']);
            $eventsCount    = self::countEventsForSport($db, $memberId, $clubId, (int)$row['sport_id']);
            $tournamentsCount = self::countTournamentsForSport($db, $memberId, $clubId, $sportKey);
            $rankings       = self::rankingsForSport($db, $memberId, $clubId, $sportKey);

            foreach ($rankings as $r) {
                $seasonSet[$r['season']] = true;
                $totalWins += (int)($r['wins'] ?? 0);
            }

            $totalTrainings   += $trainingsCount;
            $totalEvents      += $eventsCount;
            $totalTournaments += $tournamentsCount;

            $sports[] = [
                'sport_key'         => $sportKey,
                'sport_label'       => $sportLabel,
                'icon'              => $row['icon']  ?? null,
                'color'             => $row['color'] ?? null,
                'trainings_count'   => $trainingsCount,
                'events_count'      => $eventsCount,
                'tournaments_count' => $tournamentsCount,
                'rankings'          => $rankings,
            ];
        }

        // 2. Recent activity (UNION trainings/events/tournaments)
        $recent = self::recentActivityForMember($db, $memberId, $clubId);

        // 3. Highlights — best rankings, win streaks, podium results
        $highlights = self::computeHighlights($db, $memberId, $clubId, $sports);

        // 4. Monthly chart — 12m, line per sport
        $monthly = self::monthlyActivityChart($db, $memberId, $clubId, $sports);

        $result = [
            'sports' => $sports,
            'totals' => [
                'total_trainings'   => $totalTrainings,
                'total_events'      => $totalEvents,
                'total_tournaments' => $totalTournaments,
                'active_seasons'    => count($seasonSet),
                'sports_count'      => count($sports),
                'total_wins'        => $totalWins,
            ],
            'recent_activity' => $recent,
            'highlights'      => $highlights,
            'monthly_chart'   => $monthly,
        ];

        if (class_exists(Cache::class)) {
            Cache::set($cacheKey, $result, 300);
        }
        self::$memo[$memoKey] = $result;

        return $result;
    }

    /**
     * Top N najaktywniejszych czlonkow klubu cross-sport.
     *
     * @return list<array{member_id:int, first_name:string, last_name:string,
     *                    sports_count:int, total_trainings:int, total_events:int,
     *                    total_tournaments:int, total_activity:int}>
     */
    public static function topActiveForClub(int $limit = 10): array
    {
        $clubId = (int)(ClubContext::current() ?? 0);
        if ($clubId <= 0) {
            return [];
        }
        $limit = max(1, min(100, $limit));

        $db = Database::pdo();
        $sql = "
            SELECT m.id AS member_id, m.first_name, m.last_name,
                   COALESCE(sp.sports_count, 0)        AS sports_count,
                   COALESCE(tr.trainings, 0)           AS total_trainings,
                   COALESCE(ev.events, 0)              AS total_events,
                   COALESCE(tp.tournaments, 0)         AS total_tournaments,
                   (COALESCE(tr.trainings, 0)
                    + COALESCE(ev.events, 0)
                    + COALESCE(tp.tournaments, 0))     AS total_activity
            FROM members m
            LEFT JOIN (
                SELECT ms.member_id, COUNT(DISTINCT cs.sport_id) AS sports_count
                FROM member_sports ms
                JOIN club_sports cs ON cs.id = ms.club_sport_id
                WHERE ms.is_active = 1 AND cs.club_id = :c1
                GROUP BY ms.member_id
            ) sp ON sp.member_id = m.id
            LEFT JOIN (
                SELECT ta.member_id, COUNT(*) AS trainings
                FROM training_attendees ta
                JOIN trainings t ON t.id = ta.training_id
                WHERE t.club_id = :c2 AND ta.status IN ('obecny','spozniony')
                GROUP BY ta.member_id
            ) tr ON tr.member_id = m.id
            LEFT JOIN (
                SELECT er.member_id, COUNT(*) AS events
                FROM event_results er
                JOIN events e ON e.id = er.event_id
                WHERE e.club_id = :c3 AND er.member_id IS NOT NULL
                GROUP BY er.member_id
            ) ev ON ev.member_id = m.id
            LEFT JOIN (
                SELECT tp.member_id, COUNT(*) AS tournaments
                FROM tournament_participants tp
                JOIN tournaments tt ON tt.id = tp.tournament_id
                WHERE tt.club_id = :c4
                GROUP BY tp.member_id
            ) tp ON tp.member_id = m.id
            WHERE m.club_id = :c5
            HAVING total_activity > 0
            ORDER BY total_activity DESC, sports_count DESC
            LIMIT {$limit}
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':c1' => $clubId, ':c2' => $clubId, ':c3' => $clubId,
            ':c4' => $clubId, ':c5' => $clubId,
        ]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Liczba czlonkow per sport — uzywane na dashboardzie zarzadu.
     *
     * @return list<array{sport_key:string, sport_label:string, members_count:int, icon:?string, color:?string}>
     */
    public static function membersPerSportForClub(): array
    {
        $clubId = (int)(ClubContext::current() ?? 0);
        if ($clubId <= 0) {
            return [];
        }
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT s.`key` AS sport_key, s.name AS sport_label, s.icon, s.color,
                    COUNT(DISTINCT ms.member_id) AS members_count
             FROM member_sports ms
             JOIN club_sports cs ON cs.id = ms.club_sport_id
             JOIN sports s       ON s.id  = cs.sport_id
             WHERE cs.club_id = ? AND ms.is_active = 1
             GROUP BY s.id, s.`key`, s.name, s.icon, s.color
             ORDER BY members_count DESC, s.name"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Trend rejestracji nowych czlonkow per miesiac (ostatnie 12 mies).
     *
     * @return array{labels: list<string>, data: list<int>}
     */
    public static function registrationTrendForClub(): array
    {
        $clubId = (int)(ClubContext::current() ?? 0);
        if ($clubId <= 0) {
            return ['labels' => [], 'data' => []];
        }
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT DATE_FORMAT(joined_at, '%Y-%m') AS ym, COUNT(*) AS cnt
             FROM members
             WHERE club_id = ?
               AND joined_at IS NOT NULL
               AND joined_at >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
             GROUP BY ym
             ORDER BY ym"
        );
        $stmt->execute([$clubId]);
        $rows = $stmt->fetchAll() ?: [];

        // Build complete 12-month series even when months have 0 registrations
        $labels = [];
        $byMonth = [];
        foreach ($rows as $r) {
            $byMonth[$r['ym']] = (int)$r['cnt'];
        }
        for ($i = 11; $i >= 0; $i--) {
            $ts = strtotime("-{$i} months", strtotime(date('Y-m-01')));
            $key = date('Y-m', $ts);
            $labels[] = $key;
        }
        $data = array_map(fn($k) => $byMonth[$k] ?? 0, $labels);

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Czysci cache dla czlonka (np. po dodaniu treningu/rankingu).
     */
    public static function invalidate(int $memberId, ?int $clubId = null): void
    {
        $cid = $clubId ?? (int)(ClubContext::current() ?? 0);
        if (class_exists(Cache::class)) {
            Cache::delete("member_cross_stats:{$memberId}:club:{$cid}");
        }
        unset(self::$memo[$memberId . ':' . $cid]);
    }

    // ---------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------

    private static function fetchMemberSports(\PDO $db, int $memberId, int $clubId): array
    {
        $stmt = $db->prepare(
            "SELECT ms.club_sport_id, s.id AS sport_id, s.`key` AS sport_key,
                    s.name AS sport_name, s.icon, s.color
             FROM member_sports ms
             JOIN club_sports cs ON cs.id = ms.club_sport_id
             JOIN sports s       ON s.id  = cs.sport_id
             WHERE ms.member_id = ?
               AND ms.is_active = 1
               AND cs.club_id   = ?
             ORDER BY s.name"
        );
        $stmt->execute([$memberId, $clubId]);
        return $stmt->fetchAll() ?: [];
    }

    private static function countTrainingsForSport(\PDO $db, int $memberId, int $clubId, int $clubSportId): int
    {
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM training_attendees ta
             JOIN trainings t ON t.id = ta.training_id
             WHERE ta.member_id = ?
               AND t.club_id    = ?
               AND t.club_sport_id = ?
               AND ta.status IN ('obecny','spozniony')"
        );
        $stmt->execute([$memberId, $clubId, $clubSportId]);
        return (int)$stmt->fetchColumn();
    }

    private static function countEventsForSport(\PDO $db, int $memberId, int $clubId, int $sportId): int
    {
        if ($sportId <= 0) {
            return 0;
        }
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM event_results er
             JOIN events e ON e.id = er.event_id
             WHERE er.member_id = ?
               AND e.club_id    = ?
               AND e.sport_id   = ?"
        );
        $stmt->execute([$memberId, $clubId, $sportId]);
        return (int)$stmt->fetchColumn();
    }

    private static function countTournamentsForSport(\PDO $db, int $memberId, int $clubId, string $sportKey): int
    {
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM tournament_participants tp
             JOIN tournaments t ON t.id = tp.tournament_id
             WHERE tp.member_id = ?
               AND t.club_id    = ?
               AND t.sport_key  = ?"
        );
        $stmt->execute([$memberId, $clubId, $sportKey]);
        return (int)$stmt->fetchColumn();
    }

    private static function rankingsForSport(\PDO $db, int $memberId, int $clubId, string $sportKey): array
    {
        $stmt = $db->prepare(
            "SELECT season, ranking_points AS points, ranking_position AS position,
                    wins, competitions_count
             FROM sport_rankings
             WHERE member_id = ? AND club_id = ? AND sport_key = ?
             ORDER BY season DESC
             LIMIT 5"
        );
        $stmt->execute([$memberId, $clubId, $sportKey]);
        $rows = $stmt->fetchAll() ?: [];
        return array_map(static fn($r) => [
            'season'             => (string)$r['season'],
            'points'             => (int)$r['points'],
            'position'           => isset($r['position']) ? (int)$r['position'] : null,
            'wins'               => (int)($r['wins'] ?? 0),
            'competitions_count' => (int)($r['competitions_count'] ?? 0),
        ], $rows);
    }

    /**
     * UNION ALL z 3 zrodel + ORDER BY date DESC LIMIT 10.
     */
    private static function recentActivityForMember(\PDO $db, int $memberId, int $clubId): array
    {
        $sql = "
            (SELECT 'training' AS type, t.start_time AS dt, t.name AS label,
                    s.`key` AS sport_key, s.name AS sport_label, ta.status AS extra
             FROM training_attendees ta
             JOIN trainings t  ON t.id  = ta.training_id
             LEFT JOIN club_sports cs ON cs.id = t.club_sport_id
             LEFT JOIN sports s ON s.id = cs.sport_id
             WHERE ta.member_id = :m1 AND t.club_id = :c1
             ORDER BY t.start_time DESC LIMIT 10)
            UNION ALL
            (SELECT 'event' AS type, e.event_date AS dt, e.name AS label,
                    s.`key` AS sport_key, s.name AS sport_label,
                    CONCAT(COALESCE(er.place,'-'),' / score:',COALESCE(er.score,'-')) AS extra
             FROM event_results er
             JOIN events e ON e.id = er.event_id
             LEFT JOIN sports s ON s.id = e.sport_id
             WHERE er.member_id = :m2 AND e.club_id = :c2
             ORDER BY e.event_date DESC LIMIT 10)
            UNION ALL
            (SELECT 'tournament' AS type, t.date_start AS dt, t.name AS label,
                    t.sport_key AS sport_key, t.sport_key AS sport_label,
                    t.status AS extra
             FROM tournament_participants tp
             JOIN tournaments t ON t.id = tp.tournament_id
             WHERE tp.member_id = :m3 AND t.club_id = :c3
             ORDER BY t.date_start DESC LIMIT 10)
            ORDER BY dt DESC
            LIMIT 10
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':m1' => $memberId, ':c1' => $clubId,
            ':m2' => $memberId, ':c2' => $clubId,
            ':m3' => $memberId, ':c3' => $clubId,
        ]);
        $rows = $stmt->fetchAll() ?: [];
        return array_map(static fn($r) => [
            'type'        => (string)$r['type'],
            'date'        => (string)$r['dt'],
            'label'       => (string)$r['label'],
            'sport_key'   => $r['sport_key'] ?? null,
            'sport_label' => $r['sport_label'] ?? null,
            'extra'       => $r['extra'] ?? null,
        ], $rows);
    }

    /**
     * Highlights — najlepszy ranking, max ranking points, win count,
     * personal best place (event_results.place = 1/2/3).
     *
     * @return list<array{label:string, value:string, sport_key:?string}>
     */
    private static function computeHighlights(\PDO $db, int $memberId, int $clubId, array $sports): array
    {
        $highlights = [];

        // 1. Najlepsza pozycja w rankingu (najnizsza position > 0)
        $stmt = $db->prepare(
            "SELECT sport_key, season, ranking_points, ranking_position
             FROM sport_rankings
             WHERE member_id = ? AND club_id = ?
               AND ranking_position IS NOT NULL AND ranking_position > 0
             ORDER BY ranking_position ASC, ranking_points DESC
             LIMIT 1"
        );
        $stmt->execute([$memberId, $clubId]);
        if ($best = $stmt->fetch()) {
            $highlights[] = [
                'label'     => 'Najlepsza pozycja w rankingu',
                'value'     => '#' . (int)$best['ranking_position'] . ' (' . $best['sport_key'] . ', sezon ' . $best['season'] . ')',
                'sport_key' => (string)$best['sport_key'],
            ];
        }

        // 2. Max points w jednym sezonie
        $stmt = $db->prepare(
            "SELECT sport_key, season, ranking_points
             FROM sport_rankings
             WHERE member_id = ? AND club_id = ?
             ORDER BY ranking_points DESC
             LIMIT 1"
        );
        $stmt->execute([$memberId, $clubId]);
        if ($maxP = $stmt->fetch()) {
            if ((int)$maxP['ranking_points'] > 0) {
                $highlights[] = [
                    'label'     => 'Najwyzszy wynik punktowy',
                    'value'     => (int)$maxP['ranking_points'] . ' pkt (' . $maxP['sport_key'] . ', ' . $maxP['season'] . ')',
                    'sport_key' => (string)$maxP['sport_key'],
                ];
            }
        }

        // 3. Total wins (sum across sport_rankings)
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(wins), 0) FROM sport_rankings WHERE member_id = ? AND club_id = ?"
        );
        $stmt->execute([$memberId, $clubId]);
        $totalWins = (int)$stmt->fetchColumn();
        if ($totalWins > 0) {
            $highlights[] = [
                'label'     => 'Lacznie zwyciestw',
                'value'     => (string)$totalWins,
                'sport_key' => null,
            ];
        }

        // 4. Liczba podium w event_results (place 1-3)
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM event_results er
             JOIN events e ON e.id = er.event_id
             WHERE er.member_id = ? AND e.club_id = ? AND er.place BETWEEN 1 AND 3"
        );
        $stmt->execute([$memberId, $clubId]);
        $podiums = (int)$stmt->fetchColumn();
        if ($podiums > 0) {
            $highlights[] = [
                'label'     => 'Miejsca na podium (top 3)',
                'value'     => (string)$podiums,
                'sport_key' => null,
            ];
        }

        // 5. Najbardziej aktywny sport (po sumie aktywnosci)
        if (!empty($sports)) {
            $top = null;
            $topSum = 0;
            foreach ($sports as $sp) {
                $sum = $sp['trainings_count'] + $sp['events_count'] + $sp['tournaments_count'];
                if ($sum > $topSum) {
                    $top = $sp;
                    $topSum = $sum;
                }
            }
            if ($top && $topSum > 0) {
                $highlights[] = [
                    'label'     => 'Glowna dyscyplina (liczba aktywnosci)',
                    'value'     => $top['sport_label'] . ' (' . $topSum . ')',
                    'sport_key' => $top['sport_key'],
                ];
            }
        }

        return array_slice($highlights, 0, 5);
    }

    /**
     * Monthly chart — 12 ostatnich miesiecy, line per sport
     * (suma trainings+events+tournaments per miesiac).
     *
     * @return array{labels: list<string>, datasets: list<array>}
     */
    private static function monthlyActivityChart(\PDO $db, int $memberId, int $clubId, array $sports): array
    {
        // Labels: 12 ostatnich miesiecy
        $labels = [];
        $labelIndex = [];
        for ($i = 11; $i >= 0; $i--) {
            $ts = strtotime("-{$i} months", strtotime(date('Y-m-01')));
            $ym = date('Y-m', $ts);
            $labels[] = $ym;
            $labelIndex[$ym] = 11 - $i;
        }

        $palette = ['#0d6efd','#198754','#ffc107','#dc3545','#6f42c1','#fd7e14','#20c997','#0dcaf0','#d63384','#6610f2'];
        $datasets = [];

        foreach ($sports as $idx => $sp) {
            $data = array_fill(0, 12, 0);

            // trainings per month for this sport (via club_sport_id)
            $stmt = $db->prepare(
                "SELECT DATE_FORMAT(t.start_time, '%Y-%m') AS ym, COUNT(*) AS c
                 FROM training_attendees ta
                 JOIN trainings t ON t.id = ta.training_id
                 JOIN club_sports cs ON cs.id = t.club_sport_id
                 JOIN sports s ON s.id = cs.sport_id
                 WHERE ta.member_id = ? AND t.club_id = ? AND s.`key` = ?
                   AND t.start_time >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                   AND ta.status IN ('obecny','spozniony')
                 GROUP BY ym"
            );
            $stmt->execute([$memberId, $clubId, $sp['sport_key']]);
            foreach ($stmt->fetchAll() as $r) {
                if (isset($labelIndex[$r['ym']])) {
                    $data[$labelIndex[$r['ym']]] += (int)$r['c'];
                }
            }

            // events per month
            $stmt = $db->prepare(
                "SELECT DATE_FORMAT(e.event_date, '%Y-%m') AS ym, COUNT(*) AS c
                 FROM event_results er
                 JOIN events e ON e.id = er.event_id
                 JOIN sports s ON s.id = e.sport_id
                 WHERE er.member_id = ? AND e.club_id = ? AND s.`key` = ?
                   AND e.event_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                 GROUP BY ym"
            );
            $stmt->execute([$memberId, $clubId, $sp['sport_key']]);
            foreach ($stmt->fetchAll() as $r) {
                if (isset($labelIndex[$r['ym']])) {
                    $data[$labelIndex[$r['ym']]] += (int)$r['c'];
                }
            }

            // tournaments per month
            $stmt = $db->prepare(
                "SELECT DATE_FORMAT(t.date_start, '%Y-%m') AS ym, COUNT(*) AS c
                 FROM tournament_participants tp
                 JOIN tournaments t ON t.id = tp.tournament_id
                 WHERE tp.member_id = ? AND t.club_id = ? AND t.sport_key = ?
                   AND t.date_start >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
                 GROUP BY ym"
            );
            $stmt->execute([$memberId, $clubId, $sp['sport_key']]);
            foreach ($stmt->fetchAll() as $r) {
                if (isset($labelIndex[$r['ym']])) {
                    $data[$labelIndex[$r['ym']]] += (int)$r['c'];
                }
            }

            $datasets[] = [
                'label'           => $sp['sport_label'],
                'data'            => $data,
                'borderColor'     => $sp['color'] ?: $palette[$idx % count($palette)],
                'backgroundColor' => 'rgba(13,110,253,0.08)',
                'tension'         => 0.3,
                'fill'            => false,
            ];
        }

        return ['labels' => $labels, 'datasets' => $datasets];
    }
}
