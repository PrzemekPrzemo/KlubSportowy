<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\ClubKsefConfigModel;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for KSeF NIP validator (PL VAT identifier).
 *
 * Pure function — no DB / encryption / network dependencies.
 */
class KsefNipValidationTest extends TestCase
{
    /**
     * Valid NIP-y zaczerpniete z publicznych rejestrow przykladowych dla
     * algorytmu sumy kontrolnej MF.
     */
    public function testAcceptsValidNip(): void
    {
        // Przyklady z poprawna suma kontrolna
        $this->assertTrue(ClubKsefConfigModel::validateNip('5260250274'));   // MF
        $this->assertTrue(ClubKsefConfigModel::validateNip('7740001454'));   // valid sample
    }

    public function testRejectsInvalidChecksum(): void
    {
        $this->assertFalse(ClubKsefConfigModel::validateNip('1234567890'));
        $this->assertFalse(ClubKsefConfigModel::validateNip('0000000000'));
    }

    public function testRejectsWrongLength(): void
    {
        $this->assertFalse(ClubKsefConfigModel::validateNip('123'));
        $this->assertFalse(ClubKsefConfigModel::validateNip('12345678901'));
        $this->assertFalse(ClubKsefConfigModel::validateNip(''));
    }

    public function testStripsNonDigits(): void
    {
        // Walidator usuwa kreski / spacje przed sumą kontrolną
        $this->assertTrue(ClubKsefConfigModel::validateNip('526-025-02-74'));
        $this->assertTrue(ClubKsefConfigModel::validateNip('526 025 02 74'));
    }
}
