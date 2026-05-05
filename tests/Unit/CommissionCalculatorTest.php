<?php

namespace Tests\Unit;

use App\Helpers\CommissionCalculator;
use App\Models\TrainerCommissionRateModel;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 *
 * Faza U.0 — unit testy pure-function calculate(amount, type, value).
 * Bez DB — testujemy tylko matematykę prowizji.
 */
class CommissionCalculatorTest extends TestCase
{
    public function testPercentCommission(): void
    {
        // 30% z 200 PLN = 60.00
        $this->assertSame(60.0, CommissionCalculator::calculate(200.0, 'percent', 30.0));
        // 10% z 99.99 = 9.999 → round 2 = 10.00
        $this->assertSame(10.0, CommissionCalculator::calculate(99.99, 'percent', 10.0));
        // 100% = pełna kwota
        $this->assertSame(150.0, CommissionCalculator::calculate(150.0, 'percent', 100.0));
    }

    public function testPercentCappedAt100(): void
    {
        // > 100% jest cappowane do 100% (defensive)
        $this->assertSame(200.0, CommissionCalculator::calculate(200.0, 'percent', 250.0));
    }

    public function testFixedAmountCommission(): void
    {
        // Stała 50 PLN
        $this->assertSame(50.0, CommissionCalculator::calculate(200.0, 'fixed_amount', 50.0));
        // Stała większa niż wpłata — kapuje do amount (nie ujemny saldo dla klubu)
        $this->assertSame(30.0, CommissionCalculator::calculate(30.0, 'fixed_amount', 50.0));
    }

    public function testZeroOrNegativeAmount(): void
    {
        $this->assertSame(0.0, CommissionCalculator::calculate(0.0, 'percent', 30.0));
        $this->assertSame(0.0, CommissionCalculator::calculate(-50.0, 'percent', 30.0));
        $this->assertSame(0.0, CommissionCalculator::calculate(0.0, 'fixed_amount', 50.0));
    }

    public function testZeroOrNegativeRate(): void
    {
        $this->assertSame(0.0, CommissionCalculator::calculate(200.0, 'percent', 0.0));
        $this->assertSame(0.0, CommissionCalculator::calculate(200.0, 'percent', -10.0));
        $this->assertSame(0.0, CommissionCalculator::calculate(200.0, 'fixed_amount', 0.0));
    }

    public function testUnknownTypeReturnsZero(): void
    {
        $this->assertSame(0.0, CommissionCalculator::calculate(200.0, 'foobar', 50.0));
        $this->assertSame(0.0, CommissionCalculator::calculate(200.0, '', 50.0));
    }

    public function testRoundingToTwoDecimals(): void
    {
        // 33.33% z 100 = 33.33
        $this->assertSame(33.33, CommissionCalculator::calculate(100.0, 'percent', 33.33));
        // 12.5% z 17.77 = 2.22125 → 2.22
        $this->assertSame(2.22, CommissionCalculator::calculate(17.77, 'percent', 12.5));
    }

    public function testRateModelConstants(): void
    {
        // Sanity check: stałe zgodne ze schematem ENUM
        $this->assertSame('percent',      TrainerCommissionRateModel::TYPE_PERCENT);
        $this->assertSame('fixed_amount', TrainerCommissionRateModel::TYPE_FIXED);
        $this->assertArrayHasKey('skladka',  TrainerCommissionRateModel::$APPLIES_TO);
        $this->assertArrayHasKey('wpisowe',  TrainerCommissionRateModel::$APPLIES_TO);
        $this->assertArrayHasKey('licencja', TrainerCommissionRateModel::$APPLIES_TO);
        $this->assertArrayHasKey('all',      TrainerCommissionRateModel::$APPLIES_TO);
    }
}
