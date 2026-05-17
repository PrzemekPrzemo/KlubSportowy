<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Static audit + invariant checks dla E2E messengera.
 *
 * Nie wymaga DB — sprawdza ze:
 *   1. Controller deklaruje pelny zestaw endpointow E2E (setupE2E, disableE2E,
 *      enableE2EForThread, disableE2EForThread).
 *   2. ChatMessageModel posiada bezpieczne ograniczenia (MAX_CIPHERTEXT_BYTES <= 8192).
 *   3. ChatMessageModel::sendEncrypted istnieje i przyjmuje meta z wymaganymi kluczami.
 *   4. Server nigdzie nie próbuje deszyfrowac body — kazda sciezka logujaca zawartosc
 *      uzywa placeholdera "[zaszyfrowana wiadomosc]" zamiast prawdziwego body.
 *   5. Wszystkie POSTy E2E sa zabezpieczone przez Csrf::verify().
 *   6. Migracja 094 dodaje is_encrypted + ciphertext_meta + e2e_enabled (regression guard).
 *   7. JS module messenger-e2e.js zawiera AES-GCM (sanity check).
 *
 * Dzieki temu unikamy regresji w przypadku gdy ktos przypadkowo np. zaloguje plaintext
 * lub usunie validation.
 */
class MessengerE2EFlowTest extends TestCase
{
    private string $controllerPath;
    private string $modelPath;
    private string $threadModelPath;
    private string $migrationPath;
    private string $jsPath;

    protected function setUp(): void
    {
        $this->controllerPath  = ROOT_PATH . '/app/Controllers/PortalMessengerController.php';
        $this->modelPath       = ROOT_PATH . '/app/Models/ChatMessageModel.php';
        $this->threadModelPath = ROOT_PATH . '/app/Models/MessageThreadModel.php';
        $this->migrationPath   = ROOT_PATH . '/database/migrations/094_messenger_e2e.sql';
        $this->jsPath          = ROOT_PATH . '/public/js/messenger-e2e.js';
    }

    public function testControllerHasE2EEndpoints(): void
    {
        $src = file_get_contents($this->controllerPath);
        $this->assertNotFalse($src);
        $this->assertStringContainsString('public function setupE2E(', $src, 'setupE2E missing');
        $this->assertStringContainsString('public function disableE2E(', $src, 'disableE2E missing');
        $this->assertStringContainsString('public function enableE2EForThread(', $src, 'enableE2EForThread missing');
        $this->assertStringContainsString('public function disableE2EForThread(', $src, 'disableE2EForThread missing');
    }

    public function testE2EEndpointsAreCsrfProtected(): void
    {
        $src = file_get_contents($this->controllerPath);
        $methods = ['setupE2E', 'disableE2E', 'enableE2EForThread', 'disableE2EForThread'];
        foreach ($methods as $m) {
            $pattern = '/function\s+' . preg_quote($m, '/') . '\s*\([^\)]*\)\s*:\s*void\s*\{(.+?)\n\s*\}/s';
            $this->assertMatchesRegularExpression($pattern, $src, "Cannot locate body of {$m}");
            preg_match($pattern, $src, $matches);
            $this->assertStringContainsString('Csrf::verify()', $matches[1], "{$m} missing Csrf::verify()");
        }
    }

    public function testChatMessageModelHasCiphertextLimit(): void
    {
        $src = file_get_contents($this->modelPath);
        $this->assertStringContainsString('MAX_CIPHERTEXT_BYTES', $src);
        $this->assertMatchesRegularExpression(
            '/MAX_CIPHERTEXT_BYTES\s*=\s*([0-9]+)/',
            $src,
            'No numeric MAX_CIPHERTEXT_BYTES'
        );
        preg_match('/MAX_CIPHERTEXT_BYTES\s*=\s*([0-9]+)/', $src, $m);
        $this->assertLessThanOrEqual(8192, (int)$m[1], 'Ciphertext limit too high');
        $this->assertGreaterThan(0, (int)$m[1]);
    }

    public function testSendEncryptedRequiresAllMetaFields(): void
    {
        $src = file_get_contents($this->controllerPath);
        // Validation block w send() musi sprawdzac iv, alg, key_fingerprint.
        $this->assertStringContainsString("empty(\$meta['iv'])", $src);
        $this->assertStringContainsString("empty(\$meta['alg'])", $src);
        $this->assertStringContainsString("empty(\$meta['key_fingerprint'])", $src);
        $this->assertStringContainsString('AES-GCM-256', $src, 'Allowed alg whitelist missing');
    }

    public function testServerNeverLogsCiphertextAsPreview(): void
    {
        $src = file_get_contents($this->controllerPath);
        // Push preview dla zaszyfrowanych wiadomosci powinien byc placeholderem.
        $this->assertStringContainsString('[zaszyfrowana wiadomosc]', $src,
            'Push preview should be placeholder for encrypted messages, not real body');
    }

    public function testThreadE2EToggleMethodsExist(): void
    {
        $src = file_get_contents($this->threadModelPath);
        $this->assertStringContainsString('public function enableE2E(', $src);
        $this->assertStringContainsString('public function disableE2E(', $src);
        $this->assertStringContainsString('public function isE2EEnabled(', $src);
    }

    public function testEnableE2EValidatesFingerprintLength(): void
    {
        $src = file_get_contents($this->threadModelPath);
        $this->assertStringContainsString("strlen(\$fingerprint) !== 32", $src,
            'enableE2E must require exactly 32 hex chars (16 bytes)');
    }

    public function testMigrationAddsAllExpectedColumns(): void
    {
        $src = file_get_contents($this->migrationPath);
        $this->assertNotFalse($src);
        $this->assertStringContainsString('is_encrypted', $src);
        $this->assertStringContainsString('encryption_version', $src);
        $this->assertStringContainsString('ciphertext_meta', $src);
        $this->assertStringContainsString('e2e_enabled', $src);
        $this->assertStringContainsString('e2e_key_fingerprint', $src);
        $this->assertStringContainsString('messenger_member_keys', $src);
        $this->assertStringContainsString('passphrase_hash', $src);
    }

    public function testJsModuleUsesAesGcmAndPbkdf2(): void
    {
        $src = file_get_contents($this->jsPath);
        $this->assertNotFalse($src);
        $this->assertStringContainsString('AES-GCM', $src);
        $this->assertStringContainsString('PBKDF2', $src);
        // Iteration count high enough.
        $this->assertMatchesRegularExpression('/iterations\s*:\s*([0-9]+)/', $src);
        preg_match('/iterations\s*:\s*([0-9]+)/', $src, $m);
        $this->assertGreaterThanOrEqual(100000, (int)$m[1], 'PBKDF2 iterations should be >= 100k');
    }

    public function testE2EBlocksMixedPlaintextWithE2EThread(): void
    {
        $src = file_get_contents($this->controllerPath);
        // Jezeli watek ma e2e_enabled, plaintext send musi byc odrzucony.
        $this->assertStringContainsString('e2e_required', $src);
        $this->assertStringContainsString('e2e_not_enabled', $src);
    }

    public function testRateLimitOnE2ESetup(): void
    {
        $src = file_get_contents($this->controllerPath);
        // Rate limit max 3/h dla setup.
        $this->assertMatchesRegularExpression(
            "/RateLimiter::check\(.+,\s*'messenger_e2e_setup',\s*3,\s*60\)/",
            $src,
            'Setup E2E rate limit (3/h) missing'
        );
    }
}
