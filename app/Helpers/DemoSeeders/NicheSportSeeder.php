<?php

namespace App\Helpers\DemoSeeders;

use App\Helpers\Database;
use App\Sports\Support\BaseSportArchetype;
use App\Sports\Support\NicheSport;
use PDO;

/**
 * Generic seeder dla NicheSport-archetype (Bridge, Chess, Climbing,
 * CrossFit, Sailing).
 *
 * Niche sporty maja unikalne schemy z parent-child dependencies:
 *   - Climbing: climbing_routes (parent) → climbing_sends.route_id (child)
 *   - CrossFit: crossfit_wods (parent) → crossfit_scores.wod_id (child)
 *   - Sailing: sailing_boats (parent) → sailing_crew.boat_id (child)
 *
 * Strategia: archetype.tables() musi zwrocic tabele w kolejnosci
 * dependency (parent first). Seeder iteruje po nich i:
 *   1. Tabele bez FK do innej plugin-tabeli (oprocz clubs/members) seeduje
 *      jako "parent" — zapamietuje ostatnie id w lookup map.
 *   2. Tabele z FK do innej plugin-tabeli pobiera id z lookup mapy.
 *
 * Auto-detekcja kolumn:
 *   - club_id, member_id, player1_id/player2_id (Bridge) jak w innych seederach
 *   - Required ENUMy bez default → pierwsza wartosc ENUM
 *   - Required NUMERIC NOT NULL → heurystyka per kolumna
 *   - Required VARCHAR NOT NULL → '—' lub demo nazwa
 */
class NicheSportSeeder implements ArchetypeSeederInterface
{
    public function archetypeClass(): string
    {
        return NicheSport::class;
    }

