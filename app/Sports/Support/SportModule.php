<?php

declare(strict_types=1);

namespace App\Sports\Support;

/**
 * Lekka klasa znacznikowa dla modułu sportowego.
 *
 * Manifest.php danego sportu może wskazać `module => SomeModule::class`,
 * a `SomeModule::status()` zwraca 'full' lub 'partial' (do filtrowania w
 * katalogu sportów oraz raportach dojrzałości modułów).
 *
 * Nie zastępuje BaseSportArchetype — uzupełnia go o metadane "dojrzałości".
 */
abstract class SportModule
{
    /** Klucz sportu (z manifestu). */
    abstract public function key(): string;

    /**
     * Status modułu:
     *   'full'    — pełna implementacja (controllers + views + portal + admin)
     *   'partial' — istnieje tylko DB / podstawowe CRUD
     *   'stub'    — placeholder
     */
    public function status(): string
    {
        return 'partial';
    }

    /** Krótki opis funkcji rozszerzonych (poza generic CRUD). */
    public function fullFeatures(): array
    {
        return [];
    }
}
