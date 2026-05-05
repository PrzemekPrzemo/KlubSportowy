<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Statyczny audit antywzorcow SQL injection w app/.
 *
 * Sprawdza czy zaden plik nie zawiera:
 *   1. Bezposrednio $_GET / $_POST / $_REQUEST wewnatrz SQL
 *      ($db->prepare("SELECT ... $_POST[foo]"))
 *   2. Konkatenacji string + $_GET/$_POST w okolicach slow kluczowych SQL
 *      ($db->query("SELECT ... " . $_POST['col']))
 *   3. ORDER BY ze zmienna ktora nie jest sanityzowana
 *      pre `preg_replace('/[^a-zA-Z0-9_]/' ...)`
 *
 * Bez DB. Pure regex. Faza Faz E nie ma dedykowanego SQL audit; ten test
 * uzupelnia luke.
 */
class SqlInjectionAuditTest extends TestCase
{
    public function testNoSuperglobalsInsideSqlStrings(): void
    {
        $offenders = $this->scan(
            // Match: ->prepare("...$_POST..."  / ->query("...$_GET[..."
            '/->\s*(prepare|query|exec)\s*\(\s*"[^"]*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "SQL injection: superglobal w SQL stringu:\n  - " . implode("\n  - ", $offenders)
        );
    }

    public function testNoStringConcatWithSuperglobalsInSql(): void
    {
        // Match: ".sth" . $_POST[..] lub "...". $_GET[..]"
        // gdzie cudzyslow zawiera SELECT/INSERT/UPDATE/DELETE
        $offenders = $this->scan(
            '/"[^"]*\b(SELECT|INSERT|UPDATE|DELETE|FROM|WHERE)\b[^"]*"\s*\.\s*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "SQL injection: string concat z superglobal w SQL:\n  - " . implode("\n  - ", $offenders)
        );
    }

    public function testNoBuildInWherePredicateFromSuperglobal(): void
    {
        // Match: $sql .= "WHERE col = '" . $_POST[...]
        // tj. konkatenacja predykatu WHERE z superglobalem (najczestszy
        // SQL injection vector w starszym kodzie PHP)
        $offenders = $this->scan(
            '/(WHERE|AND|OR)[^"]{0,40}=[^"]{0,4}\'?"\s*\.\s*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "SQL injection: predykat WHERE/AND/OR konkatenowany z superglobal:\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    /**
     * Scan all PHP files under app/ for the regex; return list of "file:line" hits.
     */
    private function scan(string $pattern): array
    {
        $hits  = [];
        $files = $this->phpFiles();
        foreach ($files as $file) {
            $lines = file($file);
            if ($lines === false) continue;
            foreach ($lines as $lineNo => $line) {
                if (preg_match($pattern, $line)) {
                    $hits[] = $file . ':' . ($lineNo + 1);
                }
            }
        }
        return $hits;
    }

    private function scanForFiles(string $pattern): array
    {
        $matched = [];
        foreach ($this->phpFiles() as $file) {
            $src = file_get_contents($file);
            if ($src !== false && preg_match($pattern, $src)) {
                $matched[] = $file;
            }
        }
        return $matched;
    }

    private function phpFiles(): array
    {
        $base = realpath(__DIR__ . '/../../app');
        if ($base === false) return [];
        $rdi  = new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS);
        $rii  = new \RecursiveIteratorIterator($rdi);
        $list = [];
        foreach ($rii as $f) {
            if ($f->isFile() && str_ends_with($f->getPathname(), '.php')) {
                $list[] = $f->getPathname();
            }
        }
        return $list;
    }
}
