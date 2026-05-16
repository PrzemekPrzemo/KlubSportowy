<?php

namespace Tests\Unit;

use App\Helpers\Reports\DataSourceRegistry;
use App\Helpers\Reports\InvalidConfigException;
use App\Helpers\Reports\ReportBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 *
 * Security tests dla ReportBuilder — sprawdzamy że whitelist działa
 * i że NIE da się przemycić raw SQL przez user input.
 *
 * Te testy pracują WYŁĄCZNIE na buildSql() (bez DB) — czysta walidacja
 * konfiguracji i konstrukcja SQL z bindowanymi parametrami.
 */
class ReportBuilderSecurityTest extends TestCase
{
    public function testRejectsUnknownDataSource(): void
    {
        $rb = new ReportBuilder();
        $this->expectException(InvalidConfigException::class);
        $rb->execute(1, 'evil_source', ['columns' => ['id']]);
    }

    public function testRejectsUnknownColumn(): void
    {
        $rb = new ReportBuilder();
        $src = DataSourceRegistry::get('members');
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/Nieznana kolumna/');
        $rb->buildSql(1, $src, ['columns' => ['malicious_col_nope']]);
    }

    public function testRejectsRawSqlInColumnName(): void
    {
        $rb = new ReportBuilder();
        $src = DataSourceRegistry::get('members');
        $this->expectException(InvalidConfigException::class);
        $rb->buildSql(1, $src, ['columns' => ['id) UNION SELECT 1,2,3--']]);
    }

    public function testRejectsUnknownFilterField(): void
    {
        $rb = new ReportBuilder();
        $src = DataSourceRegistry::get('members');
        $this->expectException(InvalidConfigException::class);
        $rb->buildSql(1, $src, [
            'columns' => ['id'],
            'filters' => [['field' => 'nonexistent', 'op' => '=', 'value' => 'x']],
        ]);
    }

    public function testRejectsUnknownOperator(): void
    {
        $rb = new ReportBuilder();
        $src = DataSourceRegistry::get('members');
        $this->expectException(InvalidConfigException::class);
        $rb->buildSql(1, $src, [
            'columns' => ['id'],
            'filters' => [['field' => 'id', 'op' => 'EVIL_DROP', 'value' => 'x']],
        ]);
    }

    public function testInOperatorLimitedTo50Values(): void
    {
        $rb = new ReportBuilder();
        $src = DataSourceRegistry::get('members');
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/max/i');
        $rb->buildSql(1, $src, [
            'columns' => ['id'],
            'filters' => [['field' => 'id', 'op' => 'IN', 'value' => range(1, 100)]],
        ]);
    }

    public function testRejectsInvalidAggregationFunction(): void
    {
        $rb = new ReportBuilder();
        $src = DataSourceRegistry::get('members');
        $this->expectException(InvalidConfigException::class);
        $rb->buildSql(1, $src, [
            'columns' => [],
            'aggregations' => [['field' => 'id', 'fn' => 'DROP_TABLE', 'alias' => 'x']],
        ]);
    }

    public function testRejectsInvalidAggregationAlias(): void
    {
        $rb = new ReportBuilder();
        $src = DataSourceRegistry::get('members');
        $this->expectException(InvalidConfigException::class);
        $rb->buildSql(1, $src, [
            'columns' => [],
            'aggregations' => [['field' => 'id', 'fn' => 'count', 'alias' => 'a; DROP TABLE users']],
        ]);
    }

    public function testLimitClampedToMax(): void
    {
        $rb = new ReportBuilder();
        $src = DataSourceRegistry::get('members');
        $out = $rb->buildSql(1, $src, ['columns' => ['id'], 'limit' => 999999]);
        $this->assertStringContainsString('LIMIT 10000', $out['sql']);
    }

    public function testTenantFilterAlwaysAppliedAndBound(): void
    {
        $rb = new ReportBuilder();
        $src = DataSourceRegistry::get('members');
        $out = $rb->buildSql(42, $src, ['columns' => ['id']]);
        $this->assertStringContainsString('m.club_id = :club_id', $out['sql']);
        $this->assertSame(42, $out['bindings'][':club_id']);
    }

    public function testFilterValuesAreBoundNotInlined(): void
    {
        $rb = new ReportBuilder();
        $src = DataSourceRegistry::get('members');
        $evil = "'; DROP TABLE members; --";
        $out = $rb->buildSql(1, $src, [
            'columns' => ['id'],
            'filters' => [['field' => 'first_name', 'op' => '=', 'value' => $evil]],
        ]);
        // Wartość MUSI być w bindings, NIE w SQL
        $this->assertStringNotContainsString('DROP TABLE', $out['sql']);
        $this->assertContains($evil, $out['bindings']);
    }

    public function testValidConfigBuildsProperSql(): void
    {
        $rb = new ReportBuilder();
        $src = DataSourceRegistry::get('members');
        $out = $rb->buildSql(1, $src, [
            'columns' => ['first_name', 'last_name', 'email'],
            'filters' => [
                ['field' => 'status', 'op' => '=', 'value' => 'aktywny'],
                ['field' => 'address_city', 'op' => 'IN', 'value' => ['Warszawa', 'Kraków']],
            ],
            'order_by' => [['field' => 'last_name', 'dir' => 'asc']],
            'limit' => 100,
        ]);
        $this->assertStringContainsString('SELECT', $out['sql']);
        $this->assertStringContainsString('FROM `members`', $out['sql']);
        $this->assertStringContainsString('WHERE', $out['sql']);
        $this->assertStringContainsString('ORDER BY', $out['sql']);
        $this->assertStringContainsString('LIMIT 100', $out['sql']);
    }

    public function testAttendanceTenantScopingViaJoin(): void
    {
        $rb = new ReportBuilder();
        $src = DataSourceRegistry::get('attendance');
        $out = $rb->buildSql(7, $src, ['columns' => ['status']]);
        // training_attendees nie ma club_id — scoping przez t.club_id po JOIN do trainings
        $this->assertStringContainsString('INNER JOIN trainings t', $out['sql']);
        $this->assertStringContainsString('t.club_id = :club_id', $out['sql']);
        $this->assertSame(7, $out['bindings'][':club_id']);
    }
}
