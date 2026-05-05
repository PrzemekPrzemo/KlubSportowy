<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Statyczny audit Open Redirect — wykrywa antywzorce ktore pozwalalyby
 * atakujacemu przekierowac uzytkownika na zewnetrzna domene (typowy
 * vector phishing/credential-harvesting).
 *
 * Wzorce uznane za niebezpieczne:
 *   1. header('Location: ' . $_GET / $_POST / $_REQUEST)
 *      — superglobal flowing wprost do Location header
 *   2. header("Location: $_GET[..]") — interpolacja w stringu
 *   3. redirect($_GET / $_POST / $_REQUEST) — uzycie helpera bez sanityzacji
 *
 * Bezpieczne wzorce (legitnie uzywane w kodzie):
 *   - header('Location: ' . url($path))     — url() prepends BASE_URL
 *   - header('Location: ' . $referer) gdy parse_url($referer)['host']
 *     jest weryfikowany do $_SERVER['HTTP_HOST']
 *   - header('Location: ' . $checkoutUrl) gdzie $checkoutUrl pochodzi
 *     z trustedweryfikowanego provider'a (np. Stripe)
 *
 * Pure regex po app/. Bez DB, bez HTTP.
 */
class OpenRedirectAuditTest extends TestCase
{
    public function testNoSuperglobalConcatInLocationHeader(): void
    {
        $offenders = $this->scan(
            '/header\(\s*["\']Location:[^"\']*["\']\s*\.\s*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "Open redirect: superglobal w Location header (concat):\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    public function testNoSuperglobalInterpolatedInLocationHeader(): void
    {
        $offenders = $this->scan(
            '/header\(\s*"Location:[^"]*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "Open redirect: superglobal interpolowany w Location header:\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    public function testNoSuperglobalPassedToRedirectHelper(): void
    {
        // ->redirect($_GET[...]) lub redirect($_POST[...])
        $offenders = $this->scan(
            '/(?:->|::|\b)redirect\(\s*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "Open redirect: superglobal jako argument redirect():\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    public function testNoTrimSuperglobalIntoRedirect(): void
    {
        // redirect(trim($_POST[..])) bez weryfikacji hosta
        $offenders = $this->scan(
            '/redirect\(\s*trim\s*\(\s*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "Open redirect: trim(superglobal) → redirect:\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    /**
     * Recursively scan app/ for the regex; return file:line hits.
     */
    private function scan(string $pattern): array
    {
        $base = realpath(__DIR__ . '/../../app');
        if ($base === false) return [];
        $rdi  = new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS);
        $rii  = new \RecursiveIteratorIterator($rdi);
        $hits = [];
        foreach ($rii as $f) {
            if (!$f->isFile() || !str_ends_with($f->getPathname(), '.php')) continue;
            $lines = file($f->getPathname());
            if ($lines === false) continue;
            foreach ($lines as $idx => $line) {
                if (preg_match($pattern, $line)) {
                    $hits[] = $f->getPathname() . ':' . ($idx + 1);
                }
            }
        }
        return $hits;
    }
}