    public function seed(int $clubId, BaseSportArchetype $archetype, array $counts = []): array
    {
        if (!($archetype instanceof NicheSport)) {
            return ['skipped' => 'wrong_archetype', 'expected' => NicheSport::class];
        }

        $defaults     = $archetype->defaultSeedCounts();
        $athleteCount = (int)($counts['athlete'] ?? $defaults['athlete'] ?? 6);
        $resultCount  = (int)($counts['result']  ?? $defaults['result']  ?? 6);

        $db  = Database::pdo();
        $created = ['athlete' => 0, 'result' => 0];

        $memberIds = $this->seedMembers($db, $clubId, $athleteCount);
        $created['athlete'] = count($memberIds);
        if (empty($memberIds)) return ['created' => $created];

        // Lookup map: tableName => [insertedId1, insertedId2, ...]
        // Zaplnia child tables (np. climbing_sends.route_id pochodzi z
        // climbing_routes seedowane wczesniej).
        $insertedIds = [];

        foreach ($archetype->tables() as $table) {
            if (!$this->tableExists($db, $table)) continue;
            $ids = $this->seedTable(
                $db, $table, $clubId, $memberIds, $insertedIds,
                $resultCount, $archetype->key()
            );
            $created['result'] += count($ids);
            $insertedIds[$table] = $ids;
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

    /** @return array<string, string> col_name => referenced_table */
    private function fkMap(PDO $db, string $table): array
    {
        $stmt = $db->prepare(
            'SELECT column_name, referenced_table_name
             FROM information_schema.key_column_usage
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND referenced_table_name IS NOT NULL'
        );
        $stmt->execute([$table]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $col = strtolower($row['COLUMN_NAME'] ?? $row['column_name']);
            $ref = strtolower($row['REFERENCED_TABLE_NAME'] ?? $row['referenced_table_name']);
            $out[$col] = $ref;
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
            ['Helena',  'Sokołowska'],
            ['Bogdan',  'Czarnecki'],
            ['Ewa',     'Wójcicka'],
            ['Roman',   'Borkowski'],
            ['Olga',    'Brzezińska'],
            ['Marcin',  'Olszewski'],
        ];
        $ids = [];
        for ($i = 0; $i < min($count, count($names)); $i++) {
            [$first, $last] = $names[$i];
            $stmt = $db->prepare(
                "INSERT INTO members (club_id, first_name, last_name, status, member_number, join_date)
                 VALUES (?, ?, ?, 'aktywny', ?, CURDATE())"
            );
            try {
                $stmt->execute([$clubId, $first, $last, 'NIC-' . sprintf('%03d', $i + 1)]);
                $ids[] = (int)$db->lastInsertId();
            } catch (\Throwable) { /* skip */ }
        }
        return $ids;
    }

    /** @return int[] inserted row IDs */
    private function seedTable(
        PDO $db,
        string $table,
        int $clubId,
        array $memberIds,
        array $insertedIds,
        int $resultCount,
        string $sportKey
    ): array {
        $meta = $this->colsMeta($db, $table);
        $cols = array_fill_keys(array_keys($meta), true);
        $fks  = $this->fkMap($db, $table);

        $hasPlayerFk = isset($cols['member_id']) || isset($cols['player1_id']) || isset($cols['leader_id']);

        $insertedRowIds = [];
        $eventName = 'Demo ' . ucfirst($sportKey) . ' ' . date('Y');

        for ($i = 0; $i < $resultCount && $i < max(1, count($memberIds)); $i++) {
            $eventDate = date('Y-m-d', strtotime('-' . (5 + $i * 7) . ' days'));

            $fields = ['club_id' => $clubId];

            // Player references
            if (isset($cols['member_id'])) {
                $fields['member_id'] = $memberIds[$i % count($memberIds)];
            } elseif (isset($cols['player1_id'])) {
                $fields['player1_id'] = $memberIds[$i % count($memberIds)];
                if (isset($cols['player2_id'])) {
                    $fields['player2_id'] = $memberIds[($i + 1) % count($memberIds)];
                }
            } elseif (isset($cols['leader_id'])) {
                $fields['leader_id'] = $memberIds[$i % count($memberIds)];
            }

            // FKs do innych plugin-tabel (np. climbing_sends.route_id, crossfit_scores.wod_id)
            foreach ($fks as $fkCol => $refTable) {
                if (isset($fields[$fkCol])) continue;
                if (in_array($refTable, ['clubs', 'members'], true)) continue;
                if (str_contains($meta[$fkCol]['extra'] ?? '', 'generated')) continue;
                if (isset($insertedIds[$refTable]) && !empty($insertedIds[$refTable])) {
                    $parentIds = $insertedIds[$refTable];
                    $fields[$fkCol] = $parentIds[$i % count($parentIds)];
                } elseif ($meta[$fkCol]['nullable']) {
                    // pomijamy — generic auto-fill nie wpisze id 1 (brakuje rekordu)
                    continue;
                } else {
                    // Required FK do tabeli ktora nie zostala zaseedowana — skip wiersza
                    return $insertedRowIds;
                }
            }

            // Naming convention
            if (isset($cols['competition_name'])) $fields['competition_name'] = $eventName;
            if (isset($cols['competition_date'])) $fields['competition_date'] = $eventDate;
            if (isset($cols['event_name']))       $fields['event_name']       = $eventName;
            if (isset($cols['event_date']))       $fields['event_date']       = $eventDate;
            if (isset($cols['tournament_date']))  $fields['tournament_date']  = $eventDate;
            if (isset($cols['rating_date']))      $fields['rating_date']      = $eventDate;
            if (isset($cols['send_date']))        $fields['send_date']        = $eventDate;
            if (isset($cols['score_date']))       $fields['score_date']       = $eventDate;
            if (isset($cols['pr_date']))          $fields['pr_date']          = $eventDate;
            if (isset($cols['race_date']))        $fields['race_date']        = $eventDate;
            if (isset($cols['set_date']))         $fields['set_date']         = $eventDate;
            if (isset($cols['joined_at']))        $fields['joined_at']        = $eventDate;
            if (isset($cols['issue_date']))       $fields['issue_date']       = $eventDate;

            // Sport-specific name fields (parent-table tworzymy z meaningful nazwami)
            if (isset($cols['name']) && !$meta['name']['nullable']) {
                $fields['name'] = $this->demoName($table, $sportKey, $i);
            }
            if (isset($cols['movement']) && !$meta['movement']['nullable']) {
                $fields['movement'] = ['Snatch', 'Clean & Jerk', 'Deadlift', 'Squat', 'Bench Press'][$i % 5];
            }
            if (isset($cols['pr_value']) && !$meta['pr_value']['nullable']) {
                $fields['pr_value'] = (string)(100 + $i * 10);
            }
            if (isset($cols['score']) && ($meta['score']['data_type'] ?? '') === 'varchar' && !$meta['score']['nullable']) {
                $fields['score'] = (5 + $i) . ':' . (30 + $i * 5);
            }

            if (isset($cols['placement'])) $fields['placement'] = ($i % 6) + 1;
            if (isset($cols['place']))     $fields['place']     = ($i % 6) + 1;

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
                $insertedRowIds[] = (int)$db->lastInsertId();
            } catch (\Throwable) { continue; }
        }
        return $insertedRowIds;
    }

    private function demoName(string $table, string $sportKey, int $i): string
    {
        if (str_contains($table, 'route'))      return 'Demo Route #' . ($i + 1);
        if (str_contains($table, 'wod'))        return 'Demo WOD ' . ['Fran','Cindy','Murph','Helen','Mary'][$i % 5];
        if (str_contains($table, 'boat'))       return 'Demo Boat #' . ($i + 1);
        if (str_contains($table, 'race'))       return 'Demo Regata ' . ($i + 1);
        if (str_contains($table, 'tournament')) return 'Demo ' . ucfirst($sportKey) . ' Tournament ' . ($i + 1);
        return 'Demo ' . ucfirst($sportKey) . ' #' . ($i + 1);
    }

    private function numericDemoValue(string $colName, int $i): int|float
    {
        $n = strtolower($colName);
        if (str_contains($n, 'rating'))    return 1500 + $i * 50;  // ELO-ish
        if (str_contains($n, 'attempts'))  return 1 + $i;
        if (str_contains($n, 'distance_nm')) return 5.0 + $i * 1.0;
        if (str_contains($n, 'distance')) return 100;
        if (str_contains($n, 'time_seconds')) return 30.5 + $i * 5;
        if (str_contains($n, 'score'))     return 50 + $i * 5;
        if (str_contains($n, 'points'))    return 10 + $i;
        if (str_contains($n, 'length'))    return 7.5 + $i * 0.5;
        return 1;
    }
}
