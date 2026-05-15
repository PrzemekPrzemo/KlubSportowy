<?php

namespace App\Helpers;

use PDO;

/**
 * Generic introspekcja schematu MySQL dla generycznego CRUD scaffoldingu sportów.
 *
 * Używany przez SportModuleController do dynamicznego renderowania list/formularzy
 * dla 40+ sportów które nie mają dedykowanych controllerów (Judo, Karate,
 * Kickboxing, Boxing, Mma, Bjj, Sambo, Wrestling, …).
 *
 * Bezpieczeństwo:
 *   - Nazwa tabeli MUSI pochodzić z whitelisty `sport_module_resources`
 *     (nigdy z user input). Backticki bronią przed identifier-injection,
 *     ale validację robi caller (SportModuleController).
 *   - Wszystkie wartości w queryach idą przez prepared statements.
 *
 * Różnica vs SportResultIntrospector:
 *   - SportResultIntrospector dotyczy wyłącznie `<sport>_results` z member_id.
 *   - Ta klasa wykrywa FK do `members`, `clubs`, oraz dowolnej tabeli per-sport
 *     (np. `judo_belts.id` → `judo_member_grades.belt_id`).
 */
class SportTableIntrospector
{
    /**
     * Kolumny które są techniczne i nigdy nie pokazujemy w formularzu.
     * club_id wstawiamy automatycznie z ClubContext.
     */
    private const HIDDEN_IN_FORM = ['id', 'club_id', 'created_at', 'updated_at', 'created_by'];

    public function __construct(private readonly PDO $db) {}

    /**
     * Zwraca listę kolumn z meta (typ, label, FK target, opcje ENUM).
     *
     * @return array<int, array{
     *   name: string,
     *   label: string,
     *   input_type: string,
     *   required: bool,
     *   options: array<string,string>|null,
     *   max_length: int|null,
     *   default: mixed,
     *   fk_table: string|null,
     *   fk_column: string|null,
     *   is_primary: bool,
     *   is_hidden_in_form: bool,
     * }>
     */
    public function fields(string $table): array
    {
        // Pobierz kolumny
        $stmt = $this->db->prepare(
            'SELECT column_name, is_nullable, column_default, column_type, data_type,
                    character_maximum_length, extra, column_key, column_comment
             FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ?
             ORDER BY ordinal_position'
        );
        $stmt->execute([$table]);
        $rows = $stmt->fetchAll();

        $fks = $this->foreignKeys($table);

        $out = [];
        foreach ($rows as $row) {
            $name = strtolower((string)($row['COLUMN_NAME'] ?? $row['column_name']));
            $extra = strtolower((string)($row['EXTRA'] ?? $row['extra']));
            if (str_contains($extra, 'generated')) continue;

            $type     = (string)($row['COLUMN_TYPE'] ?? $row['column_type']);
            $dataType = strtolower((string)($row['DATA_TYPE'] ?? $row['data_type']));
            $nullable = strtoupper((string)($row['IS_NULLABLE'] ?? $row['is_nullable'])) === 'YES';
            $colKey   = strtoupper((string)($row['COLUMN_KEY'] ?? $row['column_key']));
            $comment  = (string)($row['COLUMN_COMMENT'] ?? $row['column_comment'] ?? '');

            $fkTable  = $fks[$name]['table']  ?? null;
            $fkColumn = $fks[$name]['column'] ?? null;

            $inputType = $this->mapInputType($dataType, $type, $name, $fkTable);

            $field = [
                'name'              => $name,
                'label'             => $this->humanize($name, $comment),
                'input_type'        => $inputType,
                'required'          => !$nullable && (($row['COLUMN_DEFAULT'] ?? null) === null),
                'options'           => null,
                'max_length'        => $row['CHARACTER_MAXIMUM_LENGTH'] ?? $row['character_maximum_length'] ?? null,
                'default'           => $row['COLUMN_DEFAULT'] ?? $row['column_default'],
                'fk_table'          => $fkTable,
                'fk_column'         => $fkColumn,
                'is_primary'        => $colKey === 'PRI',
                'is_hidden_in_form' => in_array($name, self::HIDDEN_IN_FORM, true) || $colKey === 'PRI',
            ];

            if (stripos($type, 'enum(') === 0 && preg_match_all("/'([^']+)'/", $type, $m)) {
                $values = $m[1];
                $field['input_type'] = 'enum';
                $field['options']    = array_combine($values, $values);
            } elseif (stripos($type, 'set(') === 0 && preg_match_all("/'([^']+)'/", $type, $m)) {
                $values = $m[1];
                $field['input_type'] = 'text'; // SET — uproszczone: traktuj jak tekst CSV
                $field['options']    = array_combine($values, $values);
            }

            $out[] = $field;
        }
        return $out;
    }

