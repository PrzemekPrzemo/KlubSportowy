<?php

namespace App\Helpers\DemoSeeders;

use App\Helpers\Database;
use App\Sports\Support\BaseSportArchetype;
use App\Sports\Support\RacketSport;
use PDO;

/**
 * Generic seeder dla RacketSport-archetype (Tennis, TableTennis, Badminton,
 * Squash, Archery, Golf, Padel).
 *
 * Strategia: introspekcja kolumn + auto-fill required cols. Pokrywa
 * rozne konwencje schemy:
 *
 * 1. Klasyczne `<key>_results` z member_id:
 *    - TableTennis, Badminton — competition_name/_date
 *    - Squash — match_date REQUIRED, competition_name NULL
 *    - Archery — `archery_scores` ze score_date, discipline ENUM, total_score
 *    - Golf — `golf_rounds` z round_date, course_name, tees ENUM, total_strokes
 *
 * 2. Pojedynki dwuzawodnikowe (player1_id/player2_id):
 *    - Tennis — `tennis_matches` z player1/player2_id, sets VARCHAR REQUIRED
 *
 * 3. Pary (Padel) — `padel_pairs` z player1_id/player2_id (no results table)
 *
 * Seeder iteruje archetype.tables() i seeduje kazda tabele z member_id
 * (lub player1_id) defensywnie.
 */
class RacketSportSeeder implements ArchetypeSeederInterface
{
    public function archetypeClass(): string
    {
        return RacketSport::class;
    }

    public function seed(int $clubId, BaseSportArchetype $archetype, array $counts = []): array
    {
        if (!($archetype instanceof RacketSport)) {
            return ['skipped' => 'wrong_archetype', 'expected' => RacketSport::class];
        }

        $defaults     = $archetype->defaultSeedCounts();
        $athleteCount = (int)($counts['athlete'] ?? $defaults['athlete'] ?? 8);
        $resultCount  = (int)($counts['result']  ?? $defaults['result']  ?? 6);

        $db  = Database::pdo();
        $created = ['athlete' => 0, 'result' => 0];

        $memberIds = $this->seedMembers($db, $clubId, $athleteCount);
        $created['athlete'] = count($memberIds);
        if (empty($memberIds)) return ['created' => $created];

        // Iteruj po archetype.tables() i seeduj kazda data-table (z member_id
        // lub player1_id). tables[] moze zawierac court/equipment tables ktore
        // przeskakujemy.
        foreach ($archetype->tables() as $table) {
            if (!$this->tableExists($db, $table)) continue;
            $created['result'] += $this->seedTable(
                $db, $table, $clubId, $memberIds, $resultCount, $archetype->key()
            );
        }

        return ['created' => $created];
    }

