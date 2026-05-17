<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Session;
use App\Helpers\Translator;
use PHPUnit\Framework\TestCase;

/**
 * Cascade resolve dla Translator::setLocaleForUser:
 *   member -> user -> session -> club -> Accept-Language -> pl
 *
 * Test izolowany od DB — wszystkie fetchColumn() zwracaja null (best-effort fail),
 * wiec cascade spada do warstw 3-6 (session / Accept-Language / fallback).
 */
class LocaleCascadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset Translator static state przez setLocale (czyszczenie cache).
        Translator::setLocale('pl');
        // Wyczysc session locale + Accept-Language.
        if (!session_id()) {
            @session_start();
        }
        $_SESSION = [];
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    }

    public function test_setLocale_only_accepts_whitelisted_locales(): void
    {
        Translator::setLocale('en');
        $this->assertSame('en', Translator::getLocale());

        Translator::setLocale('de');  // unsupported
        $this->assertSame('pl', Translator::getLocale(), 'Unsupported locale should fall back to pl');

        Translator::setLocale('PL');  // case-insensitive
        $this->assertSame('pl', Translator::getLocale());

        Translator::setLocale('');
        $this->assertSame('pl', Translator::getLocale());
    }

    public function test_setLocaleForUser_uses_accept_language_when_nothing_else(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';
        // No DB available in test bootstrap -> fetchColumn returns null -> cascade
        // falls through to Accept-Language (layer 5).
        $resolved = Translator::setLocaleForUser(null, null, null);
        $this->assertSame('en', $resolved);

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'pl-PL,pl;q=0.9';
        $resolved = Translator::setLocaleForUser(null, null, null);
        $this->assertSame('pl', $resolved);
    }

    public function test_setLocaleForUser_falls_back_to_pl_when_no_signals(): void
    {
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $resolved = Translator::setLocaleForUser(null, null, null);
        $this->assertSame('pl', $resolved);
    }

    public function test_setLocaleForUser_uses_session_locale_when_no_member(): void
    {
        Session::set('locale', 'en');
        $resolved = Translator::setLocaleForUser(null, null, null);
        $this->assertSame('en', $resolved);
    }

    public function test_session_locale_ignored_if_unsupported(): void
    {
        Session::set('locale', 'de');  // not whitelisted
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $resolved = Translator::setLocaleForUser(null, null, null);
        $this->assertSame('pl', $resolved, 'Unsupported session locale should be ignored');
    }

    public function test_withLocale_temporarily_switches_and_restores(): void
    {
        Translator::setLocale('pl');
        $this->assertSame('pl', Translator::getLocale());

        $result = Translator::withLocale('en', function () {
            return Translator::getLocale();
        });

        $this->assertSame('en', $result, 'withLocale callback should see new locale');
        $this->assertSame('pl', Translator::getLocale(), 'Locale should be restored after withLocale');
    }

    public function test_withLocale_restores_even_on_exception(): void
    {
        Translator::setLocale('pl');
        try {
            Translator::withLocale('en', function () {
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException) {
            // expected
        }
        $this->assertSame('pl', Translator::getLocale(), 'Locale restored even on exception');
    }

    public function test_withLocale_rejects_unsupported_locale(): void
    {
        Translator::setLocale('pl');
        $seen = Translator::withLocale('de', fn() => Translator::getLocale());
        $this->assertSame('pl', $seen, 'Unsupported locale param should fall back to pl');
    }

    public function test_translation_falls_back_to_key_when_missing(): void
    {
        Translator::setLocale('pl');
        $this->assertSame('this.key.does.not.exist', Translator::t('this.key.does.not.exist'));
    }

    public function test_t_replaces_named_params(): void
    {
        Translator::setLocale('pl');
        // Use a known key with :param style (portal.dash.days_short = ":days dni")
        $out = Translator::t('portal.dash.days_short', ['days' => 14]);
        $this->assertStringContainsString('14', $out);
    }

    public function test_supported_locales_constant_is_pl_and_en(): void
    {
        $this->assertSame(['pl', 'en'], Translator::SUPPORTED);
        $this->assertSame('pl', Translator::FALLBACK);
    }
}
