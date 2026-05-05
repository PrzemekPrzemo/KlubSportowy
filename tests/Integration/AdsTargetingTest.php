<?php

namespace Tests\Integration;

use App\Models\AdModel;

/**
 * @group integration
 *
 * Integration tests for D3 — AdModel::activeForTarget() audience filtering.
 *
 * Verifies that ads with audience_type ∈ {all, club, sport, member, plan}
 * are surfaced only when the caller's context matches the row's targeting
 * fields.
 */
class AdsTargetingTest extends TestCase
{
    private array $createdAdIds = [];
    private array $createdSportIds = [];

    public function testAllAudienceWithGlobalClubVisibleEverywhere(): void
    {
        $db = $this->requireDatabase();
        $id = $this->createAd([
            'title'         => 'Global universal',
            'audience_type' => 'all',
            'club_id'       => null,
        ]);

        $rows = (new AdModel())->activeForTarget('club_panel');
        $this->assertContains($id, array_column($rows, 'id'));

        $rows = (new AdModel())->activeForTarget('club_panel', 999999);
        $this->assertContains($id, array_column($rows, 'id'),
            'Global ad should be visible from any club context');
    }

    public function testClubAudienceOnlyVisibleForThatClub(): void
    {
        $db     = $this->requireDatabase();
        $clubA  = $this->createTestClub('AdsTarget A');
        $clubB  = $this->createTestClub('AdsTarget B');

        $id = $this->createAd([
            'title'         => 'Tylko klub A',
            'audience_type' => 'club',
            'club_id'       => $clubA,
        ]);

        $rowsA = (new AdModel())->activeForTarget('club_panel', $clubA);
        $this->assertContains($id, array_column($rowsA, 'id'));

        $rowsB = (new AdModel())->activeForTarget('club_panel', $clubB);
        $this->assertNotContains($id, array_column($rowsB, 'id'));
    }

    public function testSportAudienceMatchesOnlyWhenSportIdMatches(): void
    {
        $db = $this->requireDatabase();
        $sportId = (int)$db->query("SELECT id FROM sports LIMIT 1")->fetchColumn();
        $otherSportId = (int)$db->query("SELECT id FROM sports WHERE id != {$sportId} LIMIT 1")->fetchColumn();

        $id = $this->createAd([
            'title'         => 'Tylko ten sport',
            'audience_type' => 'sport',
            'sport_id'      => $sportId,
        ]);

        $hit  = (new AdModel())->activeForTarget('member_portal', null, null, $sportId);
        $miss = (new AdModel())->activeForTarget('member_portal', null, null, $otherSportId);

        $this->assertContains($id, array_column($hit, 'id'));
        $this->assertNotContains($id, array_column($miss, 'id'));
    }

    public function testMemberAudienceMatchesOnlyTargetMember(): void
    {
        $db    = $this->requireDatabase();
        $club  = $this->createTestClub('AdsTarget Member');
        $m1    = $this->createTestMember($club, ['first_name' => 'Tar', 'last_name' => 'Get']);
        $m2    = $this->createTestMember($club, ['first_name' => 'Other', 'last_name' => 'Person']);

        $id = $this->createAd([
            'title'         => 'Reklama personalizowana',
            'audience_type' => 'member',
            'member_id'     => $m1['id'],
        ]);

        $hit  = (new AdModel())->activeForTarget('member_portal', $club, $m1['id']);
        $miss = (new AdModel())->activeForTarget('member_portal', $club, $m2['id']);

        $this->assertContains($id, array_column($hit, 'id'));
        $this->assertNotContains($id, array_column($miss, 'id'));
    }

    public function testPlanAudienceWithMatchingCode(): void
    {
        $db = $this->requireDatabase();
        $id = $this->createAd([
            'title'         => 'Tylko basic',
            'audience_type' => 'plan',
            'plan_min'      => 'basic',
        ]);

        $hit  = (new AdModel())->activeForTarget('club_panel', null, null, null, 'basic');
        $miss = (new AdModel())->activeForTarget('club_panel', null, null, null, 'premium');

        $this->assertContains($id, array_column($hit, 'id'));
        $this->assertNotContains($id, array_column($miss, 'id'),
            'Plan-targeted ad with code basic must NOT match a different plan code');
    }

    public function testInactiveAdIsNotReturned(): void
    {
        $db = $this->requireDatabase();
        $id = $this->createAd([
            'title'         => 'Wylaczona',
            'audience_type' => 'all',
            'is_active'     => 0,
        ]);

        $rows = (new AdModel())->activeForTarget('club_panel');
        $this->assertNotContains($id, array_column($rows, 'id'));
    }

    public function testExpiredAdIsNotReturned(): void
    {
        $db = $this->requireDatabase();
        $id = $this->createAd([
            'title'         => 'Wczorajsza',
            'audience_type' => 'all',
            'start_date'    => date('Y-m-d', strtotime('-30 days')),
            'end_date'      => date('Y-m-d', strtotime('-1 day')),
        ]);

        $rows = (new AdModel())->activeForTarget('club_panel');
        $this->assertNotContains($id, array_column($rows, 'id'));
    }

    // ------------------------------------------------------------------

    private function createAd(array $overrides): int
    {
        $db = $this->requireDatabase();
        $row = array_merge([
            'title'         => 'Test ad ' . bin2hex(random_bytes(3)),
            'club_id'       => null,
            'sport_id'      => null,
            'member_id'     => null,
            'audience_type' => 'all',
            'image_path'    => null,
            'link_url'      => null,
            'target'        => 'club_panel',
            'position'      => 'top_banner',
            'plan_min'      => null,
            'start_date'    => null,
            'end_date'      => null,
            'is_active'     => 1,
        ], $overrides);

        $cols  = implode('`, `', array_keys($row));
        $holds = implode(', ', array_fill(0, count($row), '?'));
        $stmt  = $db->prepare("INSERT INTO ads (`{$cols}`) VALUES ({$holds})");
        $stmt->execute(array_values($row));
        $id = (int)$db->lastInsertId();
        $this->createdAdIds[] = $id;
        return $id;
    }

    protected function tearDown(): void
    {
        if ($this->db !== null) {
            foreach ($this->createdAdIds as $id) {
                $this->db->prepare("DELETE FROM ads WHERE id = ?")->execute([$id]);
            }
        }
        parent::tearDown();
    }
}
