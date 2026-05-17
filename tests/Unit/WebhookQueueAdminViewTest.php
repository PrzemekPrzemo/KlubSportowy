<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Static audit dla AdminPlatformWebhooksController (cross-klub kolejka webhook
 * deliveries dla super admina, model po AdminPlatformKsefController).
 *
 * Sprawdza:
 *   - constructor wywoluje requireSuperAdmin (zero zarzadu/admina klubu)
 *   - public actions: queue / retry / failPermanently istnieja
 *   - retry / failPermanently maja Csrf::verify
 *   - listing query joinuje webhook_subscriptions (zeby pobrac club_id + URL)
 *   - view istnieje i renderuje wymagane karty dashboardowe
 */
class WebhookQueueAdminViewTest extends TestCase
{
    private string $controllerFile;
    private string $viewFile;

    protected function setUp(): void
    {
        $this->controllerFile = __DIR__ . '/../../app/Controllers/AdminPlatformWebhooksController.php';
        $this->viewFile       = __DIR__ . '/../../app/Views/admin/platform/webhooks/queue.php';
    }

    public function testControllerFileExists(): void
    {
        $this->assertFileExists($this->controllerFile);
    }

    public function testViewFileExists(): void
    {
        $this->assertFileExists($this->viewFile);
    }

    public function testConstructorRequiresSuperAdmin(): void
    {
        require_once $this->controllerFile;
        $rc  = new ReflectionClass(\App\Controllers\AdminPlatformWebhooksController::class);
        $src = file_get_contents($rc->getFileName() ?: $this->controllerFile);
        $this->assertNotFalse($src);
        $this->assertMatchesRegularExpression(
            '/public\s+function\s+__construct\s*\([^)]*\)\s*\{[^}]*requireSuperAdmin\s*\(\s*\)/s',
            $src,
            'Konstruktor MUSI wywolac requireSuperAdmin() — zaden zarzad klubu nie moze widziec cross-tenant queue.'
        );
    }

    public function testRequiredActionsExist(): void
    {
        require_once $this->controllerFile;
        $rc = new ReflectionClass(\App\Controllers\AdminPlatformWebhooksController::class);
        foreach (['queue', 'retry', 'failPermanently'] as $method) {
            $this->assertTrue(
                $rc->hasMethod($method),
                "AdminPlatformWebhooksController musi miec metode {$method}()."
            );
        }
    }

    public function testPostHandlersVerifyCsrf(): void
    {
        $src = file_get_contents($this->controllerFile);
        $this->assertNotFalse($src);
        // retry i failPermanently to handlery POST → MUSZA miec Csrf::verify
        foreach (['retry', 'failPermanently'] as $method) {
            $pattern = '/public\s+function\s+' . $method . '\s*\([^)]*\)\s*:\s*void\s*\{\s*Csrf::verify\s*\(\s*\)/s';
            $this->assertMatchesRegularExpression(
                $pattern,
                $src,
                "{$method}() musi wywolac Csrf::verify() na pierwszej linii."
            );
        }
    }

    public function testListingJoinsSubscriptions(): void
    {
        $src = file_get_contents($this->controllerFile);
        $this->assertNotFalse($src);
        $this->assertStringContainsString('JOIN webhook_subscriptions', $src,
            'Listing musi joinowac webhook_subscriptions zeby pobrac target_url i club_id (cross-tenant context).');
    }

    public function testStatsCoversRequiredMetrics(): void
    {
        $src = file_get_contents($this->controllerFile);
        $this->assertNotFalse($src);
        // Dashboard cards: total pending, failed 24h, delivered 24h, avg delivery time
        foreach (['pending', 'failed_24h', 'delivered_24h', 'avg_delivery_seconds'] as $metric) {
            $this->assertStringContainsString($metric, $src,
                "Statystyki musza zawierac metryke {$metric}.");
        }
    }

    public function testViewRendersDashboardCards(): void
    {
        $viewSrc = file_get_contents($this->viewFile);
        $this->assertNotFalse($viewSrc);
        foreach (['Pending', 'Failed', 'Delivered', 'Sredni czas'] as $label) {
            $this->assertStringContainsStringIgnoringCase($label, $viewSrc,
                "View musi pokazac karte z etykieta {$label}.");
        }
    }

    public function testViewRendersActionButtons(): void
    {
        $viewSrc = file_get_contents($this->viewFile);
        $this->assertNotFalse($viewSrc);
        $this->assertStringContainsString('/retry',            $viewSrc);
        $this->assertStringContainsString('/fail-permanently', $viewSrc);
        $this->assertStringContainsString('csrf_field()',      $viewSrc,
            'View MUSI emitowac CSRF token w forms (csrf_field()).');
    }

    public function testRoutesRegistered(): void
    {
        $routesSrc = file_get_contents(__DIR__ . '/../../public/index.php');
        $this->assertNotFalse($routesSrc);
        $this->assertStringContainsString("/admin/platform/webhooks/queue", $routesSrc);
        $this->assertStringContainsString("AdminPlatformWebhooksController::class, 'queue'", $routesSrc);
        $this->assertStringContainsString("AdminPlatformWebhooksController::class, 'retry'", $routesSrc);
        $this->assertStringContainsString("AdminPlatformWebhooksController::class, 'failPermanently'", $routesSrc);
    }
}
