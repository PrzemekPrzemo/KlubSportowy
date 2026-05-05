<?php

namespace Tests\Integration;

use App\Helpers\Database;
use App\Models\MemberNotificationPrefModel;

/**
 * @group integration
 *
 * Faza S.0 — RODO compliance: zawodnik z opt-out NIE może dostać powiadomień.
 *
 * Testuje:
 *   - global opt-out (template_type=NULL) blokuje wszystkie typy
 *   - per-template opt-out blokuje tylko wskazany typ
 *   - per-channel opt-out (email vs sms) działa selektywnie
 *   - brak rekordu = NIE jest opted_out (default opt-in)
 */
class NotificationOptOutTest extends TestCase
{
    public function testNoPreferenceDefaultsToOptIn(): void
    {
        $db = $this->requireDatabase();
        $clubId = $this->createTestClub('Opt-in default');
        $member = $this->createTestMember($clubId);

        $prefs = new MemberNotificationPrefModel();
        $this->assertFalse(
            $prefs->isOptedOut((int)$member['id'], 'fee_reminder', 'email'),
            'Brak rekordu w member_notification_prefs MUSI oznaczać opt-IN (otrzymuje)'
        );
    }

    public function testGlobalOptOutBlocksAllTemplates(): void
    {
        $db = $this->requireDatabase();
        $clubId = $this->createTestClub('Global opt-out');
        $member = $this->createTestMember($clubId);

        $prefs = new MemberNotificationPrefModel();
        $prefs->setPreference((int)$member['id'], $clubId, null /* global */, 'both', true);

        $this->assertTrue(
            $prefs->isOptedOut((int)$member['id'], 'fee_reminder', 'email'),
            'Global opt-out musi blokować fee_reminder'
        );
        $this->assertTrue(
            $prefs->isOptedOut((int)$member['id'], 'license_expiry', 'email'),
            'Global opt-out musi blokować również inne typy (license_expiry)'
        );
        $this->assertTrue(
            $prefs->isOptedOut((int)$member['id'], 'fee_reminder', 'sms'),
            'Global opt-out musi blokować również SMS'
        );
    }

    public function testPerTemplateOptOutOnlyBlocksThatTemplate(): void
    {
        $db = $this->requireDatabase();
        $clubId = $this->createTestClub('Per-template opt-out');
        $member = $this->createTestMember($clubId);

        $prefs = new MemberNotificationPrefModel();
        $prefs->setPreference((int)$member['id'], $clubId, 'fee_reminder', 'both', true);

        $this->assertTrue(
            $prefs->isOptedOut((int)$member['id'], 'fee_reminder', 'email'),
            'Per-template opt-out blokuje wskazany template'
        );
        $this->assertFalse(
            $prefs->isOptedOut((int)$member['id'], 'license_expiry', 'email'),
            'Per-template opt-out NIE może blokować innych template'
        );
    }

    public function testChannelSpecificOptOut(): void
    {
        $db = $this->requireDatabase();
        $clubId = $this->createTestClub('Channel opt-out');
        $member = $this->createTestMember($clubId);

        $prefs = new MemberNotificationPrefModel();
        // Wycisz tylko SMS dla fee_reminder
        $prefs->setPreference((int)$member['id'], $clubId, 'fee_reminder', 'sms', true);

        $this->assertTrue(
            $prefs->isOptedOut((int)$member['id'], 'fee_reminder', 'sms'),
            'SMS-only opt-out blokuje SMS'
        );
        $this->assertFalse(
            $prefs->isOptedOut((int)$member['id'], 'fee_reminder', 'email'),
            'SMS-only opt-out NIE może blokować email'
        );
    }
}
