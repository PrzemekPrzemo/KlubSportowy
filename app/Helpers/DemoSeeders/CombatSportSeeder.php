<?php

namespace App\Helpers\DemoSeeders;

use App\Helpers\Database;
use App\Sports\Support\BaseSportArchetype;
use App\Sports\Support\CombatSport;
use PDO;

/**
 * Generic seeder dla CombatSport-archetype (Boxing, Kickboxing, MMA,
 * Wrestling, Sambo, Aikido, Bjj, Fencing).
 *
 * Strategia: introspekcja schemy `<key>_results` + sport-specific tables
 * (`<key>_belts`, `<key>_fighters` jesli istnieja) i defensywny insert
 * minimalnych demo data.
 *
 * Tworzy:
 *   - 6 zawodnikow (members)
 *   - 6 rekordow w `<key>_results` (po jednym per zawodnik z rosnacym placement)
 *   - jesli istnieje `<key>_belts`: po 1 wpisie pasa per zawodnik
 *   - jesli istnieje `<key>_fighters`: po 1 wpisie fighter per zawodnik
 */
class CombatSportSeeder implements ArchetypeSeederInterface
{
    public function archetypeClass(): string
    {
        return CombatSport::class;
    }

    public function seed(int $clubId, BaseSportArchetype $archetype, array $counts = []): array
    {
        if (!($archetype instanceof CombatSport)) {
            return ['skipped' => 'wrong_archetype', 'expected' => CombatSport::class];
        }

        $defaults     = $archetype->defaultSeedCounts();
        $athleteCount = (int)($counts['athlete'] ?? $defaults['athlete'] ?? 6);
        $resultCount  = (int)($counts['result']  ?? $defaults['result']  ?? 4);

        $db  = Database::pdo();
        $key = $archetype->key();
        $created = ['athlete' => 0, 'result' => 0, 'belt' => 0, 'fighter' => 0];

        // 1. Czlonkowie klubu
        $memberIds = $this->seedMembers($db, $clubId, $athleteCount);
        $created['athlete'] = count($memberIds);
        if (empty($memberIds)) return ['created' => $created];

        // 2. Results table (najczesciej obecna: `<key>_results`)
        $resultsTable = "{$key}_results";
        if ($this->tableExists($db, $resultsTable)) {
            $created['result'] = $this->seedResults($db, $resultsTable, $clubId, $memberIds, $resultCount, $key);
        }

        // 3. Belts table (BJJ, Karate, Judo, Taekwondo, Aikido)
        $beltsTable = "{$key}_belts";
        if ($this->tableExists($db, $beltsTable)) {
            $created['belt'] = $this->seedBelts($db, $beltsTable, $clubId, $memberIds);
        }

        // 4. Fighters table (MMA, Boxing — separate fighter entity)
        $fightersTable = "{$key}_fighters";
        if ($this->tableExists($db, $fightersTable)) {
            $created['fighter'] = $this->seedFighters($db, $fightersTable, $clubId, $memberIds);
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
        if (preg_match("/^enum\('([^']+)'/i", $type, $m)) return $m[1];
        return null;
    }

    /** @return int[] */
    private function seedMembers(PDO $db, int $clubId, int $count): array
    {
        $names = [
            ['Adam',   'Wojciechowski'],
            ['Michał', 'Lewandowski'],
            ['Bartosz','Kowalczyk'],
            ['Łukasz', 'Zieliński'],
            ['Jakub',  'Szymański'],
            ['Karol',  'Kamiński'],
        ];
        $ids = [];
        for ($i = 0; $i < min($count, count($names)); $i++) {
            [$first, $last] = $names[$i];
            $stmt = $db->prepare(
                "INSERT INTO members (club_id, first_name, last_name, status, member_number, join_date)
                 VALUES (?, ?, ?, 'aktywny', ?, CURDATE())"
            );
            try {
                $stmt->execute([$clubId, $first, $last, 'COMB-' . sprintf('%03d', $i + 1)]);
                $ids[] = (int)$db->lastInsertId();
            } catch (\Throwable) { /* skip */ }
        }
        return $ids;
    }

    private function seedResults(PDO $db, string $table, int $clubId, array $memberIds, int $resultCount, string $sportKey): int
    {
        $cols = $this->cols($db, $table);
        $count = 0;
        for ($i = 0; $i < $resultCount && $i < count($memberIds); $i++) {
            $memberId = $memberIds[$i];
            $fields = [
                'club_id'          => $clubId,
                'member_id'        => $memberId,
                'competition_name' => 'Demo ' . ucfirst($sportKey) . ' Open ' . date('Y'),
                'competition_date' => date('Y-m-d', strtotime('-' . (5 + $i * 7) . ' days')),
            ];
            if (isset($cols['placement'])) $fields['placement'] = ($i % 4) + 1;
            // ENUM defaults
            foreach (['category', 'weight_class', 'age_category'] as $optional) {
                if (isset($cols[$optional])) {
                    $val = $this->firstEnumValue($db, $table, $optional);
                    if ($val !== null) $fields[$optional] = $val;
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

    private function seedBelts(PDO $db, string $table, int $clubId, array $memberIds): int
    {
        $cols = $this->cols($db, $table);
        if (!isset($cols['member_id'])) return 0;
        $count = 0;
        $beltColors = ['biały', 'żółty', 'pomarańczowy', 'zielony', 'niebieski', 'brązowy'];
        foreach ($memberIds as $i => $memberId) {
            $fields = ['club_id' => $clubId, 'member_id' => $memberId];
            if (isset($cols['belt_color'])) $fields['belt_color'] = $beltColors[$i % count($beltColors)];
            elseif (isset($cols['color']))  $fields['color']      = $beltColors[$i % count($beltColors)];
            if (isset($cols['grade']))      $fields['grade']      = '5 kyu';
            if (isset($cols['awarded_at'])) $fields['awarded_at'] = date('Y-m-d', strtotime('-' . (90 + $i * 30) . ' days'));
            elseif (isset($cols['awarded_date'])) $fields['awarded_date'] = date('Y-m-d', strtotime('-' . (90 + $i * 30) . ' days'));

            if (count($fields) <= 2) continue; // tylko club_id+member_id, brak useful kolumn

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

    private function seedFighters(PDO $db, string $table, int $clubId, array $memberIds): int
    {
        $cols = $this->cols($db, $table);
        if (!isset($cols['member_id'])) return 0;
        $count = 0;
        foreach ($memberIds as $i => $memberId) {
            $fields = ['club_id' => $clubId, 'member_id' => $memberId];
            if (isset($cols['weight_class'])) {
                $val = $this->firstEnumValue($db, $table, 'weight_class');
                $fields['weight_class'] = $val ?? 'open';
            }
            if (isset($cols['stance'])) {
                $val = $this->firstEnumValue($db, $table, 'stance');
                $fields['stance'] = $val ?? 'orthodox';
            }
            if (isset($cols['style'])) {
                $val = $this->firstEnumValue($db, $table, 'style');
                $fields['style'] = $val ?? 'mixed';
            }
            if (count($fields) <= 2) continue;

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
}
