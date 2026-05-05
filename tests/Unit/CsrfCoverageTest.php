<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Statyczny audit ze KAZDY POST endpoint w public/index.php wywoluje
 * Csrf::verify() w obslugujacej metodzie kontrolera. Ochrona przed:
 *   - regression przy refactoringu metody
 *   - kopiowanie nowego endpointu bez Csrf::verify
 *   - pomylki w handlerze (nazwa metody)
 *
 * Zwolnienia (rozsadne):
 *   - WebhookController — zewnetrzne integracje, podpisane HMAC-em
 *   - ApiController     — auth przez API key (nie CSRF)
 *   - StreamingController#trackEvent — public ping z embed
 *
 * Pure regex + file scan. Bez DB, bez HTTP.
 */
class CsrfCoverageTest extends TestCase
{
    /** Zwolnienia per [Controller, method] — uzasadnione w komentarzu. */
    private array $exemptions = [
        // Webhooks: external systems POST signed payloads
        ['WebhookController', 'receive'],
        ['PaymentWebhookController', 'handle'],   // Stripe HMAC signature
        // API endpoints: API key in header, not CSRF
        ['ApiController', 'index'],
        ['ApiController', 'members'],
        ['ApiController', 'memberCreate'],
        ['ApiController', 'memberUpdate'],
        ['ApiController', 'memberDelete'],
        // Streaming: tracking ping z public embed-a
        ['StreamingController', 'trackEvent'],
        // Payment gateway callbacks: signed by gateway
        ['PaymentController', 'callback'],
        ['PaymentController', 'webhook'],
        // Push tokens: device-tied, dedicated auth header
        ['PushTokenController', 'register'],
        ['PushTokenController', 'unregister'],
        // Self-registration zablokowane — endpoint zwraca tylko 410+redirect.
        ['AuthController', 'register'],
    ];

    public function testAllPostEndpointsVerifyCsrf(): void
    {
        $routes = $this->extractPostRoutes();
        $this->assertNotEmpty($routes, 'Nie znaleziono zadnych router->post w public/index.php');

        $missing = [];
        foreach ($routes as $r) {
            [$path, $controller, $method] = $r;

            if ($this->isExempt($controller, $method)) continue;

            $body = $this->readMethodBody($controller, $method);
            if ($body === null) {
                // Method missing — separate concern, ignore here
                continue;
            }

            if (!str_contains($body, 'Csrf::verify')) {
                $missing[] = "{$controller}::{$method}() handles POST {$path}";
            }
        }

        $this->assertEmpty(
            $missing,
            "POST endpointy bez Csrf::verify():\n  - " . implode("\n  - ", $missing)
            . "\nDodaj Csrf::verify() na poczatku metody albo whitelist exemptions "
            . "(z uzasadnieniem)."
        );
    }

    /**
     * Parses public/index.php and returns all POST routes:
     * @return array<int, array{0:string,1:string,2:string}>  [path, ControllerName, methodName]
     */
    private function extractPostRoutes(): array
    {
        $src = file_get_contents(__DIR__ . '/../../public/index.php');
        $this->assertNotFalse($src);

        $routes = [];
        // Match: $router->post('/path', [\App\Controllers\Foo::class, 'bar'])
        $pattern = '/\$router->post\(\s*'
                 . '[\'"]([^\'"]+)[\'"]\s*,\s*'                       // path
                 . '\[\s*\\\\App\\\\Controllers\\\\([A-Za-z]+)::class' // Controller class
                 . '\s*,\s*[\'"]([A-Za-z]+)[\'"]\s*\]/';               // method
        if (preg_match_all($pattern, $src, $m, PREG_SET_ORDER)) {
            foreach ($m as $row) {
                $routes[] = [$row[1], $row[2], $row[3]];
            }
        }
        return $routes;
    }

    private function isExempt(string $controller, string $method): bool
    {
        foreach ($this->exemptions as [$c, $m]) {
            if ($c === $controller && $m === $method) return true;
        }
        return false;
    }

    /**
     * Read full method body (start..end) from a controller file.
     * Returns null when the method or file is missing.
     */
    private function readMethodBody(string $controller, string $method): ?string
    {
        $file = __DIR__ . '/../../app/Controllers/' . $controller . '.php';
        if (!is_file($file)) return null;

        // Trigger autoload via include + Reflection
        require_once $file;
        $fqcn = '\\App\\Controllers\\' . $controller;
        if (!class_exists($fqcn)) return null;
        try {
            $ref  = new \ReflectionMethod($fqcn, $method);
            $src  = file($file);
            if ($src === false) return null;
            $start = $ref->getStartLine() - 1;
            $end   = $ref->getEndLine();
            return implode('', array_slice($src, $start, $end - $start));
        } catch (\Throwable) {
            return null;
        }
    }
}
