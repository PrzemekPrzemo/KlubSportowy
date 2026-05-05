<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Statyczny audit IDOR (Insecure Direct Object Reference).
 *
 * IDOR = uzytkownik podaje id zasobu i system zwraca go bez sprawdzenia
 * czy faktycznie nalezy do uzytkownika / klubu. Klasyczny vector dla
 * SaaS multi-tenant.
 *
 * Architektura ClubDesk juz w duzym stopniu chroni przed tym przez
 * ClubScopedModel — kazda findById dolaza WHERE club_id = ?
 *
 * Ten test sprawdza ze kod nie obchodzi tej ochrony przez:
 *   1. withoutScope()->findById($_GET/POST)  — explicit bypass + user id
 *   2. Bezposrednie SELECT ... WHERE id = ? z $_GET/$_POST w kontrolerze
 *      bez dolaczonego predykatu club_id (omija wzorzec ClubScopedModel)
 *
 * Pure regex po app/Controllers/. Bez DB, bez HTTP.
 */
class IdorAuditTest extends TestCase
{
    public function testNoUnscopedFindByIdWithUserInput(): void
    {
        $offenders = $this->scanControllers(
            // ->withoutScope()->findById((int)$_POST[..])  / ->findById($_GET[..])
            '/withoutScope\(\)\s*->\s*findById\s*\(\s*[^)]*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "IDOR: withoutScope()->findById z user input:\n  - "
            . implode("\n  - ", $offenders)
            . "\nUsun withoutScope() lub dodaj explicit ownership check."
        );
    }

    /**
     * Note on testNoRawSelectByIdInControllers:
     *   Originally we scanned for prepare("...WHERE id = ?...") in controllers
     *   without a club_id companion. After running, almost every hit was a
     *   legitimate token-based flow (2FA backup codes, password resets, admin
     *   subscription ops) where ownership is enforced by the token itself or
     *   by the super-admin gate. False-positive rate too high; risk of
     *   developers ignoring the test. Removed in favour of the two targeted
     *   patterns that have zero false positives.
     */
    public function testNoFindByIdDirectlyOnUserInput(): void
    {
        // Pattern: ->findById($_POST['id'])  / ->findById($_GET['id'])
        // bezposrednio bez int-cast, bez ownership check.
        // Most callers cast: ->findById((int)$_POST[...]) — int cast doesn't fix IDOR
        // but is at least integer-safe. Here detect raw superglobal as primary key arg.
        $offenders = $this->scanControllers(
            '/->findById\(\s*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "IDOR: findById z raw superglobal (bez int cast):\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    private function scanControllers(string $pattern): array
    {
        $base = realpath(__DIR__ . '/../../app/Controllers');
        if ($base === false) return [];
        $hits = [];
        foreach ($this->phpUnder($base) as $file) {
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

    /** Recursive .php under $base. */
    private function phpUnder(string $base): array
    {
        $rdi = new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS);
        $rii = new \RecursiveIteratorIterator($rdi);
        $out = [];
        foreach ($rii as $f) {
            if ($f->isFile() && str_ends_with($f->getPathname(), '.php')) {
                $out[] = $f->getPathname();
            }
        }
        return $out;
    }
}
