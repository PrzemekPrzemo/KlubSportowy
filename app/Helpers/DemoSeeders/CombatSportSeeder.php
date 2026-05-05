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

    /**
     * Zwraca metadane wszystkich kolumn: nazwa => [is_nullable, has_default, type].
     * Pozwala wykryc wymagane (NOT NULL + brak DEFAULT) kolumny ktore musimy
     * jawnie wypelnic w INSERT.
     */
    private function colsMeta(PDO $db, string $table): array
    {
        $stmt = $db->prepare(
            'SELECT column_name, is_nullable, column_default, column_type
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
            ];
        }
        return $out;
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
        $meta  = $this->colsMeta($db, $table);
        $cols  = array_fill_keys(array_keys($meta), true);
        $count = 0;

        $eventName = 'Demo ' . ucfirst($sportKey) . ' Open ' . date('Y');

        for ($i = 0; $i < $resultCount && $i < count($memberIds); $i++) {
            $memberId = $memberIds[$i];
            $eventDate = date('Y-m-d', strtotime('-' . (5 + $i * 7) . ' days'));

            $fields = [
                'club_id'   => $clubId,
                'member_id' => $memberId,
            ];
            // Competition vs event naming convention — niektore sporty (Boxing,
            // Wrestling, Sambo, Aikido, Fencing) maja `competition_name/date`,
            // inne (Kickboxing, MMA, BJJ) maja `event_name/date`.
            if (isset($cols['competition_name'])) $fields['competition_name'] = $eventName;
            if (isset($cols['competition_date'])) $fields['competition_date'] = $eventDate;
            if (isset($cols['event_name']))       $fields['event_name']       = $eventName;
            if (isset($cols['event_date']))       $fields['event_date']       = $eventDate;

            if (isset($cols['placement'])) $fields['placement'] = ($i % 4) + 1;

            // Wymagane ENUMy bez default (np. kickboxing.style, sambo.style)
            // — wykryj automatycznie i wypelnij pierwsza wartoscia ENUM.
            foreach ($meta as $name => $m) {
                if (isset($fields[$name])) continue;
                $isEnum = stripos($m['type'], 'enum(') === 0;
                if (!$isEnum) continue;
                $required = !$m['nullable'] && $m['default'] === null;
                if ($required || in_array($name, ['category', 'weight_class', 'age_category'], true)) {
                    $val = $this->firstEnumValue($db, $table, $name);
                    if ($val !== null) $fields[$name] = $val;
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
        $meta = $this->colsMeta($db, $table);
        $cols = array_fill_keys(array_keys($meta), true);
        if (!isset($cols['member_id'])) return 0;
        $count = 0;

        foreach ($memberIds as $i => $memberId) {
            $fields = ['club_id' => $clubId, 'member_id' => $memberId];

            // Date column — kickboxing/bjj uzywaja exam_date, sambo/aikido
            // granted_date, niektore mialy awarded_at/awarded_date.
            $dateVal = date('Y-m-d', strtotime('-' . (90 + $i * 30) . ' days'));
            foreach (['exam_date', 'granted_date', 'awarded_date', 'awarded_at'] as $dc) {
                if (isset($cols[$dc])) { $fields[$dc] = $dateVal; break; }
            }

            // Wszystkie wymagane (NOT NULL bez default) ENUMy — wypelnij pierwsza
            // wartoscia ENUM. Pokrywa belt_color, belt_level, style, gi itp.
            foreach ($meta as $name => $m) {
                if (isset($fields[$name])) continue;
                $isEnum    = stripos($m['type'], 'enum(') === 0;
                $required  = !$m['nullable'] && $m['default'] === null;
                if ($isEnum && $required) {
                    $val = $this->firstEnumValue($db, $table, $name);
                    if ($val !== null) $fields[$name] = $val;
                }
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

    private function seedFighters(PDO $db, string $table, int $clubId, array $memberIds): int
    {
        $meta = $this->colsMeta($db, $table);
        $cols = array_fill_keys(array_keys($meta), true);
        if (!isset($cols['member_id'])) return 0;
        $count = 0;
        foreach ($memberIds as $i => $memberId) {
            $fields = ['club_id' => $clubId, 'member_id' => $memberId];

            // Wszystkie wymagane (NOT NULL bez default) ENUMy — wypelnij,
            // poniewaz UNIQUE KEY (club_id, member_id) wymusza max 1 rekord
            // per zawodnik wiec mozemy raz wstawic minimalny set.
            foreach ($meta as $name => $m) {
                if (isset($fields[$name])) continue;
                $isEnum   = stripos($m['type'], 'enum(') === 0;
                $required = !$m['nullable'] && $m['default'] === null;
                if ($isEnum && $required) {
                    $val = $this->firstEnumValue($db, $table, $name);
                    if ($val !== null) $fields[$name] = $val;
                }
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
