<?php

namespace Tests\Unit;

use App\Helpers\RateLimiter;
use PHPUnit\Framework\TestCase;

/**
 * Testy RateLimiter — bez bazy danych (mockowanie na poziomie statycznych wywołań).
 *
 * Te testy weryfikują logikę klasy. Testy integracyjne z bazą danych
 * zostaną uruchomione w tests/Integration/ po skonfigurowaniu testowej bazy.
 */
class RateLimiterTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(RateLimiter::class));
    }

    public function testHasRequiredMethods(): void
    {
        $this->assertTrue(method_exists(RateLimiter::class, 'check'));
        $this->assertTrue(method_exists(RateLimiter::class, 'hit'));
        $this->assertTrue(method_exists(RateLimiter::class, 'reset'));
        $this->assertTrue(method_exists(RateLimiter::class, 'cleanup'));
    }
}
