<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Per-klub feature flags API.
 *
 * Sprawdza czy dana feature flag (boolean włącz/wyłącz) jest dostępna
 * dla AKTYWNEGO klubu (z ClubContext) lub konkretnego clubId.
 *
 * UWAGA: To NIE jest system addons (boostery limitów ilościowych).
 *   - addons       → +10 zawodników, +5 sekcji (model: ClubAddonModel)
 *   - feature flag → pdf_export, sms_notifications, whitelabel_branding (TEN helper)
 *
 * Logika rozstrzygania `enabled($code, $clubId)`:
 *   1. Override w `club_feature_overrides` (jeśli istnieje, nie wygasł,
 *      i flaga jest w aktywnym katalogu) → wartość z override.
 *   2. `default_in_plan` z `feature_flags_catalog` mapowane przez
 *      kod planu klubu (`club_subscriptions.plan_id` → `subscription_plans.code`).
 *   3. Flaga nie istnieje w katalogu lub plan nieznany → false (fail-closed).
 *
 * Wyniki cachowane w pamięci request-scope (static array) aby uniknąć
 * powtarzanych zapytań SQL przy wielokrotnym sprawdzaniu tej samej flagi.
 *
 * Przykłady:
 *   Feature::enabled('pdf_export')               // aktywny klub z ClubContext
 *   Feature::enabled('sms_notifications', 42)    // konkretny klub
 *   Feature::requireEnabled('pdf_export')        // throw 403 / redirect
 *   Feature::list(42)                            // [{code, name, enabled, source, ...}, ...]
 */
final class Feature
{
    /** Cache request-scope: keyed by "{$clubId}:{$code}" => bool. */
    private static array $cache = [];

    /** Cache request-scope dla plan_code per klub: keyed by clubId => ?string. */
    private static array $planCache = [];

    /** Cache request-scope dla katalogu flag: code => row|null. */
    private static ?array $catalogCache = null;

    /**
     * Zwraca true jeśli feature flag jest włączona dla danego klubu.
     *
     * @param string $code Kod flagi (np. 'pdf_export').
     * @param int|null $clubId Konkretny club_id; null = aktywny z ClubContext.
     */
    public static function enabled(string $code, ?int $clubId = null): bool
    {
        $clubId = $clubId ?? ClubContext::current();
        if ($clubId === null) {
            return false; // brak kontekstu klubu — fail closed
        }

        $cacheKey = "{$clubId}:{$code}";
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }

