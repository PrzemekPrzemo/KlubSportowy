<?php

declare(strict_types=1);

use App\Models\MemberModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Models\MemberModel::slugify
 */
class PublicProfileSlugTest extends TestCase
{
    public function testBasicAsciiSlug(): void
    {
        $this->assertSame('jan-kowalski', MemberModel::slugify('Jan Kowalski'));
    }

    public function testPolishCharactersStripped(): void
    {
        $this->assertSame('zazolc-gesla-jazn', MemberModel::slugify('Zażółć gęślą jaźń'));
    }

    public function testSpecialCharsCollapsedToDash(): void
    {
        $this->assertSame('test-name', MemberModel::slugify('test ___ // name!!!'));
    }

    public function testEmptyStringReturnsEmpty(): void
    {
        $this->assertSame('', MemberModel::slugify('   '));
    }

    public function testMatchesSchemaRegex(): void
    {
        $slug = MemberModel::slugify('Jan-Pawel Kowalski');
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug);
    }

    public function testLeadingTrailingDashesRemoved(): void
    {
        $this->assertSame('abc', MemberModel::slugify('---abc---'));
    }
}
