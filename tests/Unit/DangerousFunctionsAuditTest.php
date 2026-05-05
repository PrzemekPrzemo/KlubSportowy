<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Statyczny audit niebezpiecznych funkcji PHP — eval, unserialize z user
 * input, exec/system bez escapeshellarg, preg_replace z /e modifier.
 *
 * Pure regex po app/, bez DB / HTTP.
 */
class DangerousFunctionsAuditTest extends TestCase
{
    public function testNoEvalCallAtAll(): void
    {
        // eval() jest praktycznie zawsze zly. Zero tolerancji.
        $offenders = $this->scan('/\beval\s*\(/');
        $this->assertEmpty(
            $offenders,
            "eval() w kodzie:\n  - " . implode("\n  - ", $offenders)
            . "\neval jest niemal zawsze zlym pomyslem — uzyj match/closure."
        );
    }

    public function testNoUnserializeWithSuperglobal(): void
    {
        // unserialize($_GET[..]) — RCE via Object Injection (PHP gadgets)
        $offenders = $this->scan('/unserialize\s*\(\s*[^()]*\$_(GET|POST|REQUEST|COOKIE)\b/');
        $this->assertEmpty(
            $offenders,
            "unserialize z user input (Object Injection RCE):\n  - "
            . implode("\n  - ", $offenders)
            . "\nUzyj json_decode lub PHP serialization tylko na trusted data."
        );
    }

    public function testNoExecOrSystemWithSuperglobal(): void
    {
        // exec/system/shell_exec/passthru z user input (RCE)
        $offenders = $this->scan(
            '/(?:exec|system|shell_exec|passthru|popen|proc_open)\s*\([^)]*\$_(GET|POST|REQUEST|COOKIE)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "exec/system z user input (RCE):\n  - " . implode("\n  - ", $offenders)
            . "\nZawsze owijaj args w escapeshellarg() lub uzyj parametryzowanych API."
        );
    }

    public function testNoExecOrSystemConcatWithSuperglobal(): void
    {
        // exec("ls " . $_GET['dir']) — string concat z user input bez escape
        $offenders = $this->scan(
            '/(?:exec|system|shell_exec|passthru|popen|proc_open)\s*\([^)]*[\'\"][^"\']*[\'\"]\s*\.\s*\$_(GET|POST|REQUEST|COOKIE)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "exec/system konkatenowany z user input:\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    public function testNoPregReplaceEModifier(): void
    {
        // /e modifier byl deprecated od PHP 7 (kod php pattern executes).
        // Wzorzec: ostatni delimiter / ze stringiem zawierajacym 'e' modifier.
        $offenders = $this->scan(
            '/preg_replace\s*\(\s*[\'"][^"\']+[\'"][\'"]\s*[a-z]*e[a-z]*[\'"]/i'
        );
        $this->assertEmpty(
            $offenders,
            "preg_replace z /e modifier (deprecated, kod-wykonujacy):\n  - "
            . implode("\n  - ", $offenders)
            . "\nUzyj preg_replace_callback."
        );
    }

    public function testNoCreateFunction(): void
    {
        // create_function() byl deprecated, generuje runtime PHP code
        $offenders = $this->scan('/\bcreate_function\s*\(/');
        $this->assertEmpty(
            $offenders,
            "create_function() (deprecated, RCE risk):\n  - "
            . implode("\n  - ", $offenders)
            . "\nUzyj closure (function() {}) lub fn() syntax."
        );
    }

    public function testNoAssertWithStringArg(): void
    {
        // assert($string) wykonuje string jako PHP code (deprecated zachowanie)
        $offenders = $this->scan('/\bassert\s*\(\s*[\'\"]/');
        $this->assertEmpty(
            $offenders,
            "assert() ze string argumentem (executes code):\n  - "
            . implode("\n  - ", $offenders)
        );
    }

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
