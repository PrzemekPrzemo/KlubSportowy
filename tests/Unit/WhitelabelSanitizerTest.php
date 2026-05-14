<?php

namespace Tests\Unit;

use App\Helpers\WhitelabelSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * Sanitization custom CSS + email header HTML dla whitelabel per-klub.
 *
 * Test pokrywa wymagania bezpieczenstwa zapisane w specyfikacji:
 *   - CSS: blokujemy <script>, expression(), javascript:, @import, behavior:
 *   - HTML email header: tylko whitelist tagow, brak on*-handlerow.
 */
class WhitelabelSanitizerTest extends TestCase
{
    // ── CSS ──────────────────────────────────────────────────────────────────

    public function testCssAcceptsValidStyles(): void
    {
        $css = ".sidebar .brand h5 { color: #ff0000; font-style: italic; }";
        $this->assertSame($css, WhitelabelSanitizer::sanitizeCss($css));
    }

    public function testCssAcceptsEmpty(): void
    {
        $this->assertSame('', WhitelabelSanitizer::sanitizeCss(''));
        $this->assertSame('', WhitelabelSanitizer::sanitizeCss('   '));
    }

    public function testCssRejectsScriptTag(): void
    {
        $this->assertNull(WhitelabelSanitizer::sanitizeCss('body { color: red; } <script>alert(1)</script>'));
    }

    public function testCssRejectsAngleBrackets(): void
    {
        // CSS bez wstrzykniecia tagu — ale obecnosc < > jest podejrzana.
        $this->assertNull(WhitelabelSanitizer::sanitizeCss('a { content: "<x>"; }'));
    }

    public function testCssRejectsExpression(): void
    {
        $this->assertNull(WhitelabelSanitizer::sanitizeCss('body { width: expression(alert(1)); }'));
        $this->assertNull(WhitelabelSanitizer::sanitizeCss('body { width: EXPRESSION (alert(1)); }'));
    }

    public function testCssRejectsJavascriptUrl(): void
    {
        $this->assertNull(WhitelabelSanitizer::sanitizeCss('a { background: url(javascript:alert(1)); }'));
        $this->assertNull(WhitelabelSanitizer::sanitizeCss('a { cursor: url("javascript:alert(1)"); }'));
    }

    public function testCssRejectsImport(): void
    {
        $this->assertNull(WhitelabelSanitizer::sanitizeCss('@import url(http://evil.example/x.css);'));
        $this->assertNull(WhitelabelSanitizer::sanitizeCss('@IMPORT "x.css";'));
    }

    public function testCssRejectsBehavior(): void
    {
        $this->assertNull(WhitelabelSanitizer::sanitizeCss('body { behavior: url(htc.htc); }'));
    }

    public function testCssRejectsCommentObfuscation(): void
    {
        // Atakujacy moze probowac obejsc przez komentarz CSS.
        $this->assertNull(WhitelabelSanitizer::sanitizeCss('a { background: url(java/* */script:alert(1)); }'));
    }

    public function testCssRejectsOversize(): void
    {
        $huge = str_repeat('a{color:red;}', 5000); // > 50 KB
        $this->assertNull(WhitelabelSanitizer::sanitizeCss($huge));
    }

    // ── Email header HTML ────────────────────────────────────────────────────

    public function testEmailHeaderAllowsBasicTags(): void
    {
        $html = '<div style="background:red;"><strong>Hello</strong> <em>world</em></div>';
        $result = WhitelabelSanitizer::sanitizeEmailHeaderHtml($html);
        $this->assertStringContainsString('<strong>', $result);
        $this->assertStringContainsString('<em>', $result);
    }

    public function testEmailHeaderStripsScript(): void
    {
        $html = '<div>OK</div><script>alert(1)</script>';
        $result = WhitelabelSanitizer::sanitizeEmailHeaderHtml($html);
        $this->assertStringNotContainsString('<script', $result);
        $this->assertStringNotContainsString('alert', $result);
    }

    public function testEmailHeaderStripsOnHandlers(): void
    {
        $html = '<img src="x.png" onerror="alert(1)" alt="x">';
        $result = WhitelabelSanitizer::sanitizeEmailHeaderHtml($html);
        $this->assertStringNotContainsString('onerror', $result);
        $this->assertStringNotContainsString('alert', $result);
    }

    public function testEmailHeaderBlocksJavascriptHref(): void
    {
        $html = '<a href="javascript:alert(1)">click</a>';
        $result = WhitelabelSanitizer::sanitizeEmailHeaderHtml($html);
        $this->assertStringNotContainsString('javascript:alert', $result);
    }

    public function testEmailHeaderStripsIframe(): void
    {
        $html = '<iframe src="evil.example"></iframe><div>OK</div>';
        $result = WhitelabelSanitizer::sanitizeEmailHeaderHtml($html);
        $this->assertStringNotContainsString('<iframe', $result);
        $this->assertStringContainsString('OK', $result);
    }

    public function testEmailHeaderTruncatesOversize(): void
    {
        $html = str_repeat('<p>x</p>', 2000); // > 5000 chars
        $result = WhitelabelSanitizer::sanitizeEmailHeaderHtml($html);
        $this->assertLessThanOrEqual(5000, strlen($result));
    }
}
