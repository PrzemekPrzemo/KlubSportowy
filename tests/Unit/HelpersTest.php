<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testFormatMoney(): void
    {
        $this->assertStringContainsString('100', format_money(100));
        $this->assertStringContainsString('zł', format_money(100));
        $this->assertEquals('1 234,56 zł', format_money(1234.56));
    }

    public function testFormatDate(): void
    {
        $this->assertEquals('15.01.2024', format_date('2024-01-15'));
        $this->assertEquals('—', format_date(null));
        $this->assertEquals('—', format_date(''));
    }

    public function testFormatDatetime(): void
    {
        $this->assertEquals('15.01.2024 10:30', format_datetime('2024-01-15 10:30:00'));
        $this->assertEquals('—', format_datetime(null));
    }

    public function testE(): void
    {
        $this->assertEquals('&lt;script&gt;', e('<script>'));
        $this->assertEquals('&amp;', e('&'));
        $this->assertEquals('', e(''));
    }

    public function testDaysUntil(): void
    {
        $future = date('Y-m-d', strtotime('+10 days'));
        $this->assertEquals(10, days_until($future));

        $past = date('Y-m-d', strtotime('-5 days'));
        $this->assertEquals(-5, days_until($past));

        $this->assertNull(days_until(null));
    }

    public function testAlertClass(): void
    {
        $this->assertEquals('danger', alert_class(-5));
        $this->assertEquals('warning', alert_class(10));
        $this->assertEquals('success', alert_class(60));
        $this->assertEquals('secondary', alert_class(null));
    }

    public function testNow(): void
    {
        $now = now('Y-m-d');
        $this->assertEquals(date('Y-m-d'), $now);
    }
}
