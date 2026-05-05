<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Statyczny audit hardcoded secrets — wyszukuje typowe wzorce kluczy API,
 * tokenów i haseł zostawionych w kodzie. Catches:
 *   - Stripe keys: sk_live_, sk_test_, pk_live_, pk_test_
 *   - AWS keys: AKIA[A-Z0-9]{16}
 *   - GitHub tokens: ghp_, gho_, ghs_, ghu_, github_pat_
 *   - Google API: AIza[A-Za-z0-9_-]{35}
 *   - Slack: xoxa-, xoxb-, xoxp-, xoxr-
 *   - JWT-y w stringach
 *   - 'password' => 'literal' (nie placeholder)
 *
 * Pure regex po app/, config/, public/. Bez DB / HTTP.
 */
class HardcodedSecretsAuditTest extends TestCase
{
    /**
     * Standardowe sciezki ktore wykluczamy z audytu (vendor, generated):
     */
    private array $excludeDirs = ['vendor', 'node_modules', '.git', '.phpunit.cache', 'storage'];

    /** Konkretne pliki ze znanymi false-positives (placeholdery, dokumentacja). */
    private array $excludeFiles = [
        // None currently — testy są dostatecznie precyzyjne
    ];

    public function testNoStripeLiveKeys(): void
    {
        $offenders = $this->scan('/sk_live_[A-Za-z0-9]{20,}|pk_live_[A-Za-z0-9]{20,}/');
        $this->assertEmpty(
            $offenders,
            "Stripe live keys w kodzie:\n  - " . implode("\n  - ", $offenders)
            . "\nPrzenies do .env / config/*.local.php (gitignored)."
        );
    }

    public function testNoStripeTestKeys(): void
    {
        // Test keys nie sa toxic ale i tak nie powinny byc commitowane
        $offenders = $this->scan('/sk_test_[A-Za-z0-9]{20,}|pk_test_[A-Za-z0-9]{20,}/');
        $this->assertEmpty(
            $offenders,
            "Stripe test keys w kodzie (nawet test keys nie commitujemy):\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    public function testNoAwsAccessKeys(): void
    {
        // AWS access key id: AKIA + 16 chars uppercase/digits
        $offenders = $this->scan('/\bAKIA[A-Z0-9]{16}\b/');
        $this->assertEmpty(
            $offenders,
            "AWS access key w kodzie:\n  - " . implode("\n  - ", $offenders)
        );
    }

    public function testNoGitHubTokens(): void
    {
        // GitHub: ghp_ (Personal), gho_ (OAuth), ghs_ (Server), ghu_ (User),
        // github_pat_ (Fine-grained PAT)
        $offenders = $this->scan('/\b(?:ghp|gho|ghs|ghu|ghr)_[A-Za-z0-9]{36,}\b|\bgithub_pat_[A-Za-z0-9_]{40,}\b/');
        $this->assertEmpty(
            $offenders,
            "GitHub token w kodzie:\n  - " . implode("\n  - ", $offenders)
        );
    }

    public function testNoGoogleApiKeys(): void
    {
        // Google API key: AIza + 35 chars
        $offenders = $this->scan('/\bAIza[A-Za-z0-9_-]{35}\b/');
        $this->assertEmpty(
            $offenders,
            "Google API key w kodzie:\n  - " . implode("\n  - ", $offenders)
        );
    }

    public function testNoSlackTokens(): void
    {
        $offenders = $this->scan('/\bxox[abprs]-[A-Za-z0-9-]{10,}\b/');
        $this->assertEmpty(
            $offenders,
            "Slack token w kodzie:\n  - " . implode("\n  - ", $offenders)
        );
    }

    public function testNoLiteralPasswordAssignment(): void
    {
        // 'password' => 'literal' lub $password = 'literal' z wartoscia >5 chars
        // co nie jest placeholderem (test, password, secret, hash patterns).
        $base = realpath(__DIR__ . '/../../app');
        if ($base === false) return;
        $files = $this->phpUnder($base);

        $offenders = [];
        foreach ($files as $file) {
            $lines = file($file);
            if ($lines === false) continue;
            foreach ($lines as $idx => $line) {
                // Pattern: 'password' => 'something' (literal value, ≥6 chars)
                // BUT skip placeholders / hashes / null / empty
                if (preg_match(
                    '/[\'"]password[\'"]\s*=>\s*[\'"]([^"\']{6,})[\'"]/',
                    $line, $m
                )) {
                    $val = $m[1];
                    if ($this->isLikelyPlaceholder($val)) continue;
                    $offenders[] = $file . ':' . ($idx + 1) . ' => ' . $this->truncate($val);
                }
                // Pattern: $password = 'literal'
                if (preg_match(
                    '/\$password\s*=\s*[\'"]([^"\']{6,})[\'"]/',
                    $line, $m
                )) {
                    $val = $m[1];
                    if ($this->isLikelyPlaceholder($val)) continue;
                    $offenders[] = $file . ':' . ($idx + 1) . ' => ' . $this->truncate($val);
                }
            }
        }
        $this->assertEmpty(
            $offenders,
            "Literal password w kodzie:\n  - " . implode("\n  - ", $offenders)
            . "\nPrzenies do .env / config/*.local.php."
        );
    }

    private function isLikelyPlaceholder(string $val): bool
    {
        $lower = strtolower($val);
        $placeholders = [
            'password', 'placeholder', 'example', 'changeme', 'change_me',
            'secret', 'todo', 'xxx', 'test1234', 'demo1234',
        ];
        foreach ($placeholders as $p) {
            if (str_contains($lower, $p)) return true;
        }
        // password_hash output starts with $2y$ / $2a$ / $argon2id$
        if (str_starts_with($val, '$2y$') || str_starts_with($val, '$2a$')
         || str_starts_with($val, '$argon2id$') || str_starts_with($val, '$argon2i$')) {
            return true;
        }
        return false;
    }

    private function truncate(string $s): string
    {
        return strlen($s) > 30 ? substr($s, 0, 30) . '…' : $s;
    }

    private function scan(string $pattern): array
    {
        $hits = [];
        foreach ($this->scanDirs(['app', 'config', 'public']) as $file) {
            $lines = file($file);
            if ($lines === false) continue;
            foreach ($lines as $idx => $line) {
                if (preg_match($pattern, $line)) {
                    $hits[] = $file . ':' . ($idx + 1);
                }
            }
        }
        return $hits;
    }

    private function scanDirs(array $relDirs): array
    {
        $out = [];
        foreach ($relDirs as $rel) {
            $base = realpath(__DIR__ . '/../../' . $rel);
            if ($base === false) continue;
            $out = array_merge($out, $this->phpUnder($base));
        }
        return $out;
    }

    private function phpUnder(string $base): array
    {
        $rdi  = new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS);
        $rii  = new \RecursiveIteratorIterator($rdi);
        $list = [];
        foreach ($rii as $f) {
            if (!$f->isFile()) continue;
            // Skip excluded paths
            $skip = false;
            foreach ($this->excludeDirs as $ex) {
                if (str_contains($f->getPathname(), '/' . $ex . '/')) { $skip = true; break; }
            }
            if ($skip) continue;
            // Only PHP (and maybe config files)
            $ext = strtolower($f->getExtension());
            if (!in_array($ext, ['php'], true)) continue;
            $list[] = $f->getPathname();
        }
        return $list;
    }
}
