<?php

declare(strict_types=1);

use App\Models\TournamentModel;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Models\TournamentModel::slugify
 *
 * Slug generation (pure-function tests — bez DB).
 * Generate-with-random-suffix tests pomijamy (wymagaja DB connection).
 */
class TournamentLiveSlugTest extends TestCase
{
    public function testBasicAsciiSlug(): void
    {
        $this->assertSame('mistrzostwa-warszawy', TournamentModel::slugify('Mistrzostwa Warszawy'));
    }

    public function testPolishCharactersTransliterated(): void
    {
        $this->assertSame('zazolc-gesla-jazn', TournamentModel::slugify('Zażółć gęślą jaźń'));
    }

    public function testTournamentNameRealistic(): void
    {
        $this->assertSame(
            'mistrzostwa-warszawy-bjj-2026',
            TournamentModel::slugify('Mistrzostwa Warszawy BJJ 2026')
        );
    }

    public function testSpecialCharsCollapsedToDash(): void
    {
        $this->assertSame('test-name', TournamentModel::slugify('test ___ // name!!!'));
    }

    public function testEmptyStringReturnsEmpty(): void
    {
        $this->assertSame('', TournamentModel::slugify('   '));
    }

    public function testLeadingTrailingDashesRemoved(): void
    {
        $this->assertSame('puchar', TournamentModel::slugify('---Puchar---'));
    }

    public function testMatchesSchemaRegex(): void
    {
        $slug = TournamentModel::slugify('Puchar 2026 — Gala BJJ!');
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug);
    }

    public function testMaxLengthRespected(): void
    {
        // 80 chars max in DB. Slug-only (without random suffix) jest <= 73
        // (zostawiamy 7 chars na "-XXXXXX"). Tu testujemy ze slugify samo
        // nie wprowadza nadmiarowych znakow.
        $long = str_repeat('a', 200);
        $slug = TournamentModel::slugify($long);
        // 'a' repeated — slugify nie tnie sam, controller dba o cap.
        $this->assertSame($long, $slug);
    }

    public function testUnicodeStrippedNotKept(): void
    {
        // Emoji + chinskie znaki -> usuniete (strip non-ASCII).
        $this->assertSame('puchar', TournamentModel::slugify('Puchar 🏆 中文'));
    }

    public function testNumberPreserved(): void
    {
        $this->assertSame('liga-2025-2026', TournamentModel::slugify('Liga 2025/2026'));
    }
}
