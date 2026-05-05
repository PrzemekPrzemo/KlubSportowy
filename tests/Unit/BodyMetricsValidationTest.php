<?php

namespace Tests\Unit;

use App\Models\BodyMetricsModel;
use PHPUnit\Framework\TestCase;

/**
 * Static validation rules for body metrics self-entry (B2).
 */
class BodyMetricsValidationTest extends TestCase
{
    public function testEmptyInputRequiresAtLeastOne(): void
    {
        $errors = BodyMetricsModel::validate(['measured_at' => date('Y-m-d')]);
        $this->assertArrayHasKey('_at_least_one', $errors);
    }

    public function testValidWeightAndHeightPasses(): void
    {
        $errors = BodyMetricsModel::validate([
            'measured_at' => date('Y-m-d'),
            'weight_kg'   => 75.5,
            'height_cm'   => 178,
        ]);
        $this->assertEmpty($errors);
    }

    public function testWeightOutOfRangeRejected(): void
    {
        $low  = BodyMetricsModel::validate(['measured_at' => date('Y-m-d'), 'weight_kg' => 10]);
        $high = BodyMetricsModel::validate(['measured_at' => date('Y-m-d'), 'weight_kg' => 999]);
        $this->assertArrayHasKey('weight_kg', $low);
        $this->assertArrayHasKey('weight_kg', $high);
    }

    public function testHeightOutOfRangeRejected(): void
    {
        $low  = BodyMetricsModel::validate(['measured_at' => date('Y-m-d'), 'height_cm' => 50]);
        $high = BodyMetricsModel::validate(['measured_at' => date('Y-m-d'), 'height_cm' => 300]);
        $this->assertArrayHasKey('height_cm', $low);
        $this->assertArrayHasKey('height_cm', $high);
    }

    public function testBodyFatRange(): void
    {
        $bad  = BodyMetricsModel::validate(['measured_at' => date('Y-m-d'), 'body_fat_pct' => 95]);
        $ok   = BodyMetricsModel::validate(['measured_at' => date('Y-m-d'), 'body_fat_pct' => 18.5]);
        $this->assertArrayHasKey('body_fat_pct', $bad);
        $this->assertEmpty($ok);
    }

    public function testRestingHrRange(): void
    {
        $bad = BodyMetricsModel::validate(['measured_at' => date('Y-m-d'), 'resting_hr' => 250]);
        $ok  = BodyMetricsModel::validate(['measured_at' => date('Y-m-d'), 'resting_hr' => 60]);
        $this->assertArrayHasKey('resting_hr', $bad);
        $this->assertEmpty($ok);
    }

    public function testWingspanRange(): void
    {
        $bad = BodyMetricsModel::validate(['measured_at' => date('Y-m-d'), 'wingspan_cm' => 50]);
        $ok  = BodyMetricsModel::validate(['measured_at' => date('Y-m-d'), 'wingspan_cm' => 182]);
        $this->assertArrayHasKey('wingspan_cm', $bad);
        $this->assertEmpty($ok);
    }

    public function testFutureDateRejected(): void
    {
        $future = date('Y-m-d', strtotime('+5 days'));
        $errors = BodyMetricsModel::validate(['measured_at' => $future, 'weight_kg' => 70]);
        $this->assertArrayHasKey('measured_at', $errors);
    }

    public function testInvalidDateFormat(): void
    {
        $errors = BodyMetricsModel::validate(['measured_at' => 'not-a-date', 'weight_kg' => 70]);
        $this->assertArrayHasKey('measured_at', $errors);
    }

    public function testEmptyStringTreatedAsAbsent(): void
    {
        // empty string for unused fields should not flag range errors
        $errors = BodyMetricsModel::validate([
            'measured_at' => date('Y-m-d'),
            'weight_kg'   => 75,
            'height_cm'   => '',
            'body_fat_pct'=> '',
            'resting_hr'  => '',
            'wingspan_cm' => '',
        ]);
        $this->assertEmpty($errors);
    }
}
