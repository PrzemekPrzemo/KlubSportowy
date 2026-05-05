<?php

namespace App\Helpers\DemoSeeders;

use App\Helpers\Database;
use App\Sports\Support\BaseSportArchetype;
use App\Sports\Support\ScoringSport;
use PDO;

/**
 * Generic seeder dla ScoringSport-archetype (FigureSkating, Gymnastics,
 * DanceSport).
 *
 * Strategia identyczna jak TimingSportSeeder: introspekcja kolumn +
 * auto-fill required cols. Dodatkowo obsluguje DanceSport-quirk:
 * `dance_results` uzywa `leader_id` (nie `member_id`).
 *
 * Auto-fill heurystyki dla numeric NOT NULL (po difficulty_score itp.):
 *   - score/points → 8.5 + i*0.3  (sensowny range 8.0-10.0 dla scoring sports)
 *   - inne numeric → 1
 */
class ScoringSportSeeder implements ArchetypeSeederInterface
{
    public function archetypeClass(): string
    {
        return ScoringSport::class;
    }

    public function seed(int $clubId, BaseSportArchetype $archetype, array $counts = []): array
    {
        if (!($archetype instanceof ScoringSport)) {
            return ['skipped' => 'wrong_archetype', 'expected' => ScoringSport::class];
        }

        $defaults     = $archetype->defaultSeedCounts();
        $athleteCount = (int)($counts['athlete'] ?? $defaults['athlete'] ?? 6);
        $resultCount  = (int)($counts['result']  ?? $defaults['result']  ?? 6);

        $db  = Database::pdo();
        $created = ['athlete' => 0, 'result' => 0];

        $memberIds = $this->seedMembers($db, $clubId, $athleteCount);
        $created['athlete'] = count($memberIds);
        if (empty($memberIds)) return ['created' => $created];

        $tables = $archetype->tables();
        $resultsTable = null;
        foreach ($tables as $t) {
            if (str_ends_with($t, '_results')) { $resultsTable = $t; break; }
        }
        if ($resultsTable === null && !empty($tables)) {
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
            ['Aleksandra', 'Mazur'],
            ['Patryk',     'Kaczmarek'],
            ['Natalia',    'Pawlak'],
            ['Bartłomiej', 'Górski'],
            ['Zofia',      'Adamska'],
            ['Damian',     'Sikora'],
        ];
        $ids = [];
        for ($i = 0; $i < min($count, count($names)); $i++) {
            [$first, $last] = $names[$i];
            $stmt = $db->prepare(
                "INSERT INTO members (club_id, first_name, last_name, status, member_number, join_date)
                 VALUES (?, ?, ?, 'aktywny', ?, CURDATE())"
            );
            try {
                $stmt->execute([$clubId, $first, $last, 'SCO-' . sprintf('%03d', $i + 1)]);
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

            $fields = ['club_id' => $clubId];

            // member_id (figure_skating, gymnastics) vs leader_id (dance_results)
            if (isset($cols['member_id']))     $fields['member_id']     = $memberId;
            elseif (isset($cols['leader_id'])) $fields['leader_id']     = $memberId;

            // Competition vs event naming
            if (isset($cols['competition_name'])) $fields['competition_name'] = $eventName;
            if (isset($cols['competition_date'])) $fields['competition_date'] = $eventDate;
            if (isset($cols['event_name']))       $fields['event_name']       = $eventName;
            if (isset($cols['event_date']))       $fields['event_date']       = $eventDate;

            // Placement
            if (isset($cols['placement'])) $fields['placement'] = ($i % 6) + 1;
            if (isset($cols['place']))     $fields['place']     = ($i % 6) + 1;

            // Wszystkie required NOT NULL bez default — auto-fill (pomin GENERATED).
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
        if (str_contains($n, 'difficulty') || str_contains($n, 'execution') || str_contains($n, 'penalty')) {
            return 8.5;
        }
        if (str_contains($n, 'score') || str_contains($n, 'points') || str_contains($n, 'tes') || str_contains($n, 'pcs')) {
            return 8.0 + ($i % 5) * 0.3;
        }
        return 1;
    }
}
