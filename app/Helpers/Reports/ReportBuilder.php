<?php

declare(strict_types=1);

namespace App\Helpers\Reports;

use App\Helpers\Database;
use PDO;
use RuntimeException;

/**
 * ReportBuilder — wykonuje raporty zdefiniowane przez user-config JSON.
 *
 * BEZPIECZEŃSTWO (THE WHOLE POINT):
 *   - Zero raw SQL z user input.
 *   - data_source MUSI być w DataSourceRegistry.
 *   - Każda kolumna / pole filtra / pole agregacji jest sprawdzane przez registry.
 *     Nieznane = wyjątek InvalidConfigException.
 *   - Operatory whitelistowane (DataSourceRegistry::allowedOperators()).
 *   - Funkcje agregujące whitelistowane.
 *   - Wartości filtrów ZAWSZE bindowane przez prepared statements (PDO::execute).
 *   - LIMIT max 10 000 wierszy.
 *   - IN/NOT IN max 50 wartości.
 *   - Multi-tenant: tenant_column = :club_id auto-applied.
 *
 * Config schema (JSON):
 * {
 *   "columns":      ["col1","col2"],
 *   "filters":      [{"field":"col","op":"=","value":"x"}, {"field":"col","op":"IN","value":[1,2]}],
 *   "group_by":     ["col"],
 *   "aggregations": [{"field":"amount","fn":"sum","alias":"total"}],
 *   "order_by":     [{"field":"col","dir":"asc"}],
 *   "limit":        1000,
 *   "chart":        {"type":"bar","x":"col","y":"total"}
 * }
 */
final class ReportBuilder
{
    public const MAX_LIMIT      = 10000;
    public const DEFAULT_LIMIT  = 1000;
    public const MAX_IN_VALUES  = 50;

    /**
     * Wykonuje raport. Zwraca:
     * [
     *   'columns'    => [[key, label, type], ...],
     *   'rows'       => [[col1=>v1,...], ...],
     *   'total'      => int,
     *   'chart_data' => null|array,
     *   'duration_ms'=> int,
     *   'sql_debug'  => string (tylko w trybie debug)
     * ]
     *
     * @param int                  $clubId
     * @param string               $dataSource klucz z registry
     * @param array<string, mixed> $config     specyfikacja raportu
     * @return array<string, mixed>
     */
    public function execute(int $clubId, string $dataSource, array $config): array
    {
        $src = DataSourceRegistry::get($dataSource);
        if ($src === null) {
            throw new InvalidConfigException("Nieznane źródło danych: {$dataSource}");
        }

        $startMs = (int)(microtime(true) * 1000);

        $sqlData = $this->buildSql($clubId, $src, $config);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare($sqlData['sql']);
        foreach ($sqlData['bindings'] as $k => $v) {
            $type = match (true) {
                is_int($v)  => PDO::PARAM_INT,
                is_bool($v) => PDO::PARAM_BOOL,
                is_null($v) => PDO::PARAM_NULL,
                default     => PDO::PARAM_STR,
            };
            $stmt->bindValue($k, $v, $type);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $duration = ((int)(microtime(true) * 1000)) - $startMs;

        $columnsMeta = $this->buildColumnsMeta($src, $config);
        $chartData   = $this->buildChartData($config, $rows);

        return [
            'columns'     => $columnsMeta,
            'rows'        => $rows,
            'total'       => count($rows),
            'chart_data'  => $chartData,
            'duration_ms' => $duration,
            'sql_debug'   => $sqlData['sql'],
        ];
    }

    /**
     * Buduje SQL z whitelistowanego configu. Zwraca [sql, bindings].
     *
     * @param array<string, mixed> $src
     * @param array<string, mixed> $config
     * @return array{sql: string, bindings: array<string, mixed>}
     */
    public function buildSql(int $clubId, array $src, array $config): array
    {
        $bindings = [':club_id' => $clubId];
        $paramCounter = 0;

        // ── COLUMNS + AGGREGATIONS ────────────────────────────
        $selectParts = [];
        $columns = $this->sanitizeStringList($config['columns'] ?? []);
        $aggregations = $config['aggregations'] ?? [];
        $groupBy = $this->sanitizeStringList($config['group_by'] ?? []);

        foreach ($columns as $col) {
            if (!isset($src['columns'][$col])) {
                throw new InvalidConfigException("Nieznana kolumna: {$col}");
            }
            $sql = (string)$src['columns'][$col]['sql'];
            $selectParts[] = $sql . ' AS `' . $col . '`';
        }

        foreach ($aggregations as $agg) {
            if (!is_array($agg) || !isset($agg['field'], $agg['fn'], $agg['alias'])) {
                throw new InvalidConfigException('Niepoprawna agregacja (wymagane: field, fn, alias)');
            }
            $fn    = strtolower((string)$agg['fn']);
            $field = (string)$agg['field'];
            $alias = (string)$agg['alias'];

            if (!in_array($fn, DataSourceRegistry::allowedAggregations(), true)) {
                throw new InvalidConfigException("Niedozwolona funkcja: {$fn}");
            }
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]{0,50}$/', $alias)) {
                throw new InvalidConfigException("Niepoprawny alias: {$alias}");
            }
            if (!isset($src['columns'][$field])) {
                // dla COUNT(*) — pole 'id' lub jakiekolwiek
                throw new InvalidConfigException("Nieznane pole agregacji: {$field}");
            }
            $colSql = (string)$src['columns'][$field]['sql'];
            $selectParts[] = strtoupper($fn) . '(' . $colSql . ') AS `' . $alias . '`';
        }

