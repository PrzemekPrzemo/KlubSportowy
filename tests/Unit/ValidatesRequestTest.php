<?php

namespace Tests\Unit;

use App\Helpers\ValidatesRequest;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 *
 * Unit test ValidatesRequest trait — sprawdza walidacje typow bez DB.
 *
 * Trait flashuje + redirect na fail; te testy weryfikuja happy path
 * (zwracanie wartosci dla poprawnych inputow). Negatywne sciezki
 * z redirectem testowane integracyjnie (vide Tests\Integration).
 */
class ValidatesRequestTest extends TestCase
{
    private object $stub;

    protected function setUp(): void
    {
        parent::setUp();
        // Anonymous class używająca traita — pozwala wywołać protected metody.
        $this->stub = new class {
            use ValidatesRequest {
                validateInt           as public;
                validateString        as public;
                validateOptionalString as public;
                validateInList        as public;
                validateDate          as public;
                validateOptionalInt   as public;
            }
        };
    }

    public function testValidateIntAcceptsValidNumeric(): void
    {
        $this->assertSame(42, $this->stub->validateInt('42', 'foo', 1, 100, '/'));
        $this->assertSame(0, $this->stub->validateInt('0', 'foo', 0, 100, '/'));
    }

    public function testValidateStringTrimsAndAccepts(): void
    {
        $this->assertSame('hello', $this->stub->validateString('  hello  ', 'foo', 1, 100, '/'));
    }

    public function testValidateOptionalStringReturnsNullForEmpty(): void
    {
        $this->assertNull($this->stub->validateOptionalString('', 100, '/'));
        $this->assertNull($this->stub->validateOptionalString('   ', 100, '/'));
        $this->assertSame('hi', $this->stub->validateOptionalString('hi', 100, '/'));
    }

    public function testValidateInListAcceptsKey(): void
    {
        $allowed = ['freestyle' => 'Wolny', 'classical' => 'Klasyczny'];
        $this->assertSame('freestyle', $this->stub->validateInList('freestyle', $allowed, 'style', '/'));
        $this->assertSame('classical', $this->stub->validateInList('classical', $allowed, 'style', '/'));
    }

    public function testValidateInListAcceptsValueWhenFlat(): void
    {
        $allowed = ['option1', 'option2', 'option3'];
        $this->assertSame('option2', $this->stub->validateInList('option2', $allowed, 'foo', '/'));
    }

    public function testValidateDateAcceptsIsoFormat(): void
    {
        $this->assertSame('2026-05-05', $this->stub->validateDate('2026-05-05', 'date', '/'));
    }

    public function testValidateOptionalIntReturnsNullForEmpty(): void
    {
        $this->assertNull($this->stub->validateOptionalInt('', null, null, '/'));
        $this->assertNull($this->stub->validateOptionalInt(null, null, null, '/'));
        $this->assertSame(5, $this->stub->validateOptionalInt('5', 0, 100, '/'));
    }
}