        $result = self::resolve($code, $clubId);
        self::$cache[$cacheKey] = $result;
        return $result;
    }

    /**
     * Wymusza włączenie flagi — w przeciwnym razie zwraca HTTP 403.
     * Używaj w controllerach jako guard:
     *   Feature::requireEnabled('pdf_export');
     */
    public static function requireEnabled(string $code, ?int $clubId = null): void
    {
        if (self::enabled($code, $clubId)) {
            return;
        }

        $catalog = self::catalog();
        $name = $catalog[$code]['name'] ?? $code;

        http_response_code(403);
        $safeName = htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8');
        echo '<!doctype html><meta charset="utf-8"><title>403 — funkcja niedostępna</title>';
        echo '<div style="font-family:sans-serif;max-width:560px;margin:80px auto;padding:24px;border:1px solid #ddd;border-radius:8px">';
        echo '<h1 style="margin-top:0">Funkcja niedostępna w Twoim planie</h1>';
        echo '<p>Ta funkcja (<strong>' . $safeName . '</strong>) nie jest dostępna w aktualnym pakiecie cenowym klubu.</p>';
        echo '<p>Skontaktuj się z administratorem platformy lub uaktualnij plan, aby uzyskać dostęp.</p>';
        echo '</div>';
        exit;
    }

    /**
     * Zwraca pełną listę flag z katalogu z informacją o stanie dla danego klubu.
     * Używane do UI (sekcja "Aktywne funkcje" w panelu klubu).
     *
     * @return array<int, array{
     *   code:string, name:string, description:?string, category:string,
     *   enabled:bool, source:string, override:?array
     * }>
     *   source ∈ {'plan','override','catalog_missing'}
     */
    public static function list(?int $clubId = null): array
    {
        $clubId = $clubId ?? ClubContext::current();
        if ($clubId === null) {
            return [];
        }

        $catalog   = self::catalog();
        $overrides = self::loadOverrides($clubId);
        $planCode  = self::planCodeFor($clubId);

        $rows = [];
        foreach ($catalog as $code => $row) {
            if ((int)($row['is_active'] ?? 0) !== 1) continue;

            $override = $overrides[$code] ?? null;
            if ($override !== null) {
                $enabled = (bool)$override['enabled'];
                $source  = 'override';
            } else {
                $enabled = self::defaultForPlan($row, $planCode);
                $source  = 'plan';
            }

            $rows[] = [
                'code'        => $code,
                'name'        => (string)$row['name'],
                'description' => $row['description'] ?? null,
                'category'    => (string)($row['category'] ?? 'general'),
                'enabled'     => $enabled,
                'source'      => $source,
                'override'    => $override,
                'sort_order'  => (int)($row['sort_order'] ?? 0),
            ];
        }

        usort($rows, fn($a, $b) => ($a['sort_order'] <=> $b['sort_order']) ?: strcmp($a['code'], $b['code']));
        return $rows;
    }

    /**
     * Czyści cały cache request-scope (wywoływać po zmianie override / katalogu).
     */
    public static function clearCache(): void
    {
        self::$cache        = [];
        self::$planCache    = [];
        self::$catalogCache = null;
    }

    // ── Internals ───────────────────────────────────────────────────────────

    private static function resolve(string $code, int $clubId): bool
    {
        $catalog = self::catalog();
        $row     = $catalog[$code] ?? null;
        if ($row === null || (int)($row['is_active'] ?? 0) !== 1) {
            return false; // fail closed dla nieznanej / nieaktywnej flagi
        }

        $overrides = self::loadOverrides($clubId);
        if (isset($overrides[$code])) {
            return (bool)$overrides[$code]['enabled'];
        }

        $planCode = self::planCodeFor($clubId);
        return self::defaultForPlan($row, $planCode);
    }

    private static function defaultForPlan(array $row, ?string $planCode): bool
    {
        if ($planCode === null) return false;
        $map = $row['default_in_plan'] ?? null;
        if (is_string($map)) {
            $decoded = json_decode($map, true);
            $map = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($map)) return false;
        return !empty($map[$planCode]);
    }

    /** Pełny katalog flag indeksowany po `code`. */
    private static function catalog(): array
    {
        if (self::$catalogCache !== null) {
            return self::$catalogCache;
        }
        try {
            $stmt = Database::pdo()->query(
                "SELECT code, name, description, category, default_in_plan,
                        is_active, sort_order
                   FROM feature_flags_catalog"
            );
            $rows = $stmt->fetchAll();
        } catch (\Throwable) {
            // Tabela może jeszcze nie istnieć (migracja nieuruchomiona).
            self::$catalogCache = [];
            return self::$catalogCache;
        }

        $out = [];
        foreach ($rows as $r) {
            $out[$r['code']] = $r;
        }
        self::$catalogCache = $out;
        return $out;
    }

    /**
     * Override-y dla klubu indeksowane po feature_code. Ignoruje wygasłe.
     * @return array<string, array{enabled:bool, expires_at:?string, reason:?string}>
     */
    private static function loadOverrides(int $clubId): array
    {
        static $perClub = [];
        if (isset($perClub[$clubId])) return $perClub[$clubId];

        try {
            $stmt = Database::pdo()->prepare(
                "SELECT feature_code, enabled, expires_at, reason
                   FROM club_feature_overrides
                  WHERE club_id = ?
                    AND (expires_at IS NULL OR expires_at > NOW())"
            );
            $stmt->execute([$clubId]);
            $rows = $stmt->fetchAll();
        } catch (\Throwable) {
            $perClub[$clubId] = [];
            return $perClub[$clubId];
        }

        $out = [];
        foreach ($rows as $r) {
            $out[$r['feature_code']] = [
                'enabled'    => (bool)$r['enabled'],
                'expires_at' => $r['expires_at'] ?? null,
                'reason'     => $r['reason'] ?? null,
            ];
        }
        $perClub[$clubId] = $out;
        return $out;
    }

    /** Zwraca kod planu klubu (np. 'club', 'enterprise') lub null. */
    private static function planCodeFor(int $clubId): ?string
    {
        if (array_key_exists($clubId, self::$planCache)) {
            return self::$planCache[$clubId];
        }
        try {
            $stmt = Database::pdo()->prepare(
                "SELECT sp.code
                   FROM club_subscriptions cs
                   JOIN subscription_plans sp ON sp.id = cs.plan_id
                  WHERE cs.club_id = ?
                  LIMIT 1"
            );
            $stmt->execute([$clubId]);
            $code = $stmt->fetchColumn();
        } catch (\Throwable) {
            $code = false;
        }

        self::$planCache[$clubId] = $code !== false && $code !== null ? (string)$code : null;
        return self::$planCache[$clubId];
    }
}