        if ($selectParts === []) {
            // domyślnie wszystkie zdefiniowane domyślne kolumny
            foreach (($src['default_columns'] ?? []) as $col) {
                if (isset($src['columns'][$col])) {
                    $selectParts[] = (string)$src['columns'][$col]['sql'] . ' AS `' . $col . '`';
                }
            }
            if ($selectParts === []) {
                $selectParts[] = '1';
            }
        }

        $sql = 'SELECT ' . implode(', ', $selectParts);
        $sql .= ' FROM `' . $src['table'] . '` ' . $src['alias'];

        // ── JOINS (trusted, no user input) ─────────────────────
        foreach (($src['joins'] ?? []) as $join) {
            $sql .= ' ' . $join;
        }

        // ── WHERE: tenant + filters ────────────────────────────
        $whereParts = [];
        $whereParts[] = $src['tenant_column'] . ' = :club_id';

        $filters = $config['filters'] ?? [];
        foreach ($filters as $f) {
            if (!is_array($f) || !isset($f['field'], $f['op'])) {
                throw new InvalidConfigException('Niepoprawny filtr (wymagane: field, op)');
            }
            $field = (string)$f['field'];
            $op    = strtoupper((string)$f['op']);
            $value = $f['value'] ?? null;

            if (!isset($src['columns'][$field])) {
                throw new InvalidConfigException("Nieznane pole filtra: {$field}");
            }
            if (!in_array($op, DataSourceRegistry::allowedOperators(), true)) {
                throw new InvalidConfigException("Niedozwolony operator: {$op}");
            }
            $colSql = (string)$src['columns'][$field]['sql'];

            if ($op === 'IS NULL' || $op === 'IS NOT NULL') {
                $whereParts[] = $colSql . ' ' . $op;
                continue;
            }

            if ($op === 'IN' || $op === 'NOT IN') {
                if (!is_array($value) || $value === []) {
                    throw new InvalidConfigException("Operator {$op} wymaga niepustej tablicy wartości");
                }
                if (count($value) > self::MAX_IN_VALUES) {
                    throw new InvalidConfigException("Operator {$op} max " . self::MAX_IN_VALUES . ' wartości');
                }
                $placeholders = [];
                foreach ($value as $v) {
                    $paramCounter++;
                    $p = ':p' . $paramCounter;
                    $placeholders[] = $p;
                    $bindings[$p] = $this->scalarize($v);
                }
                $whereParts[] = $colSql . ' ' . $op . ' (' . implode(',', $placeholders) . ')';
                continue;
            }

            // = != < <= > >= LIKE
            $paramCounter++;
            $p = ':p' . $paramCounter;
            $bindings[$p] = $op === 'LIKE' ? '%' . (string)$this->scalarize($value) . '%' : $this->scalarize($value);
            $whereParts[] = $colSql . ' ' . $op . ' ' . $p;
        }

        $sql .= ' WHERE ' . implode(' AND ', $whereParts);

        // ── GROUP BY ───────────────────────────────────────────
        if ($groupBy !== []) {
            $parts = [];
            foreach ($groupBy as $g) {
                if (!isset($src['columns'][$g])) {
                    throw new InvalidConfigException("Nieznane pole GROUP BY: {$g}");
                }
                $parts[] = (string)$src['columns'][$g]['sql'];
            }
            $sql .= ' GROUP BY ' . implode(', ', $parts);
        }

