<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Translator;
use PHPUnit\Framework\TestCase;

/**
 * Smoke test dla locale-aware email template rendering. Wlasciwa weryfikacja
 * fetchEventCatalogTranslation() wymaga DB (Integration suite); tu sprawdzamy
 * tylko, ze:
 *   - EmailService::queueFromTemplate ma signature z $locale param,
 *   - Translator::withLocale dziala dla bulk send pattern,
 *   - lang/pl + lang/en zachowuja parity (587+ kluczy).
 */
class EmailTemplateLocaleTest extends TestCase
{
    public function test_emailservice_queueFromTemplate_accepts_locale_param(): void
    {
        $ref = new \ReflectionMethod(\App\Helpers\EmailService::class, 'queueFromTemplate');
        $params = $ref->getParameters();
        $names = array_map(fn(\ReflectionParameter $p) => $p->getName(), $params);
        $this->assertContains('locale', $names,
            'EmailService::queueFromTemplate must accept $locale param');
    }

    public function test_withLocale_pattern_for_bulk_send(): void
    {
        // Symulacja petli bulk send do roznych odbiorcow z roznymi locale.
        $recipients = [
            ['email' => 'a@x.test', 'locale' => 'pl'],
            ['email' => 'b@x.test', 'locale' => 'en'],
            ['email' => 'c@x.test', 'locale' => 'pl'],
        ];
        Translator::setLocale('pl');

        $seen = [];
        foreach ($recipients as $r) {
            $seen[] = Translator::withLocale($r['locale'], fn() => Translator::getLocale());
        }
        $this->assertSame(['pl', 'en', 'pl'], $seen);
        $this->assertSame('pl', Translator::getLocale(), 'Bulk send must not leak locale change');
    }

    public function test_pl_and_en_message_files_have_parity(): void
    {
        $pl = require ROOT_PATH . '/lang/pl/messages.php';
        $en = require ROOT_PATH . '/lang/en/messages.php';

        $plKeys = array_keys($pl);
        $enKeys = array_keys($en);

        $missingInEn = array_diff($plKeys, $enKeys);
        $missingInPl = array_diff($enKeys, $plKeys);

        $this->assertSame([], $missingInEn, 'EN missing keys: ' . implode(', ', $missingInEn));
        $this->assertSame([], $missingInPl, 'PL missing keys: ' . implode(', ', $missingInPl));
        $this->assertGreaterThan(580, count($plKeys), 'PL must have 580+ keys after multilang migration');
    }

    public function test_locale_ui_keys_present_in_both_languages(): void
    {
        $pl = require ROOT_PATH . '/lang/pl/messages.php';
        $en = require ROOT_PATH . '/lang/en/messages.php';

        $required = [
            'portal.profile.locale.title',
            'portal.profile.locale.help',
            'portal.profile.locale.save',
            'club.settings.default_locale.title',
            'club.settings.default_locale.help',
            'wizard.default_locale.title',
            'wizard.default_locale.help',
        ];
        foreach ($required as $k) {
            $this->assertArrayHasKey($k, $pl, "PL missing $k");
            $this->assertArrayHasKey($k, $en, "EN missing $k");
            $this->assertNotSame('', $pl[$k]);
            $this->assertNotSame('', $en[$k]);
        }
    }
}
