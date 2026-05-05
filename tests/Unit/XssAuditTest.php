<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Statyczny audit XSS w plikach widoków.
 *
 * Pure regex scan w app/Views/ szukajacy oczywistych dziur:
 *   1. <?= $_GET / $_POST / $_REQUEST -> instant XSS
 *   2. <?php echo $_GET / $_POST / $_REQUEST  -> instant XSS
 *
 * Bardziej zlozone wzorce (np. `<?= $row['user_input'] ?>`) wymagaja
 * taint analysis i nie sa pokryte tym testem — wymagaja code review.
 *
 * Testy nie sprawdzaja "kazde echo musi byc owiniete View::e" bo:
 *   - liczby (<?= $i + 1 ?>) sa bezpieczne
 *   - sciezki URL (<?= $action ?>) typowo bezpieczne
 *   - klasy CSS (<?= $cls ?>) zwykle bezpieczne (z naszej listy)
 * False-positive flood sprawia ze test bylby ignorowany. Uderzamy
 * tylko w gwarantowanie niebezpieczne echo superglobalu.
 */
class XssAuditTest extends TestCase
{
    public function testNoSuperglobalEchoedDirectly(): void
    {
        $offenders = $this->scanViews(
            // <?= $_GET[...] | <?= $_POST[...] | <?= $_REQUEST[...]
            '/<\?=\s*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "XSS: superglobal echoed bez ucieczki:\n  - " . implode("\n  - ", $offenders)
            . "\nUzyj <?= View::e(\$_GET[...]) ?> lub przeniesc walidacje do controllera."
        );
    }

    public function testNoSuperglobalEchoedViaPhpEcho(): void
    {
        $offenders = $this->scanViews(
            // <?php echo $_GET / $_POST etc.
            '/<\?php\s+echo\s+\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "XSS: superglobal echo via <?php echo:\n  - " . implode("\n  - ", $offenders)
        );
    }

    public function testNoUnescapedSuperglobalInAttributes(): void
    {
        // Wzorzec: atrybut HTML z superglobalem (np. value=" + echo POST tag)
        $offenders = $this->scanViews(
            '/="\s*<\?=\s*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "XSS: superglobal w atrybucie HTML bez ucieczki:\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    /**
     * Scan recursively in app/Views/ and return list of "file:line"
     * matches for the given regex.
     */
    private function scanViews(string $pattern): array
    {
        $base = realpath(__DIR__ . '/../../app/Views');
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