        // ── ORDER BY ───────────────────────────────────────────
        $orderBy = $config['order_by'] ?? [];
        if (is_array($orderBy) && $orderBy !== []) {
            $parts = [];
            // budujemy mapę aliasów agregacji (dozwolone do sortowania)
            $aliasMap = [];
            foreach ($aggregations as $agg) {
                if (is_array($agg) && isset($agg['alias'])) {
                    $aliasMap[(string)$agg['alias']] = true;
                }
            }
            foreach ($orderBy as $o) {
                if (!is_array($o) || !isset($o['field'])) {
                    continue;
                }
                $field = (string)$o['field'];
                $dir   = strtolower((string)($o['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';

                if (isset($aliasMap[$field])) {
                    // sortowanie po aliasie agregacji — bezpieczne (alias whitelistowany regex)
                    $parts[] = '`' . $field . '` ' . $dir;
                } elseif (isset($src['columns'][$field])) {
                    $parts[] = (string)$src['columns'][$field]['sql'] . ' ' . $dir;
                } else {
                    throw new InvalidConfigException("Nieznane pole ORDER BY: {$field}");
                }
            }
            if ($parts !== []) {
                $sql .= ' ORDER BY ' . implode(', ', $parts);
            }
        }

        // ── LIMIT ──────────────────────────────────────────────
        $limit = (int)($config['limit'] ?? self::DEFAULT_LIMIT);
        if ($limit < 1) {
            $limit = self::DEFAULT_LIMIT;
        }
        if ($limit > self::MAX_LIMIT) {
            $limit = self::MAX_LIMIT;
        }
        $sql .= ' LIMIT ' . $limit;

        return ['sql' => $sql, 'bindings' => $bindings];
    }

    /**
     * Metadata kolumn dla rendera (label + type).
     * @param array<string, mixed> $src
     * @param array<string, mixed> $config
     * @return array<int, array<string, string>>
     */
    private function buildColumnsMeta(array $src, array $config): array
    {
        $out = [];
        $columns = $this->sanitizeStringList($config['columns'] ?? []);
        $aggregations = $config['aggregations'] ?? [];

        foreach ($columns as $col) {
            $meta = $src['columns'][$col] ?? null;
            if ($meta === null) {
                continue;
            }
            $out[] = [
                'key'   => $col,
                'label' => (string)$meta['label'],
                'type'  => (string)$meta['type'],
            ];
        }
        foreach ($aggregations as $agg) {
            if (!is_array($agg) || !isset($agg['alias'])) continue;
            $out[] = [
                'key'   => (string)$agg['alias'],
                'label' => (string)$agg['alias'],
                'type'  => 'number',
            ];
        }
        if ($out === []) {
            foreach (($src['default_columns'] ?? []) as $col) {
                if (isset($src['columns'][$col])) {
                    $out[] = [
                        'key'   => $col,
                        'label' => (string)$src['columns'][$col]['label'],
                        'type'  => (string)$src['columns'][$col]['type'],
                    ];
                }
            }
        }
        return $out;
    }

    /**
     * Buduje dane do wykresu Chart.js z wyniku.
     * @param array<string, mixed> $config
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, mixed>|null
     */
    private function buildChartData(array $config, array $rows): ?array
    {
        $chart = $config['chart'] ?? null;
        if (!is_array($chart) || ($chart['type'] ?? 'none') === 'none' || $rows === []) {
            return null;
        }
        $type = (string)$chart['type'];
        if (!in_array($type, ['bar', 'line', 'pie', 'doughnut'], true)) {
            return null;
        }
        $x = (string)($chart['x'] ?? '');
        $y = (string)($chart['y'] ?? '');
        if ($x === '' || $y === '') {
            return null;
        }
        $labels = [];
        $values = [];
        foreach ($rows as $row) {
            $labels[] = (string)($row[$x] ?? '');
            $values[] = is_numeric($row[$y] ?? null) ? (float)$row[$y] : 0;
        }
        return [
            'type'    => $type,
            'labels'  => $labels,
            'values'  => $values,
            'x_label' => $x,
            'y_label' => $y,
        ];
    }

    /**
     * @param mixed $list
     * @return string[]
     */
    private function sanitizeStringList(mixed $list): array
    {
        if (!is_array($list)) {
            return [];
        }
        $out = [];
        foreach ($list as $v) {
            if (is_string($v) && $v !== '') {
                $out[] = $v;
            }
        }
        return $out;
    }

    /**
     * Konwertuje wartość do skalara bezpiecznego do bindowania.
     */
    private function scalarize(mixed $v): mixed
    {
        if (is_array($v) || is_object($v)) {
            return json_encode($v);
        }
        if (is_bool($v)) {
            return $v ? 1 : 0;
        }
        return $v;
    }

    /**
     * Helper: validate config (bez wykonania) — używane przed save.
     * Zwraca [] jeśli OK, lub listę błędów.
     * @param array<string, mixed> $config
     * @return string[]
     */
    public function validateConfig(string $dataSource, array $config): array
    {
        try {
            $this->execute(0, $dataSource, array_merge($config, ['limit' => 1]));
            return [];
        } catch (InvalidConfigException $e) {
            return [$e->getMessage()];
        } catch (\Throwable $e) {
            // SQL errors itp. — tylko message, bez stack
            return ['Błąd walidacji: ' . $e->getMessage()];
        }
    }
}

/**
 * Wyjątek niepoprawnej konfiguracji raportu (whitelist failure).
 */
class InvalidConfigException extends RuntimeException
{
}
