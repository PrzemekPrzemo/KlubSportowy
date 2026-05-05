<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * F3 lite — statyczny audit ze wszystkie controllery dotykajace danych
 * wrazliwych (medyczne, anti-doping, body_metrics, emergency_contacts,
 * minor_consents) maja `requireSensitiveAccess()` w konstruktorze
 * (RBAC: zarzad/trener/instruktor/lekarz/super-admin).
 *
 * Dane wrazliwe sa szyfrowane AES-256-GCM (EncryptsFields trait) i
 * audytowane przez SensitiveAccessLogModel — ale wystawienie endpointu
 * bez gate-a obchodzi caly stack.
 *
 * Lapie regression: ktos refactoruje konstruktor i przypadkowo usuwa
 * requireSensitiveAccess(), eksponujac CRUD danych medycznych.
 *
 * Pure file-content scan — bez DB, bez HTTP.
 */
class SensitiveControllersAuthTest extends TestCase
{
    /**
     * Lista kontrolerow ktore MUSZA chronic dane wrazliwe.
     * Zmiana: jesli dodamy nowy controller dotykajacy danych medycznych /
     * anti-doping / body_metrics / emergency_contacts / minor_consents,
     * dopisujemy go tutaj.
     */
    private array $sensitiveControllers = [
        'BodyMetricsController',
        'ComplianceController',
        'EmergencyContactsController',
        'MedicalExamsController',
    ];

    public function testEachSensitiveControllerRequiresSensitiveAccess(): void
    {
        foreach ($this->sensitiveControllers as $name) {
            $file = __DIR__ . '/../../app/Controllers/' . $name . '.php';
            $this->assertFileExists($file, "{$name}.php nie istnieje");

            $src = file_get_contents($file);

            // Wymagamy obecnosci requireSensitiveAccess() w pliku
            // (typowo w konstruktorze, ale fallback na inne miejsca tez OK).
            $hasGate = str_contains($src, 'requireSensitiveAccess')
                    || str_contains($src, 'Auth::requireSensitiveAccess');

            $this->assertTrue(
                $hasGate,
                "{$name} musi wywolywac requireSensitiveAccess() — "
                . 'bez tego endpoint zwraca dane wrazliwe bez RBAC'
            );

            // Plus zwykly requireLogin (sanity check)
            $hasLogin = str_contains($src, 'requireLogin')
                     || str_contains($src, 'Auth::requireLogin');
            $this->assertTrue(
                $hasLogin,
                "{$name} brak requireLogin() — controller dostepny anon!"
            );
        }
    }

    /**
     * Controllery ktore uzywaja sensytywnego Modelu, ale tylko do
     * read-only agregatow (nie wystawiaja CRUD-u na encrypted polach).
     * Trzymamy whitelisted z uzasadnieniem.
     */
    private array $readOnlyExempt = [
        'DashboardController' => 'tylko MedicalExamModel::expiringSoon (liczniki/daty)',
    ];

    public function testNewSensitiveTablesShouldBeAddedToList(): void
    {
        $allControllers = glob(__DIR__ . '/../../app/Controllers/*Controller.php');
        $modelsWithEncryption = $this->modelsWithEncryptionTrait();
        $expected = array_map(fn($c) => basename($c, '.php'), $this->sensitiveControllers);

        $forgotten = [];
        foreach ($allControllers as $file) {
            $name = basename($file, '.php');
            // Skip Admin* (covered by F2) and Base controllers
            if (str_starts_with($name, 'Admin') || $name === 'BaseController') continue;
            // Skip MemberPortalController — uses requireSensitiveAccess on specific
            // actions, plus it's a special portal controller
            if ($name === 'MemberPortalController') continue;
            // Skip explicitly-whitelisted read-only consumers
            if (isset($this->readOnlyExempt[$name])) continue;

            $src = file_get_contents($file);
            $usesSensitiveModel = false;
            foreach ($modelsWithEncryption as $modelClass) {
                if (str_contains($src, "use App\\Models\\{$modelClass};")) {
                    $usesSensitiveModel = true;
                    break;
                }
            }
            if ($usesSensitiveModel && !in_array($name, $expected, true)) {
                $forgotten[] = $name;
            }
        }

        $this->assertEmpty(
            $forgotten,
            'Te controllery uzywaja modeli z EncryptsFields ale nie ma ich w '
            . 'sensitiveControllers w tym tescie: ' . implode(', ', $forgotten)
            . '. Albo dopisz do listy, albo dodaj do readOnlyExempt z '
            . 'uzasadnieniem (gdy tylko czytasz daty/liczniki).'
        );
    }

    /**
     * Skanuje app/Models/ szukajac klas uzywajacych traitu EncryptsFields.
     */
    private function modelsWithEncryptionTrait(): array
    {
        $out  = [];
        $glob = glob(__DIR__ . '/../../app/Models/*Model.php') ?: [];
        foreach ($glob as $file) {
            $src = file_get_contents($file);
            if (str_contains($src, 'use EncryptsFields')
             || str_contains($src, 'use Traits\\EncryptsFields')) {
                $out[] = basename($file, '.php');
            }
        }
        return $out;
    }
}
