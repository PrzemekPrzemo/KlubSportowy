<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Statyczny audit Mass Assignment.
 *
 * Mass assignment = przekazanie `$_POST` (lub `$_GET`) bezposrednio jako
 * tablicy do `Model::insert()` / `Model::update()`. Atakujacy doklada
 * dodatkowe pola (np. `is_admin=1`, `club_id=42`, `role=zarzad`) i
 * eskaluje uprawnienia / wlamuje sie do innego klubu.
 *
 * Codebase ClubDesk uzywa wzorca `parsePost()` w kazdym controllerze,
 * ktory whitelistuje pola — wiec mass assignment nie jest mozliwy.
 * Ten test gwarantuje ze ten wzorzec nie zostanie obejsciony.
 *
 * Pure regex, bez DB i HTTP.
 */
class MassAssignmentAuditTest extends TestCase
{
    public function testNoSuperglobalDirectlyToInsert(): void
    {
        $offenders = $this->scan(
            '/->insert\(\s*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "Mass assignment: \$_POST/\$_GET prosto do ->insert():\n  - "
            . implode("\n  - ", $offenders)
            . "\nUzyj parsePost() / whitelistowania pol."
        );
    }

    public function testNoSuperglobalDirectlyToUpdate(): void
    {
        // ->update($id, $_POST)  /  ->update((int)$id, $_GET)
        $offenders = $this->scan(
            '/->update\(\s*[^,]*,\s*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "Mass assignment: \$_POST/\$_GET prosto do ->update():\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    public function testNoSuperglobalSpreadIntoInsert(): void
    {
        // ->insert([...$_POST])  / ->insert(['x' => 1, ...$_POST])
        $offenders = $this->scan(
            '/->insert\(\s*\[[^\]]*\.\.\.\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "Mass assignment: spread \$_POST/\$_GET w array ->insert():\n  - "
            . implode("\n  - ", $offenders)
        );
    }

    public function testNoSuperglobalMergeWithoutWhitelist(): void
    {
        // array_merge($defaults, $_POST) — wlewa wszystkie klucze
        // ze superglobala. Powinno byc array_intersect_key zamiast.
        $offenders = $this->scan(
            '/array_merge\([^)]*\$_(GET|POST|REQUEST)\b/'
        );
        $this->assertEmpty(
            $offenders,
            "Mass assignment: array_merge z \$_POST/\$_GET (wszystkie klucze):\n  - "
            . implode("\n  - ", $offenders)
            . "\nUzyj array_intersect_key(\$_POST, array_flip(\$allowed)) lub parsePost()."
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
