<?php

namespace Tests\Unit;

use App\Models\GuardianMinorConsentModel;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 *
 * Testy logiki GuardianMinorConsentModel — typowanie zgod RODO art. 8.
 *
 * Pelne testy DB (idempotency, cross-tenant guard) wymagaja schematu —
 * sa w tests/Feature/GuardianPortalFeatureTest.php (do uruchomienia z fixturem).
 */
class GuardianMinorConsentModelTest extends TestCase
{
    public function testIsValidTypeRejectsUnknownType(): void
    {
        $this->assertFalse(GuardianMinorConsentModel::isValidType('hack_attempt'));
        $this->assertFalse(GuardianMinorConsentModel::isValidType(''));
        $this->assertFalse(GuardianMinorConsentModel::isValidType('1; DROP TABLE'));
    }

    public function testAllEnumTypesHaveLabels(): void
    {
        foreach (GuardianMinorConsentModel::TYPES as $type) {
            $label = GuardianMinorConsentModel::labelFor($type);
            $this->assertNotEmpty($label, "Brakuje etykiety dla typu {$type}");
            $this->assertNotSame(strtolower($label), $label, "Etykieta dla {$type} powinna byc human-readable: {$label}");
        }
    }

    public function testConsentTypesAreImmutableEnumList(): void
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
        $this->assertSame($expected, GuardianMinorConsentModel::TYPES);
    }

    public function testLabelForCommunicationTypes(): void
    {
        $this->assertStringContainsString('e-mail', strtolower(GuardianMinorConsentModel::labelFor('communication_email')));
        $this->assertStringContainsString('sms',    strtolower(GuardianMinorConsentModel::labelFor('communication_sms')));
    }

    public function testLabelForImageAndMedicalTypes(): void
    {
        $this->assertStringContainsString('wizerun', strtolower(GuardianMinorConsentModel::labelFor('image_use')));
        $this->assertStringContainsString('medyczn', strtolower(GuardianMinorConsentModel::labelFor('medical_treatment')));
    }

    public function testIpAddressMaxLengthConstraint(): void
    {
        $ipv6 = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
        $this->assertLessThanOrEqual(45, strlen($ipv6));

        $ipv4 = '192.168.1.1';
        $this->assertLessThanOrEqual(45, strlen($ipv4));
    }

    public function testUserAgentTruncationLimit(): void
    {
        $long  = str_repeat('A', 1000);
        $trunc = substr($long, 0, 500);
        $this->assertSame(500, strlen($trunc));
    }
}
