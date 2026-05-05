<?php

namespace App\Helpers\DemoSeeders;

use App\Helpers\Database;
use App\Sports\Support\BaseSportArchetype;
use App\Sports\Support\TimingSport;
use PDO;

/**
 * Generic seeder dla TimingSport-archetype (Swimming, Cycling, Triathlon,
 * Biathlon, Kayaking, Rowing, AlpineSki, XcSki, SkiJump, Snowboard).
 *
 * Strategia: introspekcja schemy `<archetype.tables()[*]>` (zwykle
 * `<key>_results` lub varianty np. `kayak_results`, `alpine_ski_results`)
 * i defensywny insert minimalnych demo data.
 *
 * Uniwersalny dla 10 sportow wytrzymalosciowych — pokrywa rozne konwencje:
 *   - competition_name/_date vs event_name/_date vs score_date (Swimming)
 *   - required ENUMy bez default (np. swimming.stroke, alpineski.discipline)
 *   - required NUMERIC NOT NULL (np. distance_m, time_ms, distance_km)
 *
 * Auto-fill heurystyki dla numeric NOT NULL:
 *   - <col>_ms       → 60000  (1 minuta)
 *   - <col>_s        → 60
 *   - distance_m     → 1500
 *   - distance_km    → 5.0
 *   - inne numeric   → 1
 */
class TimingSportSeeder implements ArchetypeSeederInterface
{
    public function archetypeClass(): string
    {
        return TimingSport::class;
    }

    public function seed(int $clubId, BaseSportArchetype $archetype, array $counts = []): array
    {
        if (!($archetype instanceof TimingSport)) {
            return ['skipped' => 'wrong_archetype', 'expected' => TimingSport::class];
        }

        $defaults     = $archetype->defaultSeedCounts();
        $athleteCount = (int)($counts['athlete'] ?? $defaults['athlete'] ?? 8);
        $resultCount  = (int)($counts['result']  ?? $defaults['result']  ?? 8);

        $db  = Database::pdo();
        $created = ['athlete' => 0, 'result' => 0];

        // 1. Members
        $memberIds = $this->seedMembers($db, $clubId, $athleteCount);
        $created['athlete'] = count($memberIds);
        if (empty($memberIds)) return ['created' => $created];

        // 2. Find results table — w archetype.tables() szukamy konczonego _results
        $tables       = $archetype->tables();
        $resultsTable = null;
        foreach ($tables as $t) {
            if (str_ends_with($t, '_results')) { $resultsTable = $t; break; }
        }
        if ($resultsTable === null && !empty($tables)) {
            // fallback: ostatnia tabela archetypu
            $resultsTable = end($tables);
        }

        if ($resultsTable !== null && $this->tableExists($db, $resultsTable)) {
            $created['result'] = $this->seedResults(
                $db, $resultsTable, $clubId, $memberIds, $resultCount, $archetype->key()
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
            'SELECT column_name, is_nullable, column_default, column_type, data_type
             FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$table]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $name = strtolower($row['COLUMN_NAME'] ?? $row['column_name']);
            $out[$name] = [
                'nullable' => strtoupper((string)($row['IS_NULLABLE'] ?? $row['is_nullable'])) === 'YES',
                'default'  => $row['COLUMN_DEFAULT'] ?? $row['column_default'],
                'type'     => (string)($row['COLUMN_TYPE'] ?? $row['column_type']),
                'data_type' => strtolower((string)($row['DATA_TYPE'] ?? $row['data_type'])),
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
            ['Anna',     'Nowak'],
            ['Piotr',    'Kowalski'],
            ['Magdalena','Wiśniewska'],
            ['Tomasz',   'Wójcik'],
            ['Katarzyna','Kowalska'],
            ['Marek',    'Krawczyk'],
            ['Joanna',   'Mazur'],
            ['Krzysztof','Lewandowski'],
        ];
        $ids = [];
        for ($i = 0; $i < min($count, count($names)); $i++) {
            [$first, $last] = $names[$i];
            $stmt = $db->prepare(
                "INSERT INTO members (club_id, first_name, last_name, status, member_number, join_date)
                 VALUES (?, ?, ?, 'aktywny', ?, CURDATE())"
            );
            try {
                $stmt->execute([$clubId, $first, $last, 'TIM-' . sprintf('%03d', $i + 1)]);
                $ids[] = (int)$db->lastInsertId();
            } catch (\Throwable) { /* skip */ }
        }
        return $ids;
    }

    private function seedResults(PDO $db, string $table, int $clubId, array $memberIds, int $resultCount, string $sportKey): int
    {
        $meta  = $this->colsMeta($db, $table);
        $cols  = array_fill_keys(array_keys($meta), true);
        $count = 0;

        $eventName = 'Demo ' . ucfirst($sportKey) . ' Cup ' . date('Y');

        for ($i = 0; $i < $resultCount && $i < count($memberIds); $i++) {
            $memberId  = $memberIds[$i];
            $eventDate = date('Y-m-d', strtotime('-' . (5 + $i * 4) . ' days'));

            $fields = [
                'club_id'   => $clubId,
                'member_id' => $memberId,
            ];

            // Naming convention: competition vs event vs score (Swimming)
            if (isset($cols['competition_name'])) $fields['competition_name'] = $eventName;
            if (isset($cols['competition_date'])) $fields['competition_date'] = $eventDate;
            if (isset($cols['event_name']))       $fields['event_name']       = $eventName;
            if (isset($cols['event_date']))       $fields['event_date']       = $eventDate;
            if (isset($cols['score_date']))       $fields['score_date']       = $eventDate;
            if (isset($cols['race_date']))        $fields['race_date']        = $eventDate;

            // Placement ranking column
            if (isset($cols['placement'])) $fields['placement'] = ($i % 8) + 1;
            if (isset($cols['place']))     $fields['place']     = ($i % 8) + 1;

            // Wszystkie required ENUMy + numeric NOT NULL bez default —
            // wypelnij sensownymi wartosciami demo.
            foreach ($meta as $name => $m) {
                if (isset($fields[$name])) continue;
                if ($m['nullable'] || $m['default'] !== null) continue;

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

    /**
     * Heurystyka demo dla numeric NOT NULL kolumny — pasuje zarowno do
     * dystansow (m/km), czasow (ms/s), jak i wagi/punktow.
     */
    private function numericDemoValue(string $colName, int $i): int|float
    {
        $n = strtolower($colName);
        if (str_ends_with($n, '_ms') || str_contains($n, 'time_ms'))           return 60000 + $i * 250;
        if (str_ends_with($n, '_s')  || str_contains($n, 'time_s'))            return 60 + $i * 5;
        if (str_contains($n, 'distance_m') && !str_contains($n, '_min'))       return 1500;
        if (str_contains($n, 'distance_km'))                                   return 5.0 + $i * 0.5;
        if (str_contains($n, 'distance'))                                      return 100;
        if (str_contains($n, 'points'))                                        return 50.0;
        if (str_contains($n, 'score'))                                         return 80.0;
        return 1;
    }
}