    private function tableExists(PDO $db, string $table): bool
    {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$table]);
        return ((int)$stmt->fetchColumn()) > 0;
    }

    private function colsMeta(PDO $db, string $table): array
    {
        $stmt = $db->prepare(
            'SELECT column_name, is_nullable, column_default, column_type, data_type, extra
             FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$table]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $name = strtolower($row['COLUMN_NAME'] ?? $row['column_name']);
            $out[$name] = [
                'nullable'  => strtoupper((string)($row['IS_NULLABLE'] ?? $row['is_nullable'])) === 'YES',
                'default'   => $row['COLUMN_DEFAULT'] ?? $row['column_default'],
                'type'      => (string)($row['COLUMN_TYPE'] ?? $row['column_type']),
                'data_type' => strtolower((string)($row['DATA_TYPE'] ?? $row['data_type'])),
                'extra'     => strtolower((string)($row['EXTRA'] ?? $row['extra'])),
            ];
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
        if (preg_match("/^enum\('([^']+)'/i", $type, $m)) return $m[1];
        return null;
    }

    /** @return int[] */
    private function seedMembers(PDO $db, int $clubId, int $count): array
    {
        $names = [
            ['Iga',      'Świątek'],
            ['Hubert',   'Hurkacz'],
            ['Magda',    'Linette'],
            ['Kamil',    'Majchrzak'],
            ['Wojciech', 'Fibak'],
            ['Anna',     'Lewicka'],
            ['Marek',    'Tylski'],
            ['Aleksandra','Wozniak'],
        ];
        $ids = [];
        for ($i = 0; $i < min($count, count($names)); $i++) {
            [$first, $last] = $names[$i];
            $stmt = $db->prepare(
                "INSERT INTO members (club_id, first_name, last_name, status, member_number, join_date)
                 VALUES (?, ?, ?, 'aktywny', ?, CURDATE())"
            );
            try {
                $stmt->execute([$clubId, $first, $last, 'RAC-' . sprintf('%03d', $i + 1)]);
                $ids[] = (int)$db->lastInsertId();
            } catch (\Throwable) { /* skip */ }
        }
        return $ids;
    }

    /**
     * Generic seeder dla pojedynczej tabeli — auto-fill bazowanego na introspekcji.
     */
    private function seedTable(PDO $db, string $table, int $clubId, array $memberIds, int $resultCount, string $sportKey): int
    {
        $meta = $this->colsMeta($db, $table);
        $cols = array_fill_keys(array_keys($meta), true);

        // Tabela musi miec FK do members lub players — inaczej pomijamy
        // (np. Tennis courts, Padel courts, Archery bows bez konkretnego ownera).
        $hasMember  = isset($cols['member_id']);
        $hasPlayer1 = isset($cols['player1_id']);
        $hasLeader  = isset($cols['leader_id']);
        if (!$hasMember && !$hasPlayer1 && !$hasLeader) return 0;

        $count = 0;
        $eventName = 'Demo ' . ucfirst($sportKey) . ' Open ' . date('Y');

        for ($i = 0; $i < $resultCount && $i < count($memberIds); $i++) {
            $eventDate = date('Y-m-d', strtotime('-' . (5 + $i * 4) . ' days'));

            $fields = ['club_id' => $clubId];

            // Player references
            if ($hasMember) {
                $fields['member_id'] = $memberIds[$i];
            } elseif ($hasPlayer1) {
                $fields['player1_id'] = $memberIds[$i];
                if (isset($cols['player2_id'])) {
                    // Pair partner (rotacja)
                    $fields['player2_id'] = $memberIds[($i + 1) % count($memberIds)];
                }
            } elseif ($hasLeader) {
                $fields['leader_id'] = $memberIds[$i];
            }

            // Naming convention (multiple variants)
            if (isset($cols['competition_name'])) $fields['competition_name'] = $eventName;
            if (isset($cols['competition_date'])) $fields['competition_date'] = $eventDate;
            if (isset($cols['event_name']))       $fields['event_name']       = $eventName;
            if (isset($cols['event_date']))       $fields['event_date']       = $eventDate;
            if (isset($cols['match_date']))       $fields['match_date']       = $eventDate;
            if (isset($cols['score_date']))       $fields['score_date']       = $eventDate;
            if (isset($cols['round_date']))       $fields['round_date']       = $eventDate;
            if (isset($cols['course_name']))      $fields['course_name']      = 'Demo Golf Course';
            if (isset($cols['updated_at']) && ($meta['updated_at']['data_type'] ?? '') === 'date') {
                $fields['updated_at'] = $eventDate; // golf_handicaps.updated_at jest DATE NOT NULL
            }

            if (isset($cols['placement'])) $fields['placement'] = ($i % 8) + 1;
            if (isset($cols['place']))     $fields['place']     = ($i % 8) + 1;

            // Tennis: sets VARCHAR REQUIRED
            if (isset($cols['sets']) && ($meta['sets']['data_type'] ?? '') === 'varchar') {
                $fields['sets'] = '6:4,7:5,6:3';
            }
            // Tennis rankings: season VARCHAR REQUIRED
            if (isset($cols['season'])) {
                $fields['season'] = (string)date('Y');
            }

            // Required NOT NULL bez default — auto-fill (pomin GENERATED).
            foreach ($meta as $name => $m) {
                if (isset($fields[$name])) continue;
                if ($m['nullable'] || $m['default'] !== null) continue;
                if (str_contains($m['extra'], 'generated')) continue;

                $type = $m['data_type'];
                if (stripos($m['type'], 'enum(') === 0) {
                    $val = $this->firstEnumValue($db, $table, $name);
                    if ($val !== null) $fields[$name] = $val;
                } elseif (in_array($type, ['int','tinyint','smallint','mediumint','bigint','decimal','float','double'], true)) {
                    $fields[$name] = $this->numericDemoValue($name, $i);
                } elseif (in_array($type, ['varchar','char','text'], true)) {
                    $fields[$name] = '—';
                } elseif (in_array($type, ['date','datetime','timestamp'], true)) {
                    $fields[$name] = $eventDate;
                }
            }

            $colList = '`' . implode('`,`', array_keys($fields)) . '`';
            $holders = implode(',', array_fill(0, count($fields), '?'));
            $stmt = $db->prepare("INSERT INTO `{$table}` ({$colList}) VALUES ({$holders})");
            try {
                $stmt->execute(array_values($fields));
                $count++;
            } catch (\Throwable) { continue; }
        }
        return $count;
    }

    private function numericDemoValue(string $colName, int $i): int|float
    {
        $n = strtolower($colName);
        if (str_contains($n, 'whs_index'))                   return 18.5;
        if (str_contains($n, 'total_score'))                 return 280 + $i * 5;
        if (str_contains($n, 'total_strokes'))               return 80;
        if (str_contains($n, 'strokes'))                     return 80;
        if (str_contains($n, 'points') || str_contains($n, 'rating')) return 1200 + $i * 25;
        if (str_contains($n, 'sets_won') || str_contains($n, 'sets_lost')) return 2;
        if (str_contains($n, 'duration_min'))                return 90;
        return 1;
    }
}
