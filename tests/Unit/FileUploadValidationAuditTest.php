<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Statyczny audit walidacji uploadu plikow. Sprawdza ze kazdy plik ktory
 * uzywa move_uploaded_file() ma takze:
 *   - UPLOAD_ERR_OK check (otherwise przyjmiemy bledne uploady)
 *   - extension lub MIME validation (whitelist .png/.jpg/.csv itp.)
 *
 * Brak walidacji = vector dla:
 *   - upload PHP shell-a (atakujacy uploaduje evil.php → wywoluje go z przegladarki)
 *   - upload SVG ze script-em (XSS gdy serwer renderuje)
 *   - upload nieskonczonego pliku → DoS
 *
 * Pure regex po app/, bez DB / HTTP.
 */
class FileUploadValidationAuditTest extends TestCase
{
    public function testEveryMoveUploadedFileHasUploadErrCheck(): void
    {
        $offenders = [];
        foreach ($this->phpUnder($this->base()) as $file) {
            $src = file_get_contents($file);
            if ($src === false) continue;
            if (!str_contains($src, 'move_uploaded_file')) continue;

            // Same file musi miec UPLOAD_ERR_OK lub error check lub
            // !empty($_FILES[..]['tmp_name']) (weaker ale akceptowalny pattern)
            // lub is_uploaded_file (strongest)
            $hasCheck = str_contains($src, 'UPLOAD_ERR_OK')
                     || preg_match("/\\\$_FILES\\[[^\\]]+\\]\\[[\\'\"]error[\\'\"]\\]/", $src)
                     || preg_match("/!empty\\s*\\(\\s*\\\$_FILES\\[[^\\]]+\\]\\[[\\'\"]tmp_name[\\'\"]\\]/", $src)
                     || str_contains($src, 'is_uploaded_file')
                     // Delegated to model with own validation
                     || preg_match('/->upload[A-Za-z]*\(\s*\$_FILES/', $src);
            if (!$hasCheck) {
                $offenders[] = $file;
            }
        }
        $this->assertEmpty(
            $offenders,
            "Upload bez UPLOAD_ERR_OK / error check:\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    public function testEveryUploadHandlerValidatesExtension(): void
    {
        $offenders = [];
        foreach ($this->phpUnder($this->base()) as $file) {
            $src = file_get_contents($file);
            if ($src === false) continue;
            if (!str_contains($src, 'move_uploaded_file')) continue;

            // Same file musi miec ext whitelist lub MIME validation
            $hasValidation =
                preg_match("/in_array\\s*\\(\\s*\\\$ext\\b/", $src)
                || preg_match("/in_array\\s*\\([^)]+,\\s*\\[[^\\]]*['\"](?:png|jpg|jpeg|gif|webp|svg|pdf|csv|txt)['\"][^\\]]*\\]/", $src)
                || str_contains($src, 'finfo_file')
                || str_contains($src, 'finfo_open')
                || str_contains($src, 'new \\finfo')
                || str_contains($src, 'new finfo')
                || str_contains($src, 'mime_content_type')
                || str_contains($src, 'getimagesize')
                // delegated to a model with own validation
                || preg_match('/->upload\(\s*\$_FILES/', $src);

            if (!$hasValidation) {
                $offenders[] = $file;
            }
        }
        $this->assertEmpty(
            $offenders,
            "Upload bez extension/MIME validation:\n  - " . implode("\n  - ", $offenders)
            . "\nDodaj in_array(\\\$ext, ['png','jpg',...]) lub finfo_file()."
        );
    }

    public function testNoUserNameUsedDirectlyAsFilename(): void
    {
        // Anti-pattern: \$_FILES[..][name] uzyte bezposrednio jako nazwa
        // pliku (bez basename / random gen). Atakujacy moze wgrac
        // "../../etc/passwd.evil" lub "shell.php".
        $offenders = [];
        foreach ($this->phpUnder($this->base()) as $file) {
            $src = file_get_contents($file);
            if ($src === false) continue;
            if (!str_contains($src, 'move_uploaded_file')) continue;

            // Find calls and check if 2nd arg derives from $_FILES[..]['name']
            // bez basename() / pathinfo() / random_bytes().
            // Heurystyka: if file uses $_FILES['x']['name'] AND nie uzywa
            // basename / random_bytes / bin2hex / time() do generowania
            // unique name, flag it.
            if (preg_match("/\\\$_FILES\\[[^\\]]+\\]\\[['\"]name['\"]\\]/", $src)) {
                $hasSafe =
                    str_contains($src, 'basename')
                    || str_contains($src, 'bin2hex')
                    || str_contains($src, 'random_bytes')
                    || preg_match('/\$\w*name\b\s*=\s*[\'\"][^\'\"]*[\'\"]\s*\.\s*time\(\)/', $src)
                    // pathinfo dostarczy ext, klasycznie z sztucznym basename
                    || (str_contains($src, 'pathinfo') && str_contains($src, 'time()'));
                if (!$hasSafe) {
                    $offenders[] = $file;
                }
            }
        }
        $this->assertEmpty(
            $offenders,
            "Upload uzywa \$_FILES[..]['name'] bezposrednio (bez basename/random_bytes):\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    public function testUploadDirNotInWebRoot(): void
    {
        // Upload do /public/uploads/ jest OK gdy serwer nie executes PHP w tym
        // katalogu (.htaccess deny php / nginx location blok). Najbezpieczniej:
        // /storage/ ktory nie jest pod web root.
        // Test sprawdza ze nikt nie wymyslil egzotycznego targetu (np. /public
        // bezposrednio, lub /).
        $offenders = [];
        foreach ($this->phpUnder($this->base()) as $file) {
            $src = file_get_contents($file);
            if ($src === false || !str_contains($src, 'move_uploaded_file')) continue;

            // Wzorzec niebezpieczny: $dir = ROOT_PATH . '/public/' . zmienna_user_input
            if (preg_match("@ROOT_PATH\\s*\\.\\s*['\"]/public/['\"]\\s*\\.\\s*\\\$_(GET|POST)@", $src)) {
                $offenders[] = $file . ' (path includes user input under /public/)';
            }
        }
        $this->assertEmpty(
            $offenders,
            "Upload path zawiera user input pod /public/:\n  - " . implode("\n  - ", $offenders)
        );
    }

    private function base(): string
    {
        return realpath(__DIR__ . '/../../app') ?: '';
    }

    private function phpUnder(string $base): array
    {
        if ($base === '') return [];
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
