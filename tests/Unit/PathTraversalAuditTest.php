<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Statyczny audit Path Traversal.
 *
 * Path traversal = atakujacy podaje "../../etc/passwd" w polu file/path
 * i czyta/modyfikuje pliki poza dozwolonym katalogiem. Vector dotyczy
 * file_get_contents, file_put_contents, fopen, unlink, include, require,
 * readfile gdy argument zalezy od user input bez walidacji.
 *
 * Codebase juz uzywa bezpiecznych wzorcow:
 *   - upload paths sa generowane przez bin2hex(random_bytes(N)) (unique)
 *   - basename() / pathinfo() do wyizolowania nazwy pliku
 *   - fix uploadDir przed concat z $name
 *   - never include($_GET[..])
 *
 * Ten test sprawdza ze antywzorzec nie wkradnie sie z regression'em.
 *
 * Pure regex po app/, bez DB / HTTP.
 */
class PathTraversalAuditTest extends TestCase
{
    public function testNoSuperglobalInIncludeOrRequire(): void
    {
        $offenders = $this->scan(
            // include $_GET / require $_POST itp.
            '/(?:include|require)(?:_once)?\s*\(?\s*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "Path traversal: include/require z superglobal:\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    public function testNoSuperglobalInFileOps(): void
    {
        $offenders = $this->scan(
            // file_get_contents($_POST[..]), unlink($_GET[..]), fopen($_REQUEST[..])
            '/(?:file_get_contents|file_put_contents|fopen|fread|fwrite|readfile|unlink|copy|rename)\s*\(\s*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "Path traversal: file op bezposrednio z superglobal:\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    public function testNoConcatSuperglobalInFilePath(): void
    {
        // unlink('/some/dir/' . $_POST[..])  / file_get_contents("/path/$_GET[..]")
        $offenders = $this->scan(
            '/(?:file_get_contents|file_put_contents|fopen|readfile|unlink|copy|rename)\s*\([^)]*[\'\"][^"\']*[\'\"]\s*\.\s*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "Path traversal: file path konkatenowany z superglobal:\n  - "
            . implode("\n  - ", $offenders)
            . "\nUzyj basename() / pathinfo() lub random nazwy pliku."
        );
    }

    public function testNoMoveUploadedFileWithSuperglobalDest(): void
    {
        // move_uploaded_file($tmp, $_POST[..]) — atakujacy steruje destination
        $offenders = $this->scan(
            '/move_uploaded_file\s*\([^,)]+,\s*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "Path traversal: move_uploaded_file z superglobal jako destination:\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    public function testNoUserNameInPathWithoutBasename(): void
    {
        // Pattern hint: $_FILES['xxx']['name'] uzywany w sciezce bez basename()
        // (typowy newbie mistake — atakujacy uploaduje plik o nazwie "../../evil.php")
        $offenders = $this->scan(
            '/[\'\"][^\'\"]*[\'\"]\s*\.\s*\$_FILES\[[^\]]+\]\[[\'\"]name[\'\"]\]/'
        );
        $this->assertEmpty(
            $offenders,
            "Path traversal: FILES[name] uzyte w path bez basename():\n  - "
            . implode("\n  - ", $offenders)
            . "\nUzyj basename() lub random nazwy."
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
