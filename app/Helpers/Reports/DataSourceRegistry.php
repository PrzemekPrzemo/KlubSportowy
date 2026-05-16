<?php

declare(strict_types=1);

namespace App\Helpers\Reports;

/**
 * DataSourceRegistry — statyczna whitelist źródeł danych dla report buildera.
 *
 * KAŻDE pole, filtr, operator i agregacja JEST whitelistowana.
 * ReportBuilder NIGDY nie buduje SQL z user input bez przejścia przez registry.
 *
 * Format źródła:
 *   key               => identyfikator (= saved_reports.data_source ENUM)
 *   label             => Polska nazwa do UI
 *   table             => fizyczna nazwa tabeli (FROM)
 *   tenant_column     => kolumna do scoping multi-tenant (zwykle club_id)
 *   joins             => opcjonalne LEFT JOINy (zaufane SQL, BEZ user input)
 *   columns           => mapa column_key => [type, label, sql, aggregatable, filterable]
 *                        - sql jest zaufanym fragmentem (np. "m.first_name" lub
 *                          podzapytanie). NIGDY nie pochodzi z user input.
 *   default_columns   => lista kolumn pokazywanych domyślnie w kreatorze
 */
final class DataSourceRegistry
{
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $cache = null;

    /**
     * Zwraca wszystkie źródła danych.
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        self::$cache = [
            // ── MEMBERS ────────────────────────────────────────────
            'members' => [
                'label'         => 'Członkowie',
                'table'         => 'members',
                'alias'         => 'm',
                'tenant_column' => 'm.club_id',
                'joins'         => [],
                'columns' => [
                    'id'             => ['type' => 'int',      'label' => 'ID',                 'sql' => 'm.id',             'filterable' => true],
                    'member_number'  => ['type' => 'string',   'label' => 'Nr członkowski',     'sql' => 'm.member_number',  'filterable' => true],
                    'first_name'     => ['type' => 'string',   'label' => 'Imię',               'sql' => 'm.first_name',     'filterable' => true],
                    'last_name'      => ['type' => 'string',   'label' => 'Nazwisko',           'sql' => 'm.last_name',      'filterable' => true],
                    'email'          => ['type' => 'string',   'label' => 'Email',              'sql' => 'm.email',          'filterable' => true],
                    'phone'          => ['type' => 'string',   'label' => 'Telefon',            'sql' => 'm.phone',          'filterable' => true],
                    'address_city'   => ['type' => 'string',   'label' => 'Miasto',             'sql' => 'm.address_city',   'filterable' => true],
                    'birth_date'     => ['type' => 'date',     'label' => 'Data urodzenia',     'sql' => 'm.birth_date',     'filterable' => true],
                    'gender'         => ['type' => 'string',   'label' => 'Płeć',               'sql' => 'm.gender',         'filterable' => true],
                    'status'         => ['type' => 'string',   'label' => 'Status',             'sql' => 'm.status',         'filterable' => true],
                    'join_date'      => ['type' => 'date',     'label' => 'Data dołączenia',    'sql' => 'm.join_date',      'filterable' => true],
                    'created_at'     => ['type' => 'datetime', 'label' => 'Data utworzenia',    'sql' => 'm.created_at',     'filterable' => true],
                ],
                'default_columns' => ['member_number', 'first_name', 'last_name', 'email', 'status'],
            ],

            // ── TRAININGS ──────────────────────────────────────────
            'trainings' => [
                'label'         => 'Treningi',
                'table'         => 'trainings',
                'alias'         => 't',
                'tenant_column' => 't.club_id',
                'joins'         => [],
                'columns' => [
                    'id'         => ['type' => 'int',      'label' => 'ID',           'sql' => 't.id',          'filterable' => true],
                    'name'       => ['type' => 'string',   'label' => 'Nazwa',        'sql' => 't.name',        'filterable' => true],
                    'location'   => ['type' => 'string',   'label' => 'Lokalizacja',  'sql' => 't.location',    'filterable' => true],
                    'start_time' => ['type' => 'datetime', 'label' => 'Początek',     'sql' => 't.start_time',  'filterable' => true],
                    'end_time'   => ['type' => 'datetime', 'label' => 'Koniec',       'sql' => 't.end_time',    'filterable' => true],
                    'status'     => ['type' => 'string',   'label' => 'Status',       'sql' => 't.status',      'filterable' => true],
                    'created_at' => ['type' => 'datetime', 'label' => 'Utworzono',    'sql' => 't.created_at',  'filterable' => true],
                ],
                'default_columns' => ['name', 'location', 'start_time', 'status'],
            ],

            // ── PAYMENTS ───────────────────────────────────────────
            'payments' => [
                'label'         => 'Płatności',
                'table'         => 'payments',
                'alias'         => 'p',
                'tenant_column' => 'p.club_id',
                'joins' => [
                    'LEFT JOIN members m ON m.id = p.member_id',
                ],
                'columns' => [
                    'id'              => ['type' => 'int',      'label' => 'ID',                 'sql' => 'p.id',                                            'filterable' => true],
                    'amount'          => ['type' => 'decimal',  'label' => 'Kwota',              'sql' => 'p.amount',                                        'filterable' => true],
                    'payment_date'    => ['type' => 'date',     'label' => 'Data płatności',     'sql' => 'p.payment_date',                                  'filterable' => true],
                    'period_year'     => ['type' => 'int',      'label' => 'Rok rozliczeniowy',  'sql' => 'p.period_year',                                   'filterable' => true],
                    'period_month'    => ['type' => 'int',      'label' => 'Miesiąc',            'sql' => 'p.period_month',                                  'filterable' => true],
                    'method'          => ['type' => 'string',   'label' => 'Metoda',             'sql' => 'p.method',                                        'filterable' => true],
                    'reference'       => ['type' => 'string',   'label' => 'Referencja',         'sql' => 'p.reference',                                     'filterable' => true],
                    'member_number'   => ['type' => 'string',   'label' => 'Nr członkowski',     'sql' => 'm.member_number',                                 'filterable' => true],
                    'member_full_name'=> ['type' => 'string',   'label' => 'Zawodnik',           'sql' => "CONCAT(m.first_name, ' ', m.last_name)",          'filterable' => false],
                    'created_at'      => ['type' => 'datetime', 'label' => 'Utworzono',          'sql' => 'p.created_at',                                    'filterable' => true],
                ],
                'default_columns' => ['payment_date', 'member_full_name', 'amount', 'method'],
            ],

            // ── TOURNAMENTS ────────────────────────────────────────
            'tournaments' => [
                'label'         => 'Turnieje',
                'table'         => 'tournaments',
                'alias'         => 'tr',
                'tenant_column' => 'tr.club_id',
                'joins'         => [],
                'columns' => [
                    'id'         => ['type' => 'int',    'label' => 'ID',          'sql' => 'tr.id',         'filterable' => true],
                    'name'       => ['type' => 'string', 'label' => 'Nazwa',       'sql' => 'tr.name',       'filterable' => true],
                    'sport_key'  => ['type' => 'string', 'label' => 'Sport',       'sql' => 'tr.sport_key',  'filterable' => true],
                    'format'     => ['type' => 'string', 'label' => 'Format',      'sql' => 'tr.format',     'filterable' => true],
                    'date_start' => ['type' => 'date',   'label' => 'Data startu', 'sql' => 'tr.date_start', 'filterable' => true],
                    'status'     => ['type' => 'string', 'label' => 'Status',      'sql' => 'tr.status',     'filterable' => true],
                    'created_at' => ['type' => 'datetime','label'=> 'Utworzono',   'sql' => 'tr.created_at', 'filterable' => true],
                ],
                'default_columns' => ['name', 'date_start', 'format', 'status'],
            ],

            // ── ATTENDANCE ─────────────────────────────────────────
            'attendance' => [
                'label'         => 'Obecność na treningach',
                'table'         => 'training_attendees',
                'alias'         => 'ta',
                // training_attendees nie ma club_id — scoping przez JOIN do trainings
                'tenant_column' => 't.club_id',
                'joins' => [
                    'INNER JOIN trainings t ON t.id = ta.training_id',
                    'LEFT JOIN members m ON m.id = ta.member_id',
                ],
                'columns' => [
                    'id'               => ['type' => 'int',      'label' => 'ID',                 'sql' => 'ta.id',                                           'filterable' => true],
                    'status'           => ['type' => 'string',   'label' => 'Status obecności',   'sql' => 'ta.status',                                       'filterable' => true],
                    'registered_at'    => ['type' => 'datetime', 'label' => 'Data rejestracji',   'sql' => 'ta.registered_at',                                'filterable' => true],
                    'training_name'    => ['type' => 'string',   'label' => 'Trening',            'sql' => 't.name',                                          'filterable' => true],
                    'training_start'   => ['type' => 'datetime', 'label' => 'Start treningu',     'sql' => 't.start_time',                                    'filterable' => true],
                    'member_number'    => ['type' => 'string',   'label' => 'Nr członkowski',     'sql' => 'm.member_number',                                 'filterable' => true],
                    'member_full_name' => ['type' => 'string',   'label' => 'Zawodnik',           'sql' => "CONCAT(m.first_name, ' ', m.last_name)",          'filterable' => false],
                ],
                'default_columns' => ['training_name', 'member_full_name', 'status', 'registered_at'],
            ],
        ];

        return self::$cache;
    }

    /**
     * Pobiera konfigurację jednego źródła. Zwraca null jeśli nie istnieje (= invalid input).
     * @return array<string, mixed>|null
     */
    public static function get(string $key): ?array
    {
        $all = self::all();
        return $all[$key] ?? null;
    }

    /**
     * @return string[] lista kluczy
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * Sprawdza czy kolumna istnieje w danym źródle.
     */
    public static function hasColumn(string $sourceKey, string $columnKey): bool
    {
        $src = self::get($sourceKey);
        return $src !== null && isset($src['columns'][$columnKey]);
    }

    /**
     * Zwraca zaufany SQL fragment dla kolumny (np. "m.first_name"). null = nieistniejąca.
     */
    public static function columnSql(string $sourceKey, string $columnKey): ?string
    {
        $src = self::get($sourceKey);
        if ($src === null || !isset($src['columns'][$columnKey])) {
            return null;
        }
        return (string)$src['columns'][$columnKey]['sql'];
    }

    /**
     * Whitelisted operators dla filtrów.
     * @return string[]
     */
    public static function allowedOperators(): array
    {
        return ['=', '!=', '<', '<=', '>', '>=', 'LIKE', 'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL'];
    }

    /**
     * Whitelisted funkcje agregujące.
     * @return string[]
     */
    public static function allowedAggregations(): array
    {
        return ['count', 'sum', 'avg', 'min', 'max'];
    }
}
