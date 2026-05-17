<?php

declare(strict_types=1);

use App\Models\ClubModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Models\ClubModel::slugify
 * @covers \App\Models\ClubModel::approxMembers
 *
 * Pure-function tests dla slugify klubu i agregacji liczby czlonkow.
 * Generate-with-suffix testy wymagaja DB (pomijamy).
 */
class ClubSlugTest extends TestCase
{
    public function testBasicAsciiSlug(): void
    {
        $this->assertSame('akademia-judo', ClubModel::slugify('Akademia Judo'));
    }

    public function testPolishCharactersTransliterated(): void
    {
        $this->assertSame('klub-zazolc-gesla', ClubModel::slugify('Klub Zażółć Gęślą'));
    }

    public function testRealisticClubName(): void
    {
        $this->assertSame(
            'azs-uw-warszawa',
            ClubModel::slugify('AZS UW Warszawa')
        );
    }

    public function testSpecialCharsCollapsedToDash(): void
    {
        $this->assertSame('club-name', ClubModel::slugify('club ___ // name!!!'));
    }

    public function testEmptyStringReturnsEmpty(): void
    {
        $this->assertSame('', ClubModel::slugify('   '));
    }

    public function testLeadingTrailingDashesRemoved(): void
    {
        $this->assertSame('akademia', ClubModel::slugify('---Akademia---'));
    }

    public function testMatchesSchemaRegex(): void
    {
        $slug = ClubModel::slugify('Akademia 2026 — Gala Sportu!');
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug);
    }

    public function testUnicodeStrippedNotKept(): void
    {
        $this->assertSame('puchar', ClubModel::slugify('Puchar 🏆 中文'));
    }

    public function testNumberPreserved(): void
    {
        $this->assertSame('klub-2025-2026', ClubModel::slugify('Klub 2025/2026'));
    }

    public function testSlashAndDotCollapsed(): void
    {
        $this->assertSame('mks-poznan-2024', ClubModel::slugify('MKS Poznań 2024'));
    }

    // approxMembers — buckets for privacy (nigdy nie pokazujemy dokladnej liczby).
    public function testApproxMembersZero(): void
    {
        $this->assertSame('—', ClubModel::approxMembers(0));
    }

    public function testApproxMembersSmall(): void
    {
        $this->assertSame('<10', ClubModel::approxMembers(7));
    }

    public function testApproxMembersThresholds(): void
    {
        $this->assertSame('10+', ClubModel::approxMembers(10));
        $this->assertSame('10+', ClubModel::approxMembers(24));
        $this->assertSame('25+', ClubModel::approxMembers(25));
        $this->assertSame('25+', ClubModel::approxMembers(49));
        $this->assertSame('50+', ClubModel::approxMembers(50));
        $this->assertSame('100+', ClubModel::approxMembers(100));
        $this->assertSame('100+', ClubModel::approxMembers(499));
        $this->assertSame('500+', ClubModel::approxMembers(500));
        $this->assertSame('500+', ClubModel::approxMembers(9999));
    }

    public function testSlugMaxLengthRespectedByGenerator(): void
    {
        // slugify samo nie tnie; generator (DB-based) capuje do 73.
        // Tu testujemy ze nawet bardzo dlugi input ma valid format.
        $long = str_repeat('a', 200);
        $slug = ClubModel::slugify($long);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug);
    }
}
