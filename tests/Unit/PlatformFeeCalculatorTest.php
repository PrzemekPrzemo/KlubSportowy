<?php

namespace Tests\Unit;

use App\Helpers\Payments\PlatformFeeCalculator;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 *
 * Testy hierarchii reguł i matematyki PlatformFeeCalculator.
 *
 * Używamy SQLite in-memory — odzwierciedlamy minimalny schemat
 * z migracji 092 + club_subscriptions/subscription_plans (do testów
 * scope='plan').
 */
class PlatformFeeCalculatorTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec("CREATE TABLE platform_fee_rules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            scope TEXT,
            plan_code TEXT,
            club_id INTEGER,
            fee_percent REAL,
            fee_fixed_cents INTEGER DEFAULT 0,
            min_fee_cents INTEGER DEFAULT 0,
            max_fee_cents INTEGER,
            effective_from TEXT,
            effective_until TEXT,
            active INTEGER DEFAULT 1
        )");
        $this->pdo->exec("CREATE TABLE subscription_plans (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT
        )");
        $this->pdo->exec("CREATE TABLE club_subscriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            club_id INTEGER,
            plan_id INTEGER
        )");

        // Seed default global rule = 2%
        $this->pdo->exec("INSERT INTO platform_fee_rules
            (scope, fee_percent, effective_from, active)
            VALUES ('global', 2.00, '2020-01-01', 1)");
    }

    public function testGlobalDefault2Percent(): void
    {
        $calc = new PlatformFeeCalculator($this->pdo);
        $r = $calc->calculate(clubId: 1, grossCents: 10000); // 100 PLN
        $this->assertSame(200, $r['fee_cents']);              // 2 PLN
        $this->assertSame(9800, $r['club_net_cents']);
        $this->assertSame('global', $r['rule_scope']);
    }

    public function testRoundingHalfUp(): void
    {
        // 33 grosze * 2% = 0.66 → round half-up = 1
        $calc = new PlatformFeeCalculator($this->pdo);
        $r = $calc->calculate(1, 33);
        $this->assertSame(1, $r['fee_cents']);
    }

    public function testPlanOverrideEnterprise1Percent(): void
    {
        // Plan enterprise = 1%, klub 7 na planie enterprise.
        $this->pdo->exec("INSERT INTO subscription_plans (id, code) VALUES (10, 'enterprise')");
        $this->pdo->exec("INSERT INTO club_subscriptions (club_id, plan_id) VALUES (7, 10)");
        $this->pdo->exec("INSERT INTO platform_fee_rules
            (scope, plan_code, fee_percent, effective_from, active)
            VALUES ('plan', 'enterprise', 1.00, '2020-01-01', 1)");

        $calc = new PlatformFeeCalculator($this->pdo);
        $r = $calc->calculate(7, 10000);
        $this->assertSame(100, $r['fee_cents']);   // 1 PLN (1%)
        $this->assertSame('plan', $r['rule_scope']);

        // Inny klub bez planu enterprise → nadal global 2%
        $r2 = $calc->calculate(99, 10000);
        $this->assertSame(200, $r2['fee_cents']);
        $this->assertSame('global', $r2['rule_scope']);
    }

    public function testClubOverrideWinsOverGlobalAndPlan(): void
    {
        $this->pdo->exec("INSERT INTO subscription_plans (id, code) VALUES (10, 'enterprise')");
        $this->pdo->exec("INSERT INTO club_subscriptions (club_id, plan_id) VALUES (5, 10)");
        $this->pdo->exec("INSERT INTO platform_fee_rules
            (scope, plan_code, fee_percent, effective_from, active)
            VALUES ('plan', 'enterprise', 1.00, '2020-01-01', 1)");
        // Klub 5 ma negocjowany override 0.5%
        $this->pdo->exec("INSERT INTO platform_fee_rules
            (scope, club_id, fee_percent, effective_from, active)
            VALUES ('club_override', 5, 0.50, '2020-01-01', 1)");

        $calc = new PlatformFeeCalculator($this->pdo);
        $r = $calc->calculate(5, 10000);
        $this->assertSame(50, $r['fee_cents']);    // 0.50 PLN
        $this->assertSame('club_override', $r['rule_scope']);
    }

    public function testMinFeeCapApplied(): void
    {
        // 2% z 100 groszy = 2, ale min=50 → fee = 50
        $this->pdo->exec("UPDATE platform_fee_rules SET min_fee_cents = 50 WHERE scope='global'");
        $calc = new PlatformFeeCalculator($this->pdo);
        $r = $calc->calculate(1, 100);
        $this->assertSame(50, $r['fee_cents']);
    }

    public function testMaxFeeCapApplied(): void
    {
        // 2% z 1 000 000 groszy = 20 000, ale max=10 000 → fee = 10 000
        $this->pdo->exec("UPDATE platform_fee_rules SET max_fee_cents = 10000 WHERE scope='global'");
        $calc = new PlatformFeeCalculator($this->pdo);
        $r = $calc->calculate(1, 1000000);
        $this->assertSame(10000, $r['fee_cents']);
    }

    public function testFeeNeverExceedsGross(): void
    {
        // min_fee 100 000 ale transakcja 500 groszy — fee max = 500
        $this->pdo->exec("UPDATE platform_fee_rules SET min_fee_cents = 100000 WHERE scope='global'");
        $calc = new PlatformFeeCalculator($this->pdo);
        $r = $calc->calculate(1, 500);
        $this->assertSame(500, $r['fee_cents']);
        $this->assertSame(0, $r['club_net_cents']);
    }

    public function testZeroOrNegativeGross(): void
    {
        $calc = new PlatformFeeCalculator($this->pdo);
        $this->assertSame(0, $calc->calculate(1, 0)['fee_cents']);
        $this->assertSame(0, $calc->calculate(1, -100)['fee_cents']);
    }

    public function testFixedFeeAddedOnTopOfPercent(): void
    {
        // 2% + 30 groszy fixed na 10000 = 200+30 = 230
        $this->pdo->exec("UPDATE platform_fee_rules SET fee_fixed_cents = 30 WHERE scope='global'");
        $calc = new PlatformFeeCalculator($this->pdo);
        $this->assertSame(230, $calc->calculate(1, 10000)['fee_cents']);
    }

    public function testInactiveRuleIgnoredFallsBackToDefault(): void
    {
        // Wszystkie reguły inactive → fallback 2% z DEFAULT_PERCENT
        $this->pdo->exec("UPDATE platform_fee_rules SET active = 0");
        $calc = new PlatformFeeCalculator($this->pdo);
        $r = $calc->calculate(1, 10000);
        $this->assertSame(200, $r['fee_cents']);
        $this->assertNull($r['rule_id']);
    }

    public function testRuleOutOfEffectivePeriodIgnored(): void
    {
        // Reguła z effective_until w przeszłości — pomijana, fallback default.
        $this->pdo->exec("UPDATE platform_fee_rules SET effective_until = '2020-12-31'");
        $calc = new PlatformFeeCalculator($this->pdo);
        $r = $calc->calculate(1, 10000);
        $this->assertSame(200, $r['fee_cents']);     // fallback DEFAULT_PERCENT=2
        $this->assertNull($r['rule_id']);
    }
}
