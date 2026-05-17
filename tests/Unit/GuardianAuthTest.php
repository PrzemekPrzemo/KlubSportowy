<?php

namespace Tests\Unit;

use App\Models\GuardianMinorConsentModel;
use App\Models\GuardianModel;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 *
 * Portal opiekuna — testy walidacji/logiki bez DB.
 * Pelne testy integracyjne (db round-trip, cross-tenant guard) wymagaja
 * fixture'a klubu i sa w tests/Feature/GuardianPortalFeatureTest.php (TODO).
 */
class GuardianAuthTest extends TestCase
{
    public function testSanitizePhoneStripsInvalidChars(): void
    {
        $this->assertSame('+48 600 123 456', GuardianModel::sanitizePhone('+48 600 123 456'));
        $this->assertSame('600-123-456',   GuardianModel::sanitizePhone('600-123-456 abc'));
        $this->assertNull(GuardianModel::sanitizePhone(''));
        $this->assertNull(GuardianModel::sanitizePhone(null));
        $this->assertNull(GuardianModel::sanitizePhone('   '));
    }

    public function testSanitizePhoneTruncatesTo20Chars(): void
    {
        $long = str_repeat('1', 50);
        $out  = GuardianModel::sanitizePhone($long);
        $this->assertNotNull($out);
        $this->assertLessThanOrEqual(20, strlen($out));
    }

    public function testActivationTokenIsCryptographicallyRandomHex(): void
    {
        $t1 = bin2hex(random_bytes(32));
        $t2 = bin2hex(random_bytes(32));
        $this->assertSame(64, strlen($t1));
        $this->assertSame(64, strlen($t2));
        $this->assertNotSame($t1, $t2);
        $this->assertTrue(ctype_xdigit($t1));
    }

    public function testPasswordHashingUsesBcryptWithExpectedCost(): void
    {
        $hash = password_hash('secret123', PASSWORD_BCRYPT, ['cost' => GuardianModel::BCRYPT_COST]);
        $info = password_get_info($hash);
        $this->assertSame('bcrypt', $info['algoName']);
        $this->assertSame(GuardianModel::BCRYPT_COST, $info['options']['cost']);
        $this->assertTrue(password_verify('secret123', $hash));
        $this->assertFalse(password_verify('wrong', $hash));
    }

    public function testConsentTypeValidation(): void
    {
        $this->assertTrue(GuardianMinorConsentModel::isValidType('data_processing'));
        $this->assertTrue(GuardianMinorConsentModel::isValidType('image_use'));
        $this->assertTrue(GuardianMinorConsentModel::isValidType('medical_treatment'));
        $this->assertFalse(GuardianMinorConsentModel::isValidType('random_bullshit'));
        $this->assertFalse(GuardianMinorConsentModel::isValidType(''));
        $this->assertFalse(GuardianMinorConsentModel::isValidType('SQL injection;DROP TABLE'));
    }

    public function testConsentLabelsAreHumanReadable(): void
    {
        foreach (GuardianMinorConsentModel::TYPES as $type) {
            $label = GuardianMinorConsentModel::labelFor($type);
            $this->assertNotEmpty($label);
            $this->assertNotSame($type, $label, "Label dla {$type} powinien byc inny niz kod");
        }
    }

    public function testAllSevenRequiredConsentTypesArePresent(): void
    {
        $expected = [
            'data_processing',
            'image_use',
            'training_participation',
            'tournament_participation',
            'medical_treatment',
            'communication_email',
            'communication_sms',
        ];
        foreach ($expected as $e) {
            $this->assertContains($e, GuardianMinorConsentModel::TYPES);
        }
        $this->assertCount(count($expected), GuardianMinorConsentModel::TYPES);
    }

    public function testEmailValidationFormatRules(): void
    {
        $this->assertNotFalse(filter_var('rodzic@example.com', FILTER_VALIDATE_EMAIL));
        $this->assertNotFalse(filter_var('jan.kowalski+klub@gmail.com', FILTER_VALIDATE_EMAIL));
        $this->assertFalse(filter_var('not-an-email', FILTER_VALIDATE_EMAIL));
        $this->assertFalse(filter_var('@example.com', FILTER_VALIDATE_EMAIL));
        $this->assertFalse(filter_var('rodzic@', FILTER_VALIDATE_EMAIL));
    }
}
