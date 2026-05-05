<?php

namespace App\Helpers\DemoSeeders;

use App\Helpers\Database;
use App\Sports\Support\BaseSportArchetype;
use App\Sports\Support\StrengthSport;
use PDO;

/**
 * Generic seeder dla StrengthSport-archetype (Weightlifting, Powerlifting).
 *
 * Schema obu sportow jest spójna:
 *   <key>_results — competition_name/_date, weight_class, body_weight,
 *                   <lift>_best (snatch/cleanjerk dla WL,
 *                   squat/bench/deadlift dla PL), total, sinclair/wilks
 *   <key>_records — record per member: lift ENUM, weight_kg, set_at DATE
 *
 * Auto-fill heurystyki dla numeric:
 *   - body_weight    → 75.0 + i*2  (kg)
 *   - snatch_best    → 100 + i*5
 *   - cleanjerk_best → 130 + i*5
 *   - squat_best     → 150 + i*10
 *   - bench_best     → 100 + i*5
 *   - deadlift_best  → 180 + i*10
 *   - total          → 380-450
 *   - weight_kg/value_kg → 100 + i*10
 *   - sinclair/wilks → 100.0+
 *   - inne numeric   → 1
 */
class StrengthSportSeeder implements ArchetypeSeederInterface
{
    public function archetypeClass(): string
    {
        return StrengthSport::class;
    }

    public function seed(int $clubId, BaseSportArchetype $archetype, array $counts = []): array
    {
        if (!($archetype instanceof StrengthSport)) {
            return ['skipped' => 'wrong_archetype', 'expected' => StrengthSport::class];
        }

        $defaults     = $archetype->defaultSeedCounts();
        $athleteCount = (int)($counts['athlete'] ?? $defaults['athlete'] ?? 5);
        $resultCount  = (int)($counts['result']  ?? $defaults['result']  ?? 5);

        $db  = Database::pdo();
        $created = ['athlete' => 0, 'result' => 0];

        $memberIds = $this->seedMembers($db, $clubId, $athleteCount);
        $created['athlete'] = count($memberIds);
        if (empty($memberIds)) return ['created' => $created];

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
            ['Adrian',   'Zieliński'],
            ['Arkadiusz','Michalski'],
            ['Kornel',   'Włodarczyk'],
            ['Igor',     'Sokołowski'],
            ['Mateusz',  'Wróbel'],
        ];
        $ids = [];
        for ($i = 0; $i < min($count, count($names)); $i++) {
            [$first, $last] = $names[$i];
            $stmt = $db->prepare(
                "INSERT INTO members (club_id, first_name, last_name, status, member_number, join_date)
                 VALUES (?, ?, ?, 'aktywny', ?, CURDATE())"
            );
            try {
                $stmt->execute([$clubId, $first, $last, 'STR-' . sprintf('%03d', $i + 1)]);
                $ids[] = (int)$db->lastInsertId();
            } catch (\Throwable) { /* skip */ }
        }
        return $ids;
    }

    private function seedTable(PDO $db, string $table, int $clubId, array $memberIds, int $resultCount, string $sportKey): int
    {
        $meta = $this->colsMeta($db, $table);
        $cols = array_fill_keys(array_keys($meta), true);
        if (!isset($cols['member_id'])) return 0;

        $count = 0;
        $weightClasses = ['M73', 'M81', 'M89', 'M96', 'M102'];

        for ($i = 0; $i < $resultCount && $i < count($memberIds); $i++) {
            $eventDate = date('Y-m-d', strtotime('-' . (5 + $i * 14) . ' days'));
            $eventName = 'Demo ' . ucfirst($sportKey) . ' Cup ' . date('Y');

            $fields = [
                'club_id'   => $clubId,
                'member_id' => $memberIds[$i],
            ];

            // Naming convention
            if (isset($cols['competition_name'])) $fields['competition_name'] = $eventName;
            if (isset($cols['competition_date'])) $fields['competition_date'] = $eventDate;
            if (isset($cols['event_name']))       $fields['event_name']       = $eventName;
            if (isset($cols['event_date']))       $fields['event_date']       = $eventDate;
            if (isset($cols['set_at']))           $fields['set_at']           = $eventDate;
            if (isset($cols['set_date']))         $fields['set_date']         = $eventDate;

            // weight_class is VARCHAR REQUIRED — uzyj realistic value
            if (isset($cols['weight_class']) && !$meta['weight_class']['nullable']) {
                $fields['weight_class'] = $weightClasses[$i % count($weightClasses)];
            }

            if (isset($cols['placement'])) $fields['placement'] = ($i % 5) + 1;

            // Required NOT NULL bez default — heurystyka per kolumna.
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
        if (str_contains($n, 'body_weight'))     return 75.0 + $i * 2;
        if (str_contains($n, 'snatch'))          return 100.0 + $i * 5;
        if (str_contains($n, 'cleanjerk') || str_contains($n, 'jerk')) return 130.0 + $i * 5;
        if (str_contains($n, 'squat'))           return 150.0 + $i * 10;
        if (str_contains($n, 'bench'))           return 100.0 + $i * 5;
        if (str_contains($n, 'deadlift'))        return 180.0 + $i * 10;
        if (str_contains($n, 'total'))           return 400.0 + $i * 15;
        if (str_contains($n, 'value_kg') || str_contains($n, 'weight_kg')) return 100.0 + $i * 10;
        if (str_contains($n, 'sinclair') || str_contains($n, 'wilks') || str_contains($n, 'ipf_gl')) return 100.0 + $i * 5;
        return 1;
    }
}
