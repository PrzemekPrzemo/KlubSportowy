<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;

/**
 * Konta opiekunow (rodzicow) — RODO art. 8 portal.
 *
 * Multi-tenant: kazda operacja jest filtrowana po club_id z ClubContext.
 * Hasla: bcrypt cost=12.
 * Token aktywacyjny: random_bytes(32) hex (64 znaki) + expires_at = now+7d.
 */
class GuardianModel extends ClubScopedModel
{
    protected string $table = 'guardians';

    public const BCRYPT_COST = 12;
    public const ACTIVATION_TTL_DAYS = 7;

    /**
     * Szuka opiekuna po e-mailu W AKTYWNYM KLUBIE (scope).
     */
    public function findByEmail(string $email): ?array
    {
        $clubId = $this->clubId();
        $email  = strtolower(trim($email));

        if ($clubId !== null) {
            $stmt = $this->db->prepare(
                "SELECT * FROM guardians WHERE club_id = ? AND email = ? LIMIT 1"
            );
            $stmt->execute([$clubId, $email]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT * FROM guardians WHERE email = ? LIMIT 1"
            );
            $stmt->execute([$email]);
        }
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Szuka opiekuna po e-mailu cross-klubowo (do logowania zanim wybierzemy klub).
     * Zwraca PIERWSZE konto opiekuna po tym emailu — wystepuje wiele jesli rodzic
     * ma dzieci w kilku klubach (unique key to (club_id,email)).
     */
    public function findByEmailGlobal(string $email): ?array
    {
        $email = strtolower(trim($email));
        $stmt  = $this->db->prepare(
            "SELECT * FROM guardians WHERE email = ? ORDER BY id ASC LIMIT 1"
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Wszystkie konta opiekuna o danym e-mailu we wszystkich klubach.
     * Uzywane gdy 1 rodzic ma dzieci w kilku klubach.
     */
    public function allByEmail(string $email): array
    {
        $email = strtolower(trim($email));
        $stmt  = $this->db->prepare(
            "SELECT * FROM guardians WHERE email = ? ORDER BY id ASC"
        );
        $stmt->execute([$email]);
        return $stmt->fetchAll();
    }

    public function findByActivationToken(string $token): ?array
    {
        if (strlen($token) !== 64 || !ctype_xdigit($token)) {
            return null;
        }
        $stmt = $this->db->prepare(
            "SELECT * FROM guardians
             WHERE activation_token = ?
               AND activation_token_expires_at >= NOW()
             LIMIT 1"
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Stworz zaproszenie (lub re-issue) dla opiekuna.
     * Zwraca tablice ['guardian' => ..., 'token' => string, 'created' => bool].
     */
    public function invite(
        int $clubId,
        string $email,
        ?string $firstName = null,
        ?string $lastName  = null,
        ?string $phone     = null
    ): array {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Nieprawidlowy e-mail opiekuna.');
        }

        // Sprawdz czy istnieje w tym klubie
        $stmt = $this->db->prepare(
            "SELECT * FROM guardians WHERE club_id = ? AND email = ? LIMIT 1"
        );
        $stmt->execute([$clubId, $email]);
        $existing = $stmt->fetch();

        $token   = bin2hex(random_bytes(32));
        $expires = (new \DateTimeImmutable("+" . self::ACTIVATION_TTL_DAYS . " days"))
            ->format('Y-m-d H:i:s');

        if ($existing) {
            $stmt = $this->db->prepare(
                "UPDATE guardians
                 SET activation_token = ?, activation_token_expires_at = ?,
                     first_name = COALESCE(NULLIF(?, ''), first_name),
                     last_name  = COALESCE(NULLIF(?, ''), last_name),
                     phone      = COALESCE(NULLIF(?, ''), phone),
                     active     = 1
                 WHERE id = ?"
            );
            $stmt->execute([
                $token, $expires,
                $firstName ?? '', $lastName ?? '', self::sanitizePhone($phone),
                (int)$existing['id'],
            ]);

            $stmt = $this->db->prepare("SELECT * FROM guardians WHERE id = ?");
            $stmt->execute([(int)$existing['id']]);
            return [
                'guardian' => $stmt->fetch() ?: $existing,
                'token'    => $token,
                'created'  => false,
            ];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO guardians
                (club_id, email, phone, first_name, last_name,
                 activation_token, activation_token_expires_at, active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
        );
        $stmt->execute([
            $clubId,
            $email,
            self::sanitizePhone($phone),
            $firstName,
            $lastName,
            $token,
            $expires,
        ]);
        $id = (int)$this->db->lastInsertId();

        $stmt = $this->db->prepare("SELECT * FROM guardians WHERE id = ?");
        $stmt->execute([$id]);
        return [
            'guardian' => $stmt->fetch(),
            'token'    => $token,
            'created'  => true,
        ];
    }

    /**
     * Aktywuje konto: ustawia haslo, czysci token, zaznacza email_verified_at.
     */
    public function activate(int $guardianId, string $password): bool
    {
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Haslo musi miec >= 8 znakow.');
        }
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);

        $stmt = $this->db->prepare(
            "UPDATE guardians
             SET portal_password = ?,
                 email_verified_at = NOW(),
                 activation_token = NULL,
                 activation_token_expires_at = NULL,
                 consent_terms_accepted_at = NOW(),
                 updated_at = NOW()
             WHERE id = ?"
        );
        return $stmt->execute([$hash, $guardianId]);
    }

    /**
     * Weryfikuje haslo. Zwraca true gdy zgodne.
     */
    public function verifyPassword(array $guardian, string $password): bool
    {
        $hash = $guardian['portal_password'] ?? null;
        if (!is_string($hash) || $hash === '') {
            return false;
        }
        return password_verify($password, $hash);
    }

    public function touchLogin(int $guardianId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE guardians SET last_login_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$guardianId]);
    }

    public function setPassword(int $guardianId, string $password): bool
    {
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Haslo musi miec >= 8 znakow.');
        }
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);
        $stmt = $this->db->prepare(
            "UPDATE guardians SET portal_password = ? WHERE id = ?"
        );
        return $stmt->execute([$hash, $guardianId]);
    }

    public function setPreferredLocale(int $guardianId, ?string $locale): bool
    {
        if ($locale !== null && !in_array($locale, ['pl', 'en'], true)) {
            $locale = null;
        }
        $stmt = $this->db->prepare(
            "UPDATE guardians SET preferred_locale = ? WHERE id = ?"
        );
        return $stmt->execute([$locale, $guardianId]);
    }

    public function listForClub(int $clubId, int $page = 1, int $perPage = 50): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $perPage = max(1, $perPage);
        $stmt = $this->db->prepare(
            "SELECT g.*,
                    (SELECT COUNT(*) FROM guardian_members gm WHERE gm.guardian_id = g.id) AS children_count
             FROM guardians g
             WHERE g.club_id = ?
             ORDER BY g.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public static function sanitizePhone(?string $phone): ?string
    {
        if ($phone === null) return null;
        $cleaned = preg_replace('/[^0-9 +\-]/', '', $phone) ?? '';
        $cleaned = trim($cleaned);
        if ($cleaned === '') return null;
        return substr($cleaned, 0, 20);
    }
}
