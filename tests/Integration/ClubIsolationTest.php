<?php

namespace Tests\Integration;

use App\Helpers\ClubContext;
use App\Helpers\Session;
use App\Models\ClubSettingsModel;
use App\Models\FeeRateModel;
use App\Models\MemberModel;

/**
 * @group integration
 *
 * Verifies that club-scoped models properly isolate data between clubs.
 */
class ClubIsolationTest extends TestCase
{
    private int $clubA;
    private int $clubB;

    protected function setUp(): void
    {
        parent::setUp();

        // Start session for ClubContext (suppress headers-already-sent)
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $this->clubA = $this->createTestClub('IsolationTest Club A');
        $this->clubB = $this->createTestClub('IsolationTest Club B');
    }

    // ------------------------------------------------------------------
    // MemberModel scoping
    // ------------------------------------------------------------------

    public function testMemberModelScopedByClub(): void
    {
        $memberA = $this->createTestMember($this->clubA, [
            'first_name' => 'AlicjaA',
            'last_name'  => 'Testowa',
        ]);
        $memberB = $this->createTestMember($this->clubB, [
            'first_name' => 'BogdanB',
            'last_name'  => 'Testowy',
        ]);

        // Set context to club A
        Session::set('club_id', $this->clubA);
        $model = new MemberModel();
        $allA  = $model->findAll();

        $idsA = array_column($allA, 'id');
        $this->assertContains($memberA['id'], $idsA, 'Club A should see its own member');
        $this->assertNotContains($memberB['id'], $idsA, 'Club A must NOT see Club B member');

        // Set context to club B
        Session::set('club_id', $this->clubB);
        $modelB = new MemberModel();
        $allB   = $modelB->findAll();

        $idsB = array_column($allB, 'id');
        $this->assertContains($memberB['id'], $idsB, 'Club B should see its own member');
        $this->assertNotContains($memberA['id'], $idsB, 'Club B must NOT see Club A member');
    }

    public function testMemberFindByIdRespectsScope(): void
    {
        $memberA = $this->createTestMember($this->clubA, ['first_name' => 'Scoped']);

        // Club B context should NOT see Club A member by ID
        Session::set('club_id', $this->clubB);
        $model = new MemberModel();
        $this->assertNull(
            $model->findById($memberA['id']),
            'Club B must not access Club A member by ID'
        );

        // Club A context should see it
        Session::set('club_id', $this->clubA);
        $modelA = new MemberModel();
        $row = $modelA->findById($memberA['id']);
        $this->assertNotNull($row);
        $this->assertEquals('Scoped', $row['first_name']);
    }

    // ------------------------------------------------------------------
    // ClubSettings isolation
    // ------------------------------------------------------------------

    public function testClubSettingsIsolated(): void
    {
        $settingsModel = new ClubSettingsModel();
        $settingsModel->set($this->clubA, 'test_color', 'red');
        $this->createdSettingKeys[] = [$this->clubA, 'test_color'];

        // Club A should get the stored value
        $this->assertEquals('red', $settingsModel->get($this->clubA, 'test_color'));

        // Club B should get the default (not set)
        $this->assertEquals(
            'default_value',
            $settingsModel->get($this->clubB, 'test_color', 'default_value'),
            'Club B must not see Club A settings'
        );
    }

    public function testClubSettingsOverwriteIndependent(): void
    {
        $settingsModel = new ClubSettingsModel();

        $settingsModel->set($this->clubA, 'language', 'pl');
        $settingsModel->set($this->clubB, 'language', 'en');
        $this->createdSettingKeys[] = [$this->clubA, 'language'];
        $this->createdSettingKeys[] = [$this->clubB, 'language'];

        $this->assertEquals('pl', $settingsModel->get($this->clubA, 'language'));
        $this->assertEquals('en', $settingsModel->get($this->clubB, 'language'));
    }

    // ------------------------------------------------------------------
    // FeeRate isolation
    // ------------------------------------------------------------------

    public function testFeeRatesIsolated(): void
    {
        $db = $this->requireDatabase();

        // Insert fee rate for club A
        $stmt = $db->prepare(
            "INSERT INTO fee_rates (club_id, name, amount, period, is_active, created_at)
             VALUES (?, ?, ?, ?, 1, NOW())"
        );
        $stmt->execute([$this->clubA, 'TestFee A', 100.00, 'monthly']);
        $feeIdA = (int) $db->lastInsertId();
        $this->createdFeeRateIds[] = $feeIdA;

        // Insert fee rate for club B
        $stmt->execute([$this->clubB, 'TestFee B', 200.00, 'monthly']);
        $feeIdB = (int) $db->lastInsertId();
        $this->createdFeeRateIds[] = $feeIdB;

        // Club A context: should see only its own fee
        Session::set('club_id', $this->clubA);
        $modelA = new FeeRateModel();
        $feesA  = $modelA->findAll();

        $feeIdsA = array_column($feesA, 'id');
        $this->assertContains($feeIdA, $feeIdsA, 'Club A should see its fee rate');
        $this->assertNotContains($feeIdB, $feeIdsA, 'Club A must NOT see Club B fee rate');

        // Club B context: should see only its own fee
        Session::set('club_id', $this->clubB);
        $modelB = new FeeRateModel();
        $feesB  = $modelB->findAll();

        $feeIdsB = array_column($feesB, 'id');
        $this->assertContains($feeIdB, $feeIdsB, 'Club B should see its fee rate');
        $this->assertNotContains($feeIdA, $feeIdsB, 'Club B must NOT see Club A fee rate');
    }

    public function testFeeRateCountScopedByClub(): void
    {
        $db = $this->requireDatabase();

        $stmt = $db->prepare(
            "INSERT INTO fee_rates (club_id, name, amount, period, is_active, created_at)
             VALUES (?, ?, ?, ?, 1, NOW())"
        );
        $stmt->execute([$this->clubA, 'Count Fee 1', 50.00, 'monthly']);
        $this->createdFeeRateIds[] = (int) $db->lastInsertId();
        $stmt->execute([$this->clubA, 'Count Fee 2', 75.00, 'monthly']);
        $this->createdFeeRateIds[] = (int) $db->lastInsertId();

        Session::set('club_id', $this->clubA);
        $countA = (new FeeRateModel())->count();

        Session::set('club_id', $this->clubB);
        $countB = (new FeeRateModel())->count();

        $this->assertGreaterThanOrEqual(2, $countA);
        $this->assertLessThan($countA, $countB, 'Club B count must be less than Club A count');
    }

    protected function tearDown(): void
    {
        // Clear session state to avoid pollution
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
        }

        parent::tearDown();
    }
}
