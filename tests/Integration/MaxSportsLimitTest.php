<?php

namespace Tests\Integration;

use App\Models\SubscriptionModel;

/**
 * @group integration
 *
 * Verifies that subscription_plans.max_sports is enforced when activating
 * club sport sections. Reproduces the bug fixed in A1: previously
 * SportsController::enable() called addSportToClub() without checking the
 * subscription plan's sport limit.
 */
class MaxSportsLimitTest extends TestCase
{
    public function testIsOverSportLimitFalseWhenUnderLimit(): void
    {
        $db     = $this->requireDatabase();
        $clubId = $this->createTestClub('MaxSports Test Under');

        $planId = $this->createTestPlan(maxSports: 5);
        $this->createTestSubscription($clubId, $planId);

        $this->activateSports($clubId, 2);

        $this->assertFalse(
            (new SubscriptionModel())->isOverSportLimit($clubId),
            '2 of 5 sports should not be over limit'
        );
    }

    public function testIsOverSportLimitTrueWhenAtLimit(): void
    {
        $db     = $this->requireDatabase();
        $clubId = $this->createTestClub('MaxSports Test AtLimit');

        $planId = $this->createTestPlan(maxSports: 3);
        $this->createTestSubscription($clubId, $planId);

        $this->activateSports($clubId, 3);

        $this->assertTrue(
            (new SubscriptionModel())->isOverSportLimit($clubId),
            '3 of 3 sports should be over limit (>=)'
        );
    }

    public function testIsOverSportLimitFalseWhenUnlimited(): void
    {
        $db     = $this->requireDatabase();
        $clubId = $this->createTestClub('MaxSports Test Unlimited');

        $planId = $this->createTestPlan(maxSports: null);
        $this->createTestSubscription($clubId, $planId);

        $this->activateSports($clubId, 30);

        $this->assertFalse(
            (new SubscriptionModel())->isOverSportLimit($clubId),
            'NULL max_sports means unlimited'
        );
    }

    public function testSportLimitInfoReturnsCounts(): void
    {
        $db     = $this->requireDatabase();
        $clubId = $this->createTestClub('MaxSports Test Info');

        $planId = $this->createTestPlan(maxSports: 10);
        $this->createTestSubscription($clubId, $planId);
        $this->activateSports($clubId, 4);

        $info = (new SubscriptionModel())->sportLimitInfo($clubId);

        $this->assertEquals(10, $info['limit']);
        $this->assertEquals(4,  $info['used']);
        $this->assertEquals(6,  $info['remaining']);
    }

    public function testInactiveSportsDoNotCountTowardLimit(): void
    {
        $db     = $this->requireDatabase();
        $clubId = $this->createTestClub('MaxSports Test Inactive');

        $planId = $this->createTestPlan(maxSports: 2);
        $this->createTestSubscription($clubId, $planId);

        // Activate 3 sports, then deactivate 1 — should be 2 active = at limit
        $sportIds = $this->activateSports($clubId, 3);
        $db->prepare("UPDATE club_sports SET is_active = 0 WHERE club_id = ? AND sport_id = ?")
           ->execute([$clubId, $sportIds[0]]);

        $info = (new SubscriptionModel())->sportLimitInfo($clubId);
        $this->assertEquals(2, $info['used'], 'Only active sports count');
    }

    // ------------------------------------------------------------------
    // helpers
    // ------------------------------------------------------------------

    private array $createdPlanIds = [];
    private array $createdSubIds  = [];
    private array $createdClubSportIds = [];

    private function createTestPlan(?int $maxSports = null, ?int $maxMembers = 100): int
    {
        $db   = $this->requireDatabase();
        $code = 'TEST_' . bin2hex(random_bytes(4));
        $stmt = $db->prepare(
            "INSERT INTO subscription_plans
             (code, name, max_members, max_sports, price_monthly, price_yearly, features, is_active, sort_order)
             VALUES (?, ?, ?, ?, 0, 0, '{}', 1, 250)"
        );
        $stmt->execute([$code, 'Test plan ' . $code, $maxMembers, $maxSports]);
        $id = (int)$db->lastInsertId();
        $this->createdPlanIds[] = $id;
        return $id;
    }

    private function createTestSubscription(int $clubId, int $planId): int
    {
        $db = $this->requireDatabase();
        $stmt = $db->prepare(
            "INSERT INTO club_subscriptions
             (club_id, plan_id, valid_until, status, billing_cycle)
             VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'active', 'monthly')"
        );
        $stmt->execute([$clubId, $planId]);
        $id = (int)$db->lastInsertId();
        $this->createdSubIds[] = $id;
        return $id;
    }

    /**
     * Activates N existing sports (from the seed catalog) for the club.
     * @return int[] activated sport IDs
     */
    private function activateSports(int $clubId, int $n): array
    {
        $db = $this->requireDatabase();
        $sportIds = $db->query("SELECT id FROM sports ORDER BY id LIMIT {$n}")
                       ->fetchAll(\PDO::FETCH_COLUMN);
        $stmt = $db->prepare(
            "INSERT INTO club_sports (club_id, sport_id, is_active, started_at)
             VALUES (?, ?, 1, CURDATE())
             ON DUPLICATE KEY UPDATE is_active = 1"
        );
        foreach ($sportIds as $sid) {
            $stmt->execute([$clubId, $sid]);
            $this->createdClubSportIds[] = (int)$db->lastInsertId();
        }
        return array_map('intval', $sportIds);
    }

    protected function tearDown(): void
    {
        if ($this->db !== null) {
            foreach ($this->createdClubSportIds as $id) {
                $this->db->prepare("DELETE FROM club_sports WHERE id = ?")->execute([$id]);
            }
            // Cleanup any remaining club_sports rows for the test clubs
            foreach ($this->createdClubIds as $cid) {
                $this->db->prepare("DELETE FROM club_sports WHERE club_id = ?")->execute([$cid]);
            }
            foreach ($this->createdSubIds as $id) {
                $this->db->prepare("DELETE FROM club_subscriptions WHERE id = ?")->execute([$id]);
            }
            foreach ($this->createdPlanIds as $id) {
                $this->db->prepare("DELETE FROM subscription_plans WHERE id = ?")->execute([$id]);
            }
        }
        parent::tearDown();
    }
}
