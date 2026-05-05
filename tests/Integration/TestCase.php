<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base class for integration tests.
 *
 * Provides helpers for creating test clubs, users, and members.
 * Tests requiring DB should be annotated with @group integration
 * and call $this->requireDatabase() which skips if DB is unavailable.
 */
abstract class TestCase extends BaseTestCase
{
    protected ?\PDO $db = null;

    /** IDs created during test — cleaned up in tearDown. */
    protected array $createdClubIds = [];
    protected array $createdUserIds = [];
    protected array $createdMemberIds = [];
    protected array $createdFeeRateIds = [];
    protected array $createdApiKeyIds = [];
    protected array $createdSettingKeys = []; // [[clubId, key], ...]

    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', dirname(__DIR__, 2));
        }
        if (!defined('BASE_URL')) {
            define('BASE_URL', 'http://localhost:8080');
        }
        if (!defined('TESTING')) {
            define('TESTING', true);
        }
    }

    /**
     * Attempt to connect to the database.
     * Marks the test as skipped if DB is not available.
     */
    protected function requireDatabase(): \PDO
    {
        if ($this->db !== null) {
            return $this->db;
        }

        try {
            $this->db = \App\Helpers\Database::pdo();
            // Quick connectivity check
            $this->db->query('SELECT 1');
        } catch (\Throwable $e) {
            $this->markTestSkipped('Requires database connection: ' . $e->getMessage());
        }

        return $this->db;
    }

    /**
     * Insert a test club and track its ID for cleanup.
     */
    protected function createTestClub(string $name): int
    {
        $db = $this->requireDatabase();
        $stmt = $db->prepare(
            "INSERT INTO clubs (name, short_name, is_active, created_at)
             VALUES (?, ?, 1, NOW())"
        );
        $stmt->execute([$name, substr($name, 0, 10)]);
        $id = (int) $db->lastInsertId();
        $this->createdClubIds[] = $id;
        return $id;
    }

    /**
     * Insert a test user and track its ID for cleanup.
     *
     * @return array{id: int, username: string, email: string}
     */
    protected function createTestUser(
        string $username,
        string $email,
        int $clubId,
        string $role = 'admin'
    ): array {
        $db = $this->requireDatabase();

        $stmt = $db->prepare(
            "INSERT INTO users (username, email, password, full_name, is_active, created_at)
             VALUES (?, ?, ?, ?, 1, NOW())"
        );
        $hash = password_hash('test1234', PASSWORD_BCRYPT);
        $stmt->execute([$username, $email, $hash, $username]);
        $userId = (int) $db->lastInsertId();
        $this->createdUserIds[] = $userId;

        // Link user to club
        $stmt = $db->prepare(
            "INSERT INTO user_clubs (user_id, club_id, role, is_active) VALUES (?, ?, ?, 1)"
        );
        $stmt->execute([$userId, $clubId, $role]);

        return ['id' => $userId, 'username' => $username, 'email' => $email];
    }

    /**
     * Insert a test member and track its ID for cleanup.
     *
     * @return array{id: int} + merged $data
     */
    protected function createTestMember(int $clubId, array $data = []): array
    {
        $db = $this->requireDatabase();

        $defaults = [
            'first_name'    => 'Test',
            'last_name'     => 'Member',
            'status'        => 'aktywny',
            'member_number' => 'T-' . bin2hex(random_bytes(4)),
            'join_date'     => date('Y-m-d'),
        ];
        $row = array_merge($defaults, $data, ['club_id' => $clubId]);

        $cols   = implode('`, `', array_keys($row));
        $holds  = implode(', ', array_fill(0, count($row), '?'));
        $stmt   = $db->prepare("INSERT INTO members (`{$cols}`) VALUES ({$holds})");
        $stmt->execute(array_values($row));

        $id = (int) $db->lastInsertId();
        $this->createdMemberIds[] = $id;

        return array_merge($row, ['id' => $id]);
    }

    protected function tearDown(): void
    {
        if ($this->db !== null) {
            // Clean up in reverse dependency order
            foreach ($this->createdSettingKeys as [$clubId, $key]) {
                $this->db->prepare("DELETE FROM club_settings WHERE club_id = ? AND `key` = ?")
                    ->execute([$clubId, $key]);
            }
            foreach ($this->createdApiKeyIds as $id) {
                $this->db->prepare("DELETE FROM api_keys WHERE id = ?")->execute([$id]);
            }
            foreach ($this->createdFeeRateIds as $id) {
                $this->db->prepare("DELETE FROM fee_rates WHERE id = ?")->execute([$id]);
            }
            foreach ($this->createdMemberIds as $id) {
                $this->db->prepare("DELETE FROM members WHERE id = ?")->execute([$id]);
            }
            foreach ($this->createdUserIds as $id) {
                $this->db->prepare("DELETE FROM user_clubs WHERE user_id = ?")->execute([$id]);
                $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            }
            foreach ($this->createdClubIds as $id) {
                // Sportowi seederzy moga utworzyc additional members w testowym
                // klubie ktorych createTestMember nie zna — usuwamy wszystkich
                // pozostalych members tego klubu zanim DELETE FROM clubs (FK
                // members.club_id ma ON DELETE RESTRICT).
                $this->db->prepare("DELETE FROM members WHERE club_id = ?")->execute([$id]);
                $this->db->prepare("DELETE FROM clubs WHERE id = ?")->execute([$id]);
            }
        }

        parent::tearDown();
    }
}
