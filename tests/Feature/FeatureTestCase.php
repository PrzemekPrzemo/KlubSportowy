<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\ClubContext;
use App\Helpers\Database;
use App\Helpers\Encryption;
use App\Helpers\Feature;
use App\Helpers\Session;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Wspólna baza dla feature/integration testów.
 *
 * UWAGA: nazwa pliku to `FeatureTestCase.php` (nie `AbstractFeatureTest.php`),
 * żeby PHPUnit nie próbował załadować abstract class jako test (sufiks
 * `Test.php` triggeruje discovery → runner warning + exit 1).
 * Konwencja zgodna z `tests/Integration/TestCase.php`.
 *
 * Każdy test wewnątrz transakcji DB — rollback w tearDown zostawia
 * bazę w stanie czystym (zero residue między testami).
 *
 * Jeśli DB niedostępna (lokalny dev bez MySQL, brak config/database.local.php)
 * — test jest markowany `skipped`, nie failuje. CI ma DB więc testy się
 * faktycznie wykonują.
 *
 * Każdy test rejestruje też testowy klucz szyfrowania jeśli go nie ma
 * (encrypted-field tests). Tworzony plik `config/encryption.local.php`
 * jest pozostawiony — nie usuwamy bo inne testy unitowe (np. EncryptionTest)
 * zarządzają nim samodzielnie.
 */
abstract class FeatureTestCase extends TestCase
{
    protected ?PDO $pdo = null;
    protected bool $inTransaction = false;

    /** Klucze sesji ustawione przez test — przywracane w tearDown. */
    private array $originalSession = [];

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

        // Sesja PHP — wystartuj cicho (ClubContext / Session używają $_SESSION).
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $this->originalSession = $_SESSION ?? [];
        // czysty start
        $_SESSION = [];

        $this->ensureEncryptionKey();

        // Connect to DB; skip gracefully if unavailable.
        try {
            $this->pdo = Database::pdo();
            $this->pdo->query('SELECT 1');
        } catch (\Throwable $e) {
            $this->markTestSkipped('Requires database: ' . $e->getMessage());
            return;
        }

        // Per-test transaction.
        $this->pdo->beginTransaction();
        $this->inTransaction = true;

        // Wyczyść request-scope cache feature flag (catalog jest cachowany).
        Feature::clearCache();
    }

    protected function tearDown(): void
    {
        if ($this->inTransaction && $this->pdo !== null && $this->pdo->inTransaction()) {
            try {
                $this->pdo->rollBack();
            } catch (\Throwable) {
                // ignore
            }
        }
        $this->inTransaction = false;

        // Przywróć sesję
        $_SESSION = $this->originalSession;
        ClubContext::clear();
        Feature::clearCache();

        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpery
    // ─────────────────────────────────────────────────────────────────────

    /** Ustawia aktywny klub w sesji (ClubContext + Session). */
    protected function asClub(int $clubId): void
    {
        Session::set('club_id', $clubId);
        ClubContext::set($clubId);
    }

    /** Tworzy klub testowy. */
    protected function createClub(string $name = 'Feature Test Club'): int
    {
        $shortName = substr(preg_replace('/[^a-zA-Z0-9]+/', '', $name) ?: 'FTC', 0, 10);
        $stmt = $this->pdo->prepare(
            "INSERT INTO clubs (name, short_name, is_active, created_at)
             VALUES (?, ?, 1, NOW())"
        );
        $stmt->execute([$name, $shortName]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Tworzy aktywnego usera + przypisuje do klubu.
     *
     * @return array{id:int, username:string, email:string, password:string}
     */
    protected function createUser(
        int $clubId,
        string $password = 'test1234',
        string $role = 'admin',
        ?string $username = null,
        ?string $email = null
    ): array {
        $username = $username ?? ('user_' . bin2hex(random_bytes(4)));
        $email    = $email    ?? ($username . '@test.local');
        $hash     = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare(
            "INSERT INTO users (username, email, password, full_name, is_active, created_at)
             VALUES (?, ?, ?, ?, 1, NOW())"
        );
        $stmt->execute([$username, $email, $hash, $username]);
        $userId = (int)$this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare(
            "INSERT INTO user_clubs (user_id, club_id, role, is_active) VALUES (?, ?, ?, 1)"
        );
        $stmt->execute([$userId, $clubId, $role]);

        return [
            'id' => $userId,
            'username' => $username,
            'email' => $email,
            'password' => $password,
        ];
    }

    /** Tworzy członka klubu (wstawienie bezpośrednie przez PDO, omija MemberModel encryption). */
    protected function createMember(
        int $clubId,
        string $firstName = 'Jan',
        string $lastName = 'Kowalski',
        array $extra = []
    ): int {
        $data = array_merge([
            'club_id'       => $clubId,
            'member_number' => 'M-' . bin2hex(random_bytes(4)),
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'join_date'     => date('Y-m-d'),
            'status'        => 'aktywny',
        ], $extra);

        $cols  = implode('`, `', array_keys($data));
        $holds = implode(', ', array_fill(0, count($data), '?'));
        $stmt  = $this->pdo->prepare("INSERT INTO members (`{$cols}`) VALUES ({$holds})");
        $stmt->execute(array_values($data));

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Tworzy plik klucza szyfrowania jeśli go nie ma — żeby Encryption::isConfigured()
     * zwracał true i encrypted-field tests faktycznie szyfrowały.
     */
    private function ensureEncryptionKey(): void
    {
        $keyFile = ROOT_PATH . '/config/encryption.local.php';
        if (!file_exists($keyFile)) {
            $testKey = base64_encode(str_repeat('F', 32));
            @file_put_contents(
                $keyFile,
                "<?php\nreturn ['key' => '{$testKey}', 'cipher' => 'aes-256-gcm'];\n"
            );
        }
        Encryption::reset();
    }
}
