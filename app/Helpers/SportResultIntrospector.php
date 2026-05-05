<?php

namespace App\Helpers;

use PDO;

/**
 * Helper introspekcji `<key>_results` tabel — uzywany przez generyczne
 * widoki admina (sport_result_show.php, sport_result_edit.php) by
 * uniknac duplikacji per-sport kodu CRUD.
 *
 * Dla danej tabeli (np. wrestling_results) zwraca:
 *   - listę kolumn z typem i metadanymi (label, input_type, options)
 *   - przyjazne etykiety (auto-generowane z column_name)
 *   - rozpoznaje ENUM, DATE, INT, DECIMAL, VARCHAR, TEXT
 *   - pomija technikalia: id, club_id, created_at, updated_at
 */
class SportResultIntrospector
{
    private const HIDDEN = ['id', 'club_id', 'created_at', 'updated_at', 'created_by'];

    public function __construct(
        private readonly PDO $db,
    ) {
    }

    /**
     * @return array<int, array{
     *   name: string, label: string, input_type: string, required: bool,
     *   options: array<string,string>|null, max_length: int|null, default: mixed
     * }>
     */
    public function fields(string $table): array
    {
        $stmt = $this->db->prepare(
            'SELECT column_name, is_nullable, column_default, column_type, data_type, character_maximum_length, extra
             FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ?
             ORDER BY ordinal_position'
        );
        $stmt->execute([$table]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $name = strtolower($row['COLUMN_NAME'] ?? $row['column_name']);
            if (in_array($name, self::HIDDEN, true)) continue;
            $extra = strtolower((string)($row['EXTRA'] ?? $row['extra']));
            if (str_contains($extra, 'generated')) continue;

            $type     = (string)($row['COLUMN_TYPE'] ?? $row['column_type']);
            $dataType = strtolower((string)($row['DATA_TYPE'] ?? $row['data_type']));
            $nullable = strtoupper((string)($row['IS_NULLABLE'] ?? $row['is_nullable'])) === 'YES';

            $field = [
                'name'       => $name,
                'label'      => $this->humanize($name),
                'input_type' => $this->mapInputType($dataType, $type, $name),
                'required'   => !$nullable,
                'options'    => null,
                'max_length' => $row['CHARACTER_MAXIMUM_LENGTH'] ?? $row['character_maximum_length'] ?? null,
                'default'    => $row['COLUMN_DEFAULT'] ?? $row['column_default'],
            ];

            if (stripos($type, 'enum(') === 0 && preg_match_all("/'([^']+)'/", $type, $m)) {
                $values = $m[1];
                $field['input_type'] = 'enum';
                $field['options']    = array_combine($values, $values);
            }
            $out[] = $field;
        }
        return $out;
    }

    /**
     * Pobiera jeden wiersz (tylko gdy nalezy do danego club_id) —
     * uzywane przez admin show()/edit().
     */
    public function findRow(string $table, int $id, ?int $clubId = null): ?array
    {
        $sql = "SELECT * FROM `{$table}` WHERE id = ?";
        $params = [$id];
        if ($clubId !== null) {
            $sql .= " AND club_id = ?";
            $params[] = $clubId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function mapInputType(string $dataType, string $columnType, string $name): string
    {
        if (str_contains($name, '_date') || $dataType === 'date') return 'date';
        if ($dataType === 'datetime' || $dataType === 'timestamp') return 'datetime-local';
        if ($dataType === 'text') return 'textarea';
        if (in_array($dataType, ['int','tinyint','smallint','mediumint','bigint'], true)) {
            // tinyint(1) z SHOW COLUMNS często jest boolean — wykryjmy
            if ($dataType === 'tinyint' && str_contains($columnType, '(1)')) return 'checkbox';
            return 'number';
        }
        if (in_array($dataType, ['decimal','float','double'], true)) return 'number_decimal';
        if (in_array($dataType, ['date'], true)) return 'date';
        return 'text';
    }

    private function humanize(string $columnName): string
    {
        // first_name → First name
        $s = str_replace('_', ' ', $columnName);
        return ucfirst($s);
    }
}
