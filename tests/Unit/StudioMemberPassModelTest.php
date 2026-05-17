<?php

namespace Tests\Unit;

use App\Helpers\ClubContext;
use App\Helpers\Database;
use App\Models\StudioMemberPassModel;
use App\Sports\Studio\PassExhaustedException;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 *
 * Unit testy logiki karnetow studio (consume / refund / multi-tenant).
 * Uzywa in-memory SQLite z uproszczonym schematem (bez FOR UPDATE / ENUM).
 */
class StudioMemberPassModelTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Uproszczony schemat (SQLite-compatible)
        $this->pdo->exec("CREATE TABLE studio_pass_types (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            club_id INTEGER NOT NULL,
            sport_key TEXT NULL,
            code TEXT NOT NULL,
            name TEXT NOT NULL,
            type TEXT NOT NULL,
            classes_count INTEGER NULL,
            validity_days INTEGER NOT NULL,
            price_cents INTEGER NOT NULL,
            currency TEXT DEFAULT 'PLN',
            active INTEGER DEFAULT 1,
            sort_order INTEGER DEFAULT 0
        )");

        $this->pdo->exec("CREATE TABLE studio_member_passes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            club_id INTEGER NOT NULL,
            member_id INTEGER NOT NULL,
            pass_type_id INTEGER NOT NULL,
            purchased_at TEXT DEFAULT CURRENT_TIMESTAMP,
            valid_from TEXT NOT NULL,
            valid_until TEXT NOT NULL,
            classes_total INTEGER NULL,
            classes_remaining INTEGER NULL,
            status TEXT NOT NULL DEFAULT 'active',
            payment_id INTEGER NULL
        )");

        Database::setInstance($this->pdo);
        ClubContext::set(1);
    }

    protected function tearDown(): void
    {
        Database::setInstance(null);
        ClubContext::clear();
        parent::tearDown();
    }

    private function seedPassType(array $overrides = []): int
    {
        $defaults = [
            'club_id'       => 1,
            'sport_key'     => 'yoga',
            'code'          => 'yoga_4pack_' . uniqid(),
            'name'          => 'Yoga 4-pack',
            'type'          => 'multi_class',
            'classes_count' => 4,
            'validity_days' => 30,
            'price_cents'   => 14000,
        ];
        $row = array_merge($defaults, $overrides);
        $cols = implode(',', array_keys($row));
        $place = implode(',', array_fill(0, count($row), '?'));
        $stmt = $this->pdo->prepare("INSERT INTO studio_pass_types ($cols) VALUES ($place)");
        $stmt->execute(array_values($row));
        return (int)$this->pdo->lastInsertId();
    }

    // ──────────────────────────────────────────────────────
    // purchase()
    // ──────────────────────────────────────────────────────

    public function testPurchaseCreatesPassWithCorrectClassesRemaining(): void
    {
        $typeId = $this->seedPassType(['classes_count' => 8, 'validity_days' => 45]);
        $passId = (new StudioMemberPassModel())->purchase(100, $typeId);

        $row = $this->pdo->query("SELECT * FROM studio_member_passes WHERE id = $passId")->fetch();
        $this->assertSame(8, (int)$row['classes_total']);
        $this->assertSame(8, (int)$row['classes_remaining']);
        $this->assertSame('active', $row['status']);
        $this->assertSame(1, (int)$row['club_id']);
    }

    public function testPurchaseUnlimitedHasNullClassesRemaining(): void
    {
        $typeId = $this->seedPassType(['type' => 'unlimited_period', 'classes_count' => null]);
        $passId = (new StudioMemberPassModel())->purchase(100, $typeId);
        $row = $this->pdo->query("SELECT * FROM studio_member_passes WHERE id = $passId")->fetch();
        $this->assertNull($row['classes_total']);
        $this->assertNull($row['classes_remaining']);
    }

    // ──────────────────────────────────────────────────────
    // consumeOne() — happy path
    // ──────────────────────────────────────────────────────

    public function testConsumeOneDecrementsRemaining(): void
    {
        $typeId = $this->seedPassType(['classes_count' => 4]);
        $passId = (new StudioMemberPassModel())->purchase(100, $typeId);

        (new StudioMemberPassModel())->consumeOne($passId);

        $row = $this->pdo->query("SELECT * FROM studio_member_passes WHERE id = $passId")->fetch();
        $this->assertSame(3, (int)$row['classes_remaining']);
        $this->assertSame('active', $row['status']);
    }

    public function testConsumeOneMarksExhaustedAtZero(): void
    {
        $typeId = $this->seedPassType(['classes_count' => 1]);
        $passId = (new StudioMemberPassModel())->purchase(100, $typeId);
        (new StudioMemberPassModel())->consumeOne($passId);
        $row = $this->pdo->query("SELECT * FROM studio_member_passes WHERE id = $passId")->fetch();
        $this->assertSame(0, (int)$row['classes_remaining']);
        $this->assertSame('exhausted', $row['status']);
    }

    public function testConsumeOneOnUnlimitedDoesNotDecrement(): void
    {
        $typeId = $this->seedPassType(['type' => 'unlimited_period', 'classes_count' => null]);
        $passId = (new StudioMemberPassModel())->purchase(100, $typeId);
        (new StudioMemberPassModel())->consumeOne($passId);
        $row = $this->pdo->query("SELECT * FROM studio_member_passes WHERE id = $passId")->fetch();
        $this->assertNull($row['classes_remaining']);
        $this->assertSame('active', $row['status']);
    }

    // ──────────────────────────────────────────────────────
    // consumeOne() — failure
    // ──────────────────────────────────────────────────────

    public function testConsumeExhaustedThrows(): void
    {
        $typeId = $this->seedPassType(['classes_count' => 1]);
        $passId = (new StudioMemberPassModel())->purchase(100, $typeId);

        // pierwszy consume ok, drugi throw
        (new StudioMemberPassModel())->consumeOne($passId);
        $this->expectException(PassExhaustedException::class);
        (new StudioMemberPassModel())->consumeOne($passId);
    }

    public function testConsumeExpiredThrowsAndMarksExpired(): void
    {
        $typeId = $this->seedPassType(['classes_count' => 4]);
        $passId = (new StudioMemberPassModel())->purchase(100, $typeId);
        // Recznie zepsuj valid_until (past)
        $past = date('Y-m-d', strtotime('-1 day'));
        $this->pdo->exec("UPDATE studio_member_passes SET valid_until='$past' WHERE id=$passId");

        try {
            (new StudioMemberPassModel())->consumeOne($passId);
            $this->fail('Expected PassExhaustedException');
        } catch (PassExhaustedException) {
            // expected
        }
        $row = $this->pdo->query("SELECT * FROM studio_member_passes WHERE id = $passId")->fetch();
        $this->assertSame('expired', $row['status']);
    }

    // ──────────────────────────────────────────────────────
    // refundOne()
    // ──────────────────────────────────────────────────────

    public function testRefundOneRestoresClass(): void
    {
        $typeId = $this->seedPassType(['classes_count' => 4]);
        $passId = (new StudioMemberPassModel())->purchase(100, $typeId);
        (new StudioMemberPassModel())->consumeOne($passId);

        (new StudioMemberPassModel())->refundOne($passId);
        $row = $this->pdo->query("SELECT * FROM studio_member_passes WHERE id = $passId")->fetch();
        $this->assertSame(4, (int)$row['classes_remaining']);
        $this->assertSame('active', $row['status']);
    }

    public function testRefundOneAfterExhaustionReactivates(): void
    {
        $typeId = $this->seedPassType(['classes_count' => 1]);
        $passId = (new StudioMemberPassModel())->purchase(100, $typeId);
        (new StudioMemberPassModel())->consumeOne($passId); // → exhausted
        $row = $this->pdo->query("SELECT * FROM studio_member_passes WHERE id = $passId")->fetch();
        $this->assertSame('exhausted', $row['status']);

        (new StudioMemberPassModel())->refundOne($passId);
        $row = $this->pdo->query("SELECT * FROM studio_member_passes WHERE id = $passId")->fetch();
        $this->assertSame(1, (int)$row['classes_remaining']);
        $this->assertSame('active', $row['status']);
    }

    public function testRefundOneCannotExceedTotal(): void
    {
        $typeId = $this->seedPassType(['classes_count' => 4]);
        $passId = (new StudioMemberPassModel())->purchase(100, $typeId);
        // bez consume, refund nie podnosi powyzej total
        (new StudioMemberPassModel())->refundOne($passId);
        $row = $this->pdo->query("SELECT * FROM studio_member_passes WHERE id = $passId")->fetch();
        $this->assertSame(4, (int)$row['classes_remaining']);
    }

    // ──────────────────────────────────────────────────────
    // Multi-tenant strict
    // ──────────────────────────────────────────────────────

    public function testPassFromAnotherClubCannotBeConsumed(): void
    {
        // Klub A (id=1) tworzy pass
        $typeId = $this->seedPassType(['club_id' => 1]);
        $passId = (new StudioMemberPassModel())->purchase(100, $typeId);

        // Przelacz na klub B (id=2)
        ClubContext::set(2);

        $this->expectException(PassExhaustedException::class);
        (new StudioMemberPassModel())->consumeOne($passId);
    }

    public function testActiveForMemberOnlyReturnsCurrentClubPass(): void
    {
        // Klub 1: pass aktywny
        $type1 = $this->seedPassType(['club_id' => 1, 'code' => 'k1']);
        $pass1 = (new StudioMemberPassModel())->purchase(100, $type1);

        // Klub 2: inny pass (insert recznie, bo ClubScopedModel insertuje z scope)
        ClubContext::set(2);
        $type2 = $this->seedPassType(['club_id' => 2, 'code' => 'k2']);
        $pass2 = (new StudioMemberPassModel())->purchase(100, $type2);

        // Klub 1 widzi swoj pass
        ClubContext::set(1);
        $active = (new StudioMemberPassModel())->activeForMember(100, 'yoga');
        $this->assertNotNull($active);
        $this->assertSame($pass1, (int)$active['id']);

        // Klub 2 widzi swoj
        ClubContext::set(2);
        $active = (new StudioMemberPassModel())->activeForMember(100, 'yoga');
        $this->assertNotNull($active);
        $this->assertSame($pass2, (int)$active['id']);
    }
}