    /**
     * Klucz główny tabeli (zwykle 'id').
     */
    public function primaryKey(string $table): string
    {
        $stmt = $this->db->prepare(
            "SELECT column_name FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_key = 'PRI'
             ORDER BY ordinal_position LIMIT 1"
        );
        $stmt->execute([$table]);
        $row = $stmt->fetch();
        return strtolower((string)($row['COLUMN_NAME'] ?? $row['column_name'] ?? 'id')) ?: 'id';
    }

    /**
     * Czy tabela ma kolumnę club_id (większość per-sport tabel ma).
     */
    public function hasClubScope(string $table): bool
    {
        return $this->columnExists($table, 'club_id');
    }

    /**
     * Czy kolumna istnieje w tabeli.
     */
    public function columnExists(string $table, string $column): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
             LIMIT 1'
        );
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Mapa column_name → ['table' => referenced_table, 'column' => referenced_column].
     *
     * @return array<string, array{table:string, column:string}>
     */
    public function foreignKeys(string $table): array
    {
        $stmt = $this->db->prepare(
            'SELECT column_name, referenced_table_name, referenced_column_name
             FROM information_schema.key_column_usage
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND referenced_table_name IS NOT NULL'
        );
        $stmt->execute([$table]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $col = strtolower((string)($row['COLUMN_NAME'] ?? $row['column_name']));
            $ref = (string)($row['REFERENCED_TABLE_NAME'] ?? $row['referenced_table_name']);
            $refCol = (string)($row['REFERENCED_COLUMN_NAME'] ?? $row['referenced_column_name']);
            $out[$col] = ['table' => $ref, 'column' => $refCol];
        }
        return $out;
    }

    /**
     * Mapuje DB type → HTML input type.
     */
    private function mapInputType(string $dataType, string $columnType, string $name, ?string $fkTable): string
    {
        if ($fkTable === 'members')                 return 'member_picker';
        if ($fkTable !== null && $fkTable !== 'clubs') return 'fk_select';
        if ($dataType === 'date')                   return 'date';
        if ($dataType === 'datetime' || $dataType === 'timestamp') return 'datetime-local';
        if ($dataType === 'time')                   return 'time';
        if ($dataType === 'text' || $dataType === 'longtext' || $dataType === 'mediumtext') return 'textarea';
        if ($dataType === 'json')                   return 'textarea_json';
        if ($dataType === 'blob' || $dataType === 'longblob' || $dataType === 'mediumblob') return 'blob_skip';
        if (in_array($dataType, ['int','tinyint','smallint','mediumint','bigint'], true)) {
            if ($dataType === 'tinyint' && str_contains($columnType, '(1)')) return 'checkbox';
            return 'number';
        }
        if (in_array($dataType, ['decimal','float','double','numeric'], true)) return 'number_decimal';
        return 'text';
    }

    /**
     * column_name → "Etykieta po polsku" lub Human Readable.
     *
     * Strategia:
     *   1) Jeśli column_comment niepusty — użyj fragmentu (do pierwszej kropki).
     *   2) Dopasowanie z mapy popularnych nazw (member_id → Zawodnik).
     *   3) Konwersja snake_case → "Snake Case".
     */
    private function humanize(string $columnName, string $comment = ''): string
    {
        static $map = [
            'member_id'      => 'Zawodnik',
            'club_id'        => 'Klub',
            'belt_id'        => 'Pas / Stopień',
            'weight_class_id'=> 'Kategoria wagowa',
            'team_id'        => 'Drużyna',
            'season_id'      => 'Sezon',
            'league_id'      => 'Liga',
            'name'           => 'Nazwa',
            'code'           => 'Kod',
            'color'          => 'Kolor',
            'notes'          => 'Notatki',
            'rank_order'     => 'Kolejność',
            'gender'         => 'Płeć',
            'age_category'   => 'Kategoria wiekowa',
            'weight_min_kg'  => 'Waga min. (kg)',
            'weight_max_kg'  => 'Waga max. (kg)',
            'achieved_at'    => 'Data osiągnięcia',
            'granted_date'   => 'Data nadania',
            'date'           => 'Data',
            'start_date'     => 'Data początkowa',
            'end_date'       => 'Data końcowa',
            'examiner'       => 'Egzaminator',
            'location'       => 'Miejsce',
            'description'    => 'Opis',
            'is_active'      => 'Aktywny',
            'placement'      => 'Miejsce',
            'score'          => 'Wynik',
            'points'         => 'Punkty',
            'time_seconds'   => 'Czas (s)',
            'distance_m'     => 'Dystans (m)',
            'category'       => 'Kategoria',
            'competition_name' => 'Nazwa zawodów',
            'competition_date' => 'Data zawodów',
        ];

        if (isset($map[$columnName])) {
            return $map[$columnName];
        }

        // column_comment override — pierwszy fragment do kropki / 80 znaków
        if ($comment !== '') {
            $c = trim(strtok($comment, ".\n"));
            if ($c !== '' && mb_strlen($c) <= 80) {
                return $c;
            }
        }

        return ucfirst(str_replace('_', ' ', $columnName));
    }
}
