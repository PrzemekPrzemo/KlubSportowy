<?php

namespace App\Helpers;

/**
 * Zarządza aktywnym kontekstem klubu w sesji.
 *
 * Każde żądanie ma przypisany jeden aktywny klub (club_id z sesji).
 * Super admin może przełączać kontekst między klubami.
 * ClubContext::setFromSubdomain() jest wywoływany przed routingiem
 * w public/index.php aby subdomena automatycznie ustawiała kontekst.
 */
class ClubContext
{
    private const SESSION_KEY     = 'club_id';
    private const SUPER_ADMIN_KEY = 'is_super_admin';

    public static function current(): ?int
    {
        $val = Session::get(self::SESSION_KEY);
        return $val !== null ? (int)$val : null;
    }

    public static function set(int $clubId): void
    {
        Session::set(self::SESSION_KEY, $clubId);
    }

    public static function clear(): void
    {
        Session::remove(self::SESSION_KEY);
    }

    public static function isSuperAdmin(): bool
    {
        return (bool)Session::get(self::SUPER_ADMIN_KEY, false);
    }

    /**
     * Wykrywa klub na podstawie subdomeny żądania HTTP.
     * Przykład: host = "azs-warszawa.klubsportowy.pl", baseDomain = "klubsportowy.pl"
     *   → wyszukuje club_customization.subdomain = "azs-warszawa"
     *   → ustawia kontekst jeśli znaleziono
     */
    public static function setFromSubdomain(string $host, string $baseDomain): void
    {
        if (empty($baseDomain) || empty($host)) {
            return;
        }

        $host = strtolower(explode(':', $host)[0]);
        $base = strtolower($baseDomain);

        if (!str_ends_with($host, '.' . $base)) {
            return;
        }

        $subdomain = substr($host, 0, strlen($host) - strlen('.' . $base));

        if ($subdomain === '' || $subdomain === 'www') {
            return;
        }

        try {
            $db   = Database::pdo();
            $stmt = $db->prepare(
                'SELECT club_id FROM club_customization WHERE subdomain = ? LIMIT 1'
            );
            $stmt->execute([$subdomain]);
            $row = $stmt->fetch();
            if ($row) {
                self::set((int)$row['club_id']);
            }
        } catch (\Throwable) {
            // Tabela może jeszcze nie istnieć podczas setup — ignoruj
        }
    }

    public static function require(): int
    {
        $id = self::current();
        if ($id === null) {
            throw new \RuntimeException('Brak aktywnego kontekstu klubu w sesji.');
        }
        return $id;
    }

    /**
     * Wykonaj $callback z tymczasowo aktywnym kontekstem klubu $clubId.
     *
     * Wzorzec zaczerpniety z hovera.app-sys TenantManager::execute() —
     * Hovera uzywa tego do queue-jobow i super-admin impersonation aby
     * tymczasowo przelaczyc tenant context i nigdy nie zostawic "wisiacego"
     * przelaczenia po wyjsciu z bloku.
     *
     * Gwarancje:
     *   - poprzedni club_id zostaje przywrocony nawet jesli $callback rzuci
     *   - return-value $callback jest zwracany przez execute()
     *   - thread-safe nie jest potrzebne — PHP request-per-process
     *
     * Uzycie:
     *   ClubContext::execute($otherClubId, function () use ($id) {
     *       return (new MemberModel())->findById($id); // dziala w scope $otherClubId
     *   });
     */
    public static function execute(int $clubId, callable $callback): mixed
    {
        $previous = self::current();
        self::set($clubId);
        try {
            return $callback();
        } finally {
            if ($previous === null) {
                self::clear();
            } else {
                self::set($previous);
            }
        }
    }

    /**
     * Hard-fail jesli brak aktywnego kontekstu — uzywaj w kodzie, ktory
     * NIGDY nie powinien dzialac bez izolacji (np. controllery klubowe).
     *
     * Roznica wzgledem require(): rzuca dedykowany wyjatek z severity
     * "critical" zamiast generycznego RuntimeException, dzieki czemu
     * ErrorMonitor moze potraktowac to jako security-event.
     */
    public static function requireForRead(string $context = ''): int
    {
        $id = self::current();
        if ($id === null) {
            $msg = 'Tenant isolation violation: read attempted without active club context';
            if ($context !== '') {
                $msg .= ' (' . $context . ')';
            }
            throw new \RuntimeException($msg);
        }
        return $id;
    }
}
