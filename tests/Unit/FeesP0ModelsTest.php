<?php

namespace Tests\Unit;

use App\Models\FeeDiscountModel;
use App\Models\MemberFeeAssignmentModel;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 *
 * Faza P.0 — unit testy logiki kalkulacyjnej.
 * Bez DB — testują czyste funkcje obliczeń zniżek i net amount.
 */
class FeesP0ModelsTest extends TestCase
{
    public function testCalculatePercentDiscount(): void
    {
        $discount = ['discount_type' => 'percent', 'value' => 20.0];
        $this->assertSame(20.0, FeeDiscountModel::calculateDiscountAmount($discount, 100.0));
        $this->assertSame(50.0, FeeDiscountModel::calculateDiscountAmount($discount, 250.0));
    }

    public function testCalculateFixedAmountDiscount(): void
    {
        $discount = ['discount_type' => 'fixed_amount', 'value' => 50.0];
        $this->assertSame(50.0, FeeDiscountModel::calculateDiscountAmount($discount, 200.0));
        // Kwota stała większa niż gross — kapuje do gross (nie ujemny)
        $this->assertSame(30.0, FeeDiscountModel::calculateDiscountAmount($discount, 30.0));
    }

    public function testCalculateNetWithSingleStackableDiscount(): void
    {
        $discounts = [
            ['id' => 1, 'code' => 'junior', 'name' => 'Junior',
             'discount_type' => 'percent', 'value' => 20.0, 'is_stackable' => 1],
        ];
        $result = MemberFeeAssignmentModel::calculateNet(200.0, $discounts);
        $this->assertSame(200.0, $result['gross_amount']);
        $this->assertSame(40.0, $result['discount_amount']);
        $this->assertSame(160.0, $result['net_amount']);
        $this->assertCount(1, $result['breakdown']);
        $this->assertSame('junior', $result['breakdown'][0]['code']);
    }

    public function testCalculateNetWithMultipleStackableDiscounts(): void
    {
        // 200 - 20% (40) = 160; 160 - 50zl = 110
        $discounts = [
            ['id' => 1, 'code' => 'junior', 'name' => 'Junior',
             'discount_type' => 'percent', 'value' => 20.0, 'is_stackable' => 1],
            ['id' => 2, 'code' => 'multisport', 'name' => 'Multi-sport',
             'discount_type' => 'fixed_amount', 'value' => 50.0, 'is_stackable' => 1],
        ];
        $result = MemberFeeAssignmentModel::calculateNet(200.0, $discounts);
        $this->assertSame(200.0, $result['gross_amount']);
        $this->assertSame(90.0, $result['discount_amount']); // 40 + 50
        $this->assertSame(110.0, $result['net_amount']);
        $this->assertCount(2, $result['breakdown']);
    }

    public function testNonStackableDiscountIgnoredAfterFirst(): void
    {
        // Pierwsza zniżka stackable=0, kolejna ignorowana
        $discounts = [
            ['id' => 1, 'code' => 'scholarship', 'name' => 'Stypendium',
             'discount_type' => 'percent', 'value' => 100.0, 'is_stackable' => 0],
            ['id' => 2, 'code' => 'junior', 'name' => 'Junior',
             'discount_type' => 'percent', 'value' => 20.0, 'is_stackable' => 1],
        ];
        $result = MemberFeeAssignmentModel::calculateNet(200.0, $discounts);
        // Tylko scholarship zastosowane (100% = 200zl off)
        $this->assertSame(200.0, $result['discount_amount']);
        $this->assertSame(0.0, $result['net_amount']);
        $this->assertCount(1, $result['breakdown']);
    }

    public function testNetAmountCannotBeNegative(): void
    {
        // 100% off + 50zl fixed = -50zl... net powinien być >= 0
        $discounts = [
            ['id' => 1, 'code' => 'free', 'name' => 'Free',
             'discount_type' => 'percent', 'value' => 100.0, 'is_stackable' => 1],
            ['id' => 2, 'code' => 'extra', 'name' => 'Extra',
             'discount_type' => 'fixed_amount', 'value' => 50.0, 'is_stackable' => 1],
        ];
        $result = MemberFeeAssignmentModel::calculateNet(100.0, $discounts);
        $this->assertSame(0.0, $result['net_amount']);
    }

    public function testFeeDiscountTypesConstants(): void
    {
        $this->assertArrayHasKey(FeeDiscountModel::TYPE_PERCENT, FeeDiscountModel::$TYPES);
        $this->assertArrayHasKey(FeeDiscountModel::TYPE_FIXED, FeeDiscountModel::$TYPES);
    }
}
