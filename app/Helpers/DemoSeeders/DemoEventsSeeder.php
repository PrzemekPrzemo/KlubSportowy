<?php

declare(strict_types=1);

namespace App\Helpers\DemoSeeders;

use App\Helpers\Database;
use App\Helpers\Ranking\RankingEngine;
use PDO;
use Throwable;

/**
 * Seeds events: weekly trainings (past 30d + next 30d), historic tournaments
 * with results, and finally triggers RankingEngine::recalculateForSport().
 */
final class DemoEventsSeeder
{
    public static function seed(array &$context): array
    {
        $db = Database::pdo();
        $clubId    = (int)$context['club_id'];
        $createdBy = $context['admin_user_id'] ?? null;
        $sportIds  = $context['sport_ids']      ?? [];
        $membersPerSport = $context['members_per_sport'] ?? [];

        $today = new \DateTimeImmutable('today');

        $stats = [
            'trainings'     => 0,
            'events'        => 0,
            'event_entries' => 0,
            'event_results' => 0,
            'tournaments'   => 0,
            'ranking_runs'  => 0,
        ];

        if (empty($sportIds)) return $stats;

        // ── 1. Trainings (2-3 per week per sport, past 30d + next 30d) ────
        $trainingInsert = $db->prepare(
            "INSERT INTO trainings
                (club_id, sport_id, club_sport_id, name, description, location, start_time, end_time, status, created_by, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,NOW())"
        );

        $trainNames = ['Trening ogolnorozwojowy', 'Trening techniczny', 'Trening taktyczny', 'Trening kondycyjny'];
        $locations  = ['Hala glowna', 'Boisko zewnetrzne', 'Centrum sportowe', 'Sala gimnastyczna'];

        foreach ($sportIds as $sportKey => $sportId) {
            $csId = $context['club_sport_ids'][$sportKey] ?? null;

            for ($dayOffset = -30; $dayOffset <= 30; $dayOffset++) {
                $date = $today->modify("{$dayOffset} days");
                $dow = (int)$date->format('N');
                // Tu/Th/Sat schedule
                if (!in_array($dow, [2, 4, 6], true)) continue;

                $startHour = 17 + (($dayOffset + 30) % 3);
                $start = $date->setTime($startHour, 0);
                $end   = $start->modify('+90 minutes');

                $status = $dayOffset < 0 ? 'zakonczony' : 'zaplanowany';
                $trainingInsert->execute([
                    $clubId, $sportId, $csId,
                    DemoNames::pick($trainNames, $dayOffset + 30, 0) . ' (' . $sportKey . ')',
                    '[DEMO] Trening generowany automatycznie.',
                    DemoNames::pick($locations, $dayOffset + 30, 1),
                    $start->format('Y-m-d H:i:s'),
                    $end->format('Y-m-d H:i:s'),
                    $status,
                    $createdBy,
                ]);
                $stats['trainings']++;
            }
        }

        // ── 2. Historic events with results (per sport) ───────────────────
        $eventInsert = $db->prepare(
            "INSERT INTO events
                (club_id, sport_id, type, name, event_date, end_date, location, status, description, created_by, created_at)
             VALUES (?,?,?,?,?,?,?, 'zakonczone', ?, ?, NOW())"
        );

        $entryInsert = $db->prepare(
            "INSERT IGNORE INTO event_entries (event_id, member_id, status, registered_at)
             VALUES (?, ?, 'potwierdzony', NOW())"
        );

        $resultInsert = $db->prepare(
            "INSERT INTO event_results (event_id, member_id, score, place, extra, notes, entered_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );

        foreach ($sportIds as $sportKey => $sportId) {
            $participants = $membersPerSport[$sportKey] ?? [];
            if (count($participants) < 4) continue;

            // 5 events: scattered in past 6 months
            for ($e = 1; $e <= 5; $e++) {
                $eventDate = $today->modify('-' . (15 * $e) . ' days')->setTime(10, 0);
                $eventEnd  = $eventDate->modify('+3 hours');

                $eventInsert->execute([
                    $clubId, $sportId, 'turniej',
                    sprintf('Turniej %s #%d', ucfirst($sportKey), $e),
                    $eventDate->format('Y-m-d H:i:s'),
                    $eventEnd->format('Y-m-d H:i:s'),
                    DemoNames::pick(['Stadion miejski','Hala glowna','Centrum sportowe','Obiekt zewnetrzny'], $e, 0),
                    '[DEMO] Turniej historyczny z wynikami.',
                    $createdBy,
                ]);
                $eventId = (int)$db->lastInsertId();
                $stats['events']++;

                // Take ~8 participants
                $picked = array_slice($participants, ($e - 1) * 2, 8);
                if (count($picked) < 2) {
                    $picked = array_slice($participants, 0, min(8, count($participants)));
                }

                foreach ($picked as $idx => $mid) {
                    $entryInsert->execute([$eventId, $mid]);
                    $stats['event_entries']++;

                    // Sport-specific results
                    [$score, $extra] = self::buildResult($sportKey, $idx, $e);
                    $place = $idx + 1;
                    $resultInsert->execute([
                        $eventId, $mid, $score, $place,
                        json_encode($extra, JSON_UNESCAPED_UNICODE),
                        '[DEMO] Wynik generowany.', $createdBy,
                    ]);
                    $stats['event_results']++;
                }
            }
        }

        // ── 3. Tournaments table (for RankingEngine to pick up) ───────────
        try {
            $tournInsert = $db->prepare(
                "INSERT INTO tournaments (club_id, sport_key, name, format, date_start, status, created_at)
                 VALUES (?, ?, ?, 'round_robin', ?, 'finished', NOW())"
            );
            $tpInsert = $db->prepare(
                "INSERT IGNORE INTO tournament_participants (tournament_id, member_id, seed, eliminated) VALUES (?,?,?,0)"
            );

            foreach ($sportIds as $sportKey => $sportId) {
                $participants = $membersPerSport[$sportKey] ?? [];
                if (count($participants) < 4) continue;

                $start = $today->modify('-60 days');
                $tournInsert->execute([
                    $clubId, $sportKey,
                    'Liga klubowa ' . ucfirst($sportKey) . ' ' . $today->format('Y'),
                    $start->format('Y-m-d'),
                ]);
                $tournId = (int)$db->lastInsertId();
                $stats['tournaments']++;

                foreach (array_slice($participants, 0, 8) as $seed => $mid) {
                    $tpInsert->execute([$tournId, $mid, $seed + 1]);
                }
            }
        } catch (Throwable $e) {
            // tournaments table missing in some envs — non-fatal.
        }

        // ── 4. Rankings recalculation ─────────────────────────────────────
        foreach (array_keys($sportIds) as $sportKey) {
            try {
                RankingEngine::recalculateForSport($sportKey, (string)(int)date('Y'));
                $stats['ranking_runs']++;
            } catch (Throwable $e) {
                // Engine may not support every sport — ignore failures, demo still useful.
            }
        }

        return $stats;
    }

    /**
     * @return array{0: float, 1: array<string,mixed>}
     */
    private static function buildResult(string $sportKey, int $idx, int $eventNo): array
    {
        return match ($sportKey) {
            'football' => [
                3.0 - ($idx * 0.2),
                ['goals' => max(0, 5 - $idx), 'assists' => max(0, 3 - $idx), 'event' => $eventNo],
            ],
            'basketball' => [
                max(0, 28.0 - $idx * 2.5),
                ['points' => max(0, 28 - $idx * 2), 'rebounds' => max(0, 12 - $idx), 'assists' => max(0, 8 - $idx)],
            ],
            'volleyball' => [
                max(0, 20.0 - $idx * 1.5),
                ['blocks' => max(0, 8 - $idx), 'kills' => max(0, 15 - $idx)],
            ],
            'swimming' => [
                // Score = time in seconds — lower-is-better strategies still get a value
                55.0 + $idx * 0.8 + $eventNo * 0.1,
                ['discipline' => '100m freestyle', 'time_sec' => 55.0 + $idx * 0.8, 'lane' => $idx + 1],
            ],
            'tennis' => [
                max(0, 7.0 - $idx * 0.5),
                ['sets_won' => max(0, 2 - intdiv($idx, 3)), 'aces' => max(0, 6 - $idx)],
            ],
            default => [
                max(0, 100.0 - $idx * 10),
                ['raw' => 100 - $idx * 10],
            ],
        };
    }
}
