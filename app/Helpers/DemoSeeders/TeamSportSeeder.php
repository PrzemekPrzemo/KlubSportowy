<?php

namespace App\Helpers\DemoSeeders;

use App\Helpers\Database;
use App\Sports\Support\BaseSportArchetype;
use App\Sports\Support\TeamSport;
use PDO;

/**
 * Generic seeder dla TeamSport-archetype pluginow.
 *
 * Strategia: introspekcja schemy przez INFORMATION_SCHEMA + insert minimalny
 * dla typowych kolumn. Plugin sport ktory ma niestandardowa schemę override-uje
 * `seedFor*()` w wlasnym subseederze (override przez DemoSeederFactory::register
 * z innym archetypeClass).
 *
 * Tworzy:
 *   - 2 drużyny (`<key>_teams`)  — np. "Demo A", "Demo B"
 *   - 12 zawodnikow per druzyna (`<key>_players` linked to members)
 *   - 5 meczy (`<key>_matches`)
 *   - 8 zdarzen (`<key>_events` lub `<key>_match_events`)
 */
class TeamSportSeeder implements ArchetypeSeederInterface
{
    public function archetypeClass(): string
    {
        return TeamSport::class;
    }

    public function seed(int $clubId, BaseSportArchetype $archetype, array $counts = []): array
    {
        if (!($archetype instanceof TeamSport)) {
            return ['skipped' => 'wrong_archetype', 'expected' => TeamSport::class];
        }

        $defaults = $archetype->defaultSeedCounts();
        $teamCount    = (int)($counts['team']    ?? $defaults['team']    ?? 2);
        $athleteCount = (int)($counts['athlete'] ?? $defaults['athlete'] ?? 12);
        $eventCount   = (int)($counts['event']   ?? $defaults['event']   ?? 5);
        $resultCount  = (int)($counts['result']  ?? $defaults['result']  ?? 8);

        $db = Database::pdo();
        $key = $archetype->key();

        $created = ['team' => 0, 'athlete' => 0, 'event' => 0, 'result' => 0];

        // 1. Druzyny
        $teamsTable = "{$key}_teams";
        if ($this->tableExists($db, $teamsTable)) {
            $teamIds = $this->seedTeams($db, $teamsTable, $clubId, $teamCount);
            $created['team'] = count($teamIds);
        } else {
            $teamIds = [];
        }

        // 2. Zawodnicy (members + plugin players join table jesli jest)
        $memberIds = $this->seedMembers($db, $clubId, $athleteCount);
        $created['athlete'] = count($memberIds);

        $playersTable = "{$key}_players";
        if ($this->tableExists($db, $playersTable) && !empty($teamIds) && !empty($memberIds)) {
            $this->seedPlayers($db, $playersTable, $clubId, $teamIds, $memberIds);
        }

        // 3. Mecze
        $matchesTable = "{$key}_matches";
        $matchIds = [];
        if ($this->tableExists($db, $matchesTable) && count($teamIds) >= 1) {
            $matchIds = $this->seedMatches($db, $matchesTable, $clubId, $teamIds, $eventCount);
            $created['event'] = count($matchIds);
        }

        // 4. Zdarzenia (events lub match_events — try both)
        $eventsTable = $this->firstExistingTable($db, [
            "{$key}_match_events",
            "{$key}_events",
        ]);
        if ($eventsTable !== null && !empty($matchIds) && !empty($memberIds)) {
            $created['result'] = $this->seedEvents(
                $db, $eventsTable, $matchIds, $memberIds, $resultCount
            );
        }

        return ['created' => $created];
    }

    // ── Helpery (proste, defensywne) ───────────────────────────────────────

