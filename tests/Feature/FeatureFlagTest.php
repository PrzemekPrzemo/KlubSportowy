<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\Feature;

/**
 * Feature: per-klub feature flags (`Feature::enabled()`).
 *
 * Logika rozstrzygania:
 *   1. override w `club_feature_overrides` (jeśli istnieje + nie wygasł) → priority
 *   2. inaczej default z `feature_flags_catalog.default_in_plan[plan_code]`
 *   3. flaga nieznana / plan nieznany → false (fail-closed)
 *
 * Wszystkie testy w transakcji więc seedowane override-y znikają w tearDown.
 */
class FeatureFlagTest extends FeatureTestCase
{
    /** Tworzy parę plan/subskrypcję dla klubu, zwraca plan_id. */
    private function attachPlan(int $clubId, string $planCode): int
    {
        // 1. Plan
        $stmt = $this->pdo->prepare(
            "INSERT INTO subscription_plans (code, name, price_monthly, is_active, created_at)
             VALUES (?, ?, 0, 1, NOW())
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)"
        );
        // Niektóre schematy mogą nie mieć created_at lub price_monthly — wykryj minimalne kolumny.
        try {
            $stmt->execute([$planCode, "Test plan {$planCode}"]);
            $planId = (int)$this->pdo->lastInsertId();
        } catch (\PDOException) {
            // fallback: minimum cols
            $this->pdo->prepare("INSERT IGNORE INTO subscription_plans (code, name) VALUES (?, ?)")
                ->execute([$planCode, "Test plan {$planCode}"]);
            $planId = (int)$this->pdo->query(
                "SELECT id FROM subscription_plans WHERE code = " . $this->pdo->quote($planCode)
            )->fetchColumn();
        }

        // 2. Subskrypcja
        try {
            $this->pdo->prepare(
                "INSERT INTO club_subscriptions (club_id, plan_id, status, started_at)
                 VALUES (?, ?, 'active', NOW())"
            )->execute([$clubId, $planId]);
        } catch (\PDOException) {
            // fallback bez status/started_at
            $this->pdo->prepare(
                "INSERT INTO club_subscriptions (club_id, plan_id) VALUES (?, ?)"
            )->execute([$clubId, $planId]);
        }

        return $planId;
    }

    private function ensureCatalogFlag(string $code, array $defaultsByPlan): void
    {
        $json = json_encode($defaultsByPlan);
        $this->pdo->prepare(
            "INSERT INTO feature_flags_catalog (code, name, category, default_in_plan, is_active, sort_order)
             VALUES (?, ?, 'test', ?, 1, 999)
             ON DUPLICATE KEY UPDATE default_in_plan = VALUES(default_in_plan), is_active = 1"
        )->execute([$code, "Test flag {$code}", $json]);
    }

    public function testFlagEnabledWhenPlanIncludesIt(): void
    {
        $clubId = $this->createClub('FF Plan Enabled');
        $this->asClub($clubId);
        $this->attachPlan($clubId, 'club');

        // pdf_export jest TRUE dla planu "club" w seedzie migracji 056.
        // Jeśli seed nie był zaaplikowany — zapewnij flagę w katalogu.
        $this->ensureCatalogFlag('pdf_export', [
            'starter' => false, 'club' => true, 'enterprise' => true,
        ]);
        Feature::clearCache();

        $this->assertTrue(
            Feature::enabled('pdf_export', $clubId),
            'pdf_export musi być włączona dla planu "club"'
        );
    }

    public function testFlagDisabledWhenPlanExcludesIt(): void
    {
        $clubId = $this->createClub('FF Plan Disabled');
        $this->asClub($clubId);
        $this->attachPlan($clubId, 'starter');

        $this->ensureCatalogFlag('pdf_export', [
            'starter' => false, 'club' => true,
        ]);
        Feature::clearCache();

        $this->assertFalse(
            Feature::enabled('pdf_export', $clubId),
            'pdf_export musi być wyłączona dla planu "starter"'
        );
    }

    public function testOverrideTakesPriorityOverPlanDefault(): void
    {
        $clubId = $this->createClub('FF Override Wins');
        $this->asClub($clubId);
        $this->attachPlan($clubId, 'starter'); // plan = false

        $this->ensureCatalogFlag('pdf_export', ['starter' => false, 'club' => true]);

        // Override = true (np. trial-promo)
        $this->pdo->prepare(
            "INSERT INTO club_feature_overrides (club_id, feature_code, enabled, reason, created_at)
             VALUES (?, ?, 1, 'trial', NOW())"
        )->execute([$clubId, 'pdf_export']);

        Feature::clearCache();
        $this->assertTrue(
            Feature::enabled('pdf_export', $clubId),
            'Override TRUE musi nadpisać plan-default FALSE'
        );
    }

    public function testExpiredOverrideIsIgnored(): void
    {
        $clubId = $this->createClub('FF Expired Override');
        $this->asClub($clubId);
        $this->attachPlan($clubId, 'starter');

        $this->ensureCatalogFlag('pdf_export', ['starter' => false]);

        // Override TRUE ale wygasł (expires_at < NOW)
        $this->pdo->prepare(
            "INSERT INTO club_feature_overrides (club_id, feature_code, enabled, reason, expires_at, created_at)
             VALUES (?, ?, 1, 'expired_trial', DATE_SUB(NOW(), INTERVAL 1 DAY), NOW())"
        )->execute([$clubId, 'pdf_export']);

        Feature::clearCache();
        $this->assertFalse(
            Feature::enabled('pdf_export', $clubId),
            'Wygasły override musi być ignorowany — fallback do plan-default (false)'
        );
    }

    public function testUnknownFlagFailsClosed(): void
    {
        $clubId = $this->createClub('FF Unknown');
        $this->asClub($clubId);
        $this->attachPlan($clubId, 'enterprise');

        Feature::clearCache();
        $this->assertFalse(
            Feature::enabled('nieistniejaca_flaga_xyz', $clubId),
            'Nieznana flaga musi zwracać false (fail-closed)'
        );
    }

    public function testNoClubContextReturnsFalse(): void
    {
        // Brak active club context.
        \App\Helpers\ClubContext::clear();
        $this->assertFalse(
            Feature::enabled('pdf_export'),
            'Bez kontekstu klubu — fail closed'
        );
    }
}
