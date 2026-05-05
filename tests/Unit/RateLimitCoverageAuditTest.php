<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Statyczny audit pokrycia rate-limiting na endpointach wrazliwych na
 * brute force:
 *   - /auth/login                   — admin/club login
 *   - /portal/login                 — member login
 *   - /auth/forgot-password         — email enumeration via timing
 *   - /portal/forgot-password       — same
 *   - /2fa/verify                   — 6-digit code, brute-force feasible
 *   - /portal/2fa/verify            — same
 *
 * Wymog: kazdy z powyzszych endpointow musi wywolywac RateLimiter::check
 * lub RateLimiter::hit w obslugujacej metodzie kontrolera.
 *
 * Pure regex, no DB, no HTTP.
 */
class RateLimitCoverageAuditTest extends TestCase
{
    /**
     * Wymagane endpointy z mapowaniem na [Controller, method].
     * Jesli endpoint nie istnieje w public/index.php — test go nie sprawdza
     * (zaden "ghost" check). Niewystarczajaca lista to false-negative
     * latwy do dopisania.
     */
    private array $expectedRoutes = [
        ['/auth/login',                'AuthController',           'login'],
        ['/portal/login',              'MemberPortalController',   'login'],
        ['/auth/forgot-password',      'PasswordResetController',  'sendReset'],
        ['/portal/forgot-password',    'PasswordResetController',  'sendResetMember'],
        ['/2fa/verify',                'TwoFactorController',      'verifyCode'],
        ['/portal/2fa/verify',         'MemberTwoFactorController','verifySubmit'],
    ];

    public function testEachAuthEndpointUsesRateLimiter(): void
    {
        $offenders = [];
        foreach ($this->expectedRoutes as [$route, $controller, $method]) {
            // Sprawdz ze route w ogole jest zarejestrowany
            $indexSrc = file_get_contents(__DIR__ . '/../../public/index.php');
            if ($indexSrc === false) { $this->fail('public/index.php missing'); }

            $routePattern = '/router->post\(\s*[\'"]' . preg_quote($route, '/') . '[\'"]/';
            if (!preg_match($routePattern, $indexSrc)) {
                // Endpoint nie istnieje — pomijamy
                continue;
            }

            // Czytaj metode kontrolera przez Reflection
            $body = $this->readMethodBody($controller, $method);
            if ($body === null) {
                $offenders[] = "{$controller}::{$method}() — metoda nie istnieje, ale route {$route} jest zarejestrowany";
                continue;
            }

            $hasRateLimit =
                str_contains($body, 'RateLimiter::check')
                || str_contains($body, 'RateLimiter::hit')
                || str_contains($body, 'RateLimiter::reset');

            if (!$hasRateLimit) {
                $offenders[] = "{$controller}::{$method}() handles {$route} bez RateLimiter";
            }
        }

        $this->assertEmpty(
            $offenders,
            "Auth endpointy bez rate limiting:\n  - " . implode("\n  - ", $offenders)
            . "\nDodaj RateLimiter::check(\$ip, 'action') na poczatku metody."
        );
    }

    public function testRateLimiterHitOnFailure(): void
    {
        // Strong invariant: po odrzuceniu credentiali, RateLimiter::hit
        // musi byc wywolane (inaczej bruteforce nie zostanie zarejestrowany).
        $offenders = [];
        $files = [
            'AuthController'         => 'login',
            'MemberPortalController' => 'login',
        ];
        foreach ($files as $controller => $method) {
            $body = $this->readMethodBody($controller, $method);
            if ($body === null) continue;

            // Heuristic: musi byc 'RateLimiter::hit' (counter increment)
            // gdzieś w metodzie
            if (!str_contains($body, 'RateLimiter::hit')) {
                $offenders[] = "{$controller}::{$method} — brak RateLimiter::hit po failed credential";
            }
        }
        $this->assertEmpty(
            $offenders,
            "Login endpoints muszą wywoływać RateLimiter::hit po niepoprawnych credentials:\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    private function readMethodBody(string $controller, string $method): ?string
    {
        $file = __DIR__ . '/../../app/Controllers/' . $controller . '.php';
        if (!is_file($file)) return null;
        require_once $file;
        $fqcn = '\\App\\Controllers\\' . $controller;
        if (!class_exists($fqcn) || !method_exists($fqcn, $method)) return null;
        try {
            $ref   = new \ReflectionMethod($fqcn, $method);
            $src   = file($file);
            if ($src === false) return null;
            $start = $ref->getStartLine() - 1;
            $end   = $ref->getEndLine();
            return implode('', array_slice($src, $start, $end - $start));
        } catch (\Throwable) {
            return null;
        }
    }
}
