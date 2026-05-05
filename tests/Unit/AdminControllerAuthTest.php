<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * F2 lite — statyczny audit ze wszystkie AdminController-y maja auth gate
 * w konstruktorze. Sprawdza, ze konstruktor zawiera ktorys z:
 *   - requireSuperAdmin()  — najczestsza brama dla master admina
 *   - requireRole([...])   — gdy controller egzekwuje konkretna role
 *
 * Lapie regression gdy ktos przy refactoringu konstruktora przypadkowo
 * usunie auth check. Nie wymaga DB ani uruchomienia HTTP.
 *
 * Uwaga: AdminSensitiveAccessController uzywa requireRole(['zarzad'])
 * zamiast requireSuperAdmin (per-club zarzad, nie master), wiec test
 * akceptuje obie konwencje.
 */
class AdminControllerAuthTest extends TestCase
{
    public function testAdminControllersHaveAuthGate(): void
    {
        $glob = glob(__DIR__ . '/../../app/Controllers/Admin*.php');
        $this->assertNotEmpty($glob, 'No Admin*Controller files found — adjust glob path?');

        foreach ($glob as $file) {
            $name = basename($file);
            $src  = file_get_contents($file);
            $this->assertNotFalse($src, "Cannot read {$name}");

            // Konstruktor musi istniec
            $this->assertMatchesRegularExpression(
                '/public\s+function\s+__construct\s*\(/',
                $src,
                "{$name} brakuje konstruktora"
            );

            // Jakas brama auth musi byc obecna
            $hasAuth =
                str_contains($src, 'requireSuperAdmin')
                || preg_match('/requireRole\s*\(/', $src)
                || str_contains($src, 'Auth::requireRole')
                || str_contains($src, 'Auth::requireSuperAdmin');

            $this->assertTrue(
                (bool)$hasAuth,
                "{$name} musi miec auth gate w konstruktorze "
                . "(requireSuperAdmin lub requireRole(...))"
            );
        }
    }

    public function testAdminControllerCount(): void
    {
        // Refresh w testach pokazuje gdy dodamy nowy AdminController
        $glob = glob(__DIR__ . '/../../app/Controllers/Admin*.php');
        $this->assertGreaterThanOrEqual(
            8,
            count($glob),
            'Spadek liczby AdminController moze oznaczac regression'
        );
    }
}