    private function tableExists(PDO $db, string $table): bool
    {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$table]);
        return ((int)$stmt->fetchColumn()) > 0;
    }

    private function firstExistingTable(PDO $db, array $candidates): ?string
    {
        foreach ($candidates as $t) {
            if ($this->tableExists($db, $t)) return $t;
        }
        return null;
    }

    /** @return int[] */
    private function seedTeams(PDO $db, string $table, int $clubId, int $count): array
    {
        $teamIds = [];
        for ($i = 1; $i <= $count; $i++) {
            $name = "Demo Team " . chr(64 + $i); // Demo Team A / B / C ...
            $stmt = $db->prepare("INSERT INTO `{$table}` (club_id, name) VALUES (?, ?)");
            try {
                $stmt->execute([$clubId, $name]);
                $teamIds[] = (int)$db->lastInsertId();
            } catch (\Throwable) {
                // table moze miec dodatkowe NOT NULL kolumny — ignorujemy
                break;
            }
        }
        return $teamIds;
    }

    /** @return int[] member IDs */
    private function seedMembers(PDO $db, int $clubId, int $count): array
    {
        $ids = [];
        for ($i = 1; $i <= $count; $i++) {
            $stmt = $db->prepare(
                "INSERT INTO members (club_id, first_name, last_name, status, member_number, join_date)
                 VALUES (?, ?, ?, 'aktywny', ?, CURDATE())"
            );
            $num = 'DEMO-' . sprintf('%04d', $i);
            try {
                $stmt->execute([$clubId, "Demo{$i}", "Player", $num]);
                $ids[] = (int)$db->lastInsertId();
            } catch (\Throwable) {
                break;
            }
        }
        return $ids;
    }

    private function seedPlayers(PDO $db, string $table, int $clubId, array $teamIds, array $memberIds): void
    {
        $cols = $this->cols($db, $table);
        if (!isset($cols['team_id']) || !isset($cols['member_id'])) return;

        $teamIdx = 0;
        foreach ($memberIds as $mi => $memberId) {
            $teamId = $teamIds[$teamIdx % count($teamIds)];
            $teamIdx++;
            $stmt = $db->prepare(
                "INSERT INTO `{$table}` (club_id, team_id, member_id) VALUES (?, ?, ?)"
            );
            try { $stmt->execute([$clubId, $teamId, $memberId]); }
            catch (\Throwable) { /* unique key, skip */ }
        }
    }

    /** @return int[] match ids */
    private function seedMatches(PDO $db, string $table, int $clubId, array $teamIds, int $count): array
    {
        $cols = $this->cols($db, $table);
        $matchIds = [];

        for ($i = 0; $i < $count; $i++) {
            $home = $teamIds[$i % count($teamIds)];
            $away = $teamIds[($i + 1) % count($teamIds)];
            if ($home === $away && count($teamIds) >= 2) {
                $away = $teamIds[($i + 2) % count($teamIds)];
            }
            $date = (new \DateTime("+{$i} days"))->format('Y-m-d H:i:s');

            // Standardowe kolumny: club_id, home_team_id, match_date.
            // away_team_id / away_team_name niektore plugin maja.
            $fields = ['club_id' => $clubId, 'home_team_id' => $home, 'match_date' => $date];
            if (isset($cols['away_team_id'])) {
                $fields['away_team_id'] = $away;
            }
            if (isset($cols['away_team_name']) && !isset($cols['away_team'])) {
                $fields['away_team_name'] = 'Demo Opponent ' . ($i + 1);
            } elseif (isset($cols['away_team']) && !isset($fields['away_team_id'])) {
                $fields['away_team'] = 'Demo Opponent ' . ($i + 1);
            }
            if (isset($cols['home_score'])) $fields['home_score'] = rand(15, 30);
            if (isset($cols['away_score'])) $fields['away_score'] = rand(15, 30);

            $colList = '`' . implode('`,`', array_keys($fields)) . '`';
            $holders = implode(',', array_fill(0, count($fields), '?'));
            $stmt = $db->prepare("INSERT INTO `{$table}` ({$colList}) VALUES ({$holders})");
            try {
                $stmt->execute(array_values($fields));
                $matchIds[] = (int)$db->lastInsertId();
            } catch (\Throwable) {
                break;
            }
        }
        return $matchIds;
    }

    private function seedEvents(PDO $db, string $table, array $matchIds, array $memberIds, int $count): int
    {
        $cols = $this->cols($db, $table);
        if (!isset($cols['match_id']) || !isset($cols['member_id'])) return 0;

        $created = 0;
        for ($i = 0; $i < $count; $i++) {
            $matchId = $matchIds[$i % count($matchIds)];
            $memberId = $memberIds[$i % count($memberIds)];

            $fields = ['match_id' => $matchId, 'member_id' => $memberId];
            // Common optional columns
            if (isset($cols['minute'])) $fields['minute'] = rand(1, 90);
            if (isset($cols['type'])) {
                // Pobierz pierwsza wartosc z ENUM type
                $fields['type'] = $this->firstEnumValue($db, $table, 'type') ?? 'gol';
            }

            $colList = '`' . implode('`,`', array_keys($fields)) . '`';
            $holders = implode(',', array_fill(0, count($fields), '?'));
            $stmt = $db->prepare("INSERT INTO `{$table}` ({$colList}) VALUES ({$holders})");
            try {
                $stmt->execute(array_values($fields));
                $created++;
            } catch (\Throwable) {
                continue;
            }
        }
        return $created;
    }

    /** @return array<string, true> column name => true */
    private function cols(PDO $db, string $table): array
    {
        $stmt = $db->prepare(
            'SELECT column_name FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$table]);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $c) {
            $out[strtolower($c)] = true;
        }
        return $out;
    }

    private function firstEnumValue(PDO $db, string $table, string $column): ?string
    {
        $stmt = $db->prepare(
            "SELECT column_type FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?"
        );
        $stmt->execute([$table, $column]);
        $type = (string)$stmt->fetchColumn();
        if (!str_starts_with(strtolower($type), 'enum(')) return null;
        if (preg_match("/^enum\('([^']+)'/i", $type, $m)) return $m[1];
        return null;
    }
}
