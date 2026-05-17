<?php

namespace Tests\Unit;

use App\Helpers\Reports\ScheduledReportRunner;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 *
 * Sprawdza, ze calculateNext() dla 4 schedules wraca PRAWIDLOWE
 * nastepne daty 8:00 z deterministycznym $now (DateTimeImmutable).
 */
class ScheduledReportNextRunTest extends TestCase
{
    public function testWeeklyMondayPicksUpcomingMonday(): void
    {
        // Sroda 14.05.2025 12:00 → ponedzialek 19.05.2025 08:00
        $now  = new \DateTimeImmutable('2025-05-14 12:00:00');
        $next = ScheduledReportRunner::calculateNext('weekly_mon', $now);
        $this->assertSame('2025-05-19 08:00:00', $next);
    }

    public function testWeeklyMondayFromMondayPicksNextMonday(): void
    {
        // Poniedzialek 12.05.2025 07:00 → poniedzialek 19.05.2025 08:00
        $now  = new \DateTimeImmutable('2025-05-12 07:00:00');
        $next = ScheduledReportRunner::calculateNext('weekly_mon', $now);
        $this->assertSame('2025-05-19 08:00:00', $next);
    }

    public function testWeeklyFridayPicksUpcomingFriday(): void
    {
        // Sroda 14.05.2025 → piatek 16.05.2025 08:00
        $now  = new \DateTimeImmutable('2025-05-14 12:00:00');
        $next = ScheduledReportRunner::calculateNext('weekly_fri', $now);
        $this->assertSame('2025-05-16 08:00:00', $next);
    }

    public function testMonthlyFirstPicksFirstOfNextMonth(): void
    {
        // 14.05.2025 → 01.06.2025 08:00
        $now  = new \DateTimeImmutable('2025-05-14 12:00:00');
        $next = ScheduledReportRunner::calculateNext('monthly_1st', $now);
        $this->assertSame('2025-06-01 08:00:00', $next);
    }

    public function testMonthlyFirstFromFirstDayPicksNextMonth(): void
    {
        // 01.05.2025 → 01.06.2025 08:00 (a nie ten sam dzien)
        $now  = new \DateTimeImmutable('2025-05-01 09:00:00');
        $next = ScheduledReportRunner::calculateNext('monthly_1st', $now);
        $this->assertSame('2025-06-01 08:00:00', $next);
    }

    public function testQuarterlyFromQ1PicksApril1(): void
    {
        // Luty → 01.04 (Q2)
        $now  = new \DateTimeImmutable('2025-02-20 10:00:00');
        $next = ScheduledReportRunner::calculateNext('quarterly', $now);
        $this->assertSame('2025-04-01 08:00:00', $next);
    }

    public function testQuarterlyFromQ2PicksJuly1(): void
    {
        $now  = new \DateTimeImmutable('2025-05-15 10:00:00');
        $next = ScheduledReportRunner::calculateNext('quarterly', $now);
        $this->assertSame('2025-07-01 08:00:00', $next);
    }

    public function testQuarterlyFromQ3PicksOctober1(): void
    {
        $now  = new \DateTimeImmutable('2025-08-15 10:00:00');
        $next = ScheduledReportRunner::calculateNext('quarterly', $now);
        $this->assertSame('2025-10-01 08:00:00', $next);
    }

    public function testQuarterlyFromQ4RollsToNextYearJanuary(): void
    {
        $now  = new \DateTimeImmutable('2025-12-20 10:00:00');
        $next = ScheduledReportRunner::calculateNext('quarterly', $now);
        $this->assertSame('2026-01-01 08:00:00', $next);
    }

    public function testRecipientsEncodeDecodeRoundtrip(): void
    {
        $emails = ['a@example.com', 'invalid', 'b@example.com'];
        $json = ScheduledReportRunner::encodeRecipients($emails);
        $decoded = ScheduledReportRunner::decodeRecipients($json);
        $this->assertContains('a@example.com', $decoded);
        $this->assertContains('b@example.com', $decoded);
        $this->assertNotContains('invalid', $decoded);
    }

    public function testApplyPlaceholdersReplacesAll(): void
    {
        $tpl = 'Hi {{name}}, your KPI is {{kpi}}%.';
        $out = ScheduledReportRunner::applyPlaceholders($tpl, ['name' => 'Klub', 'kpi' => 78.5]);
        $this->assertSame('Hi Klub, your KPI is 78.5%.', $out);
    }
}
