<?php

namespace Tests\Unit;

use App\Helpers\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testRequiredPasses(): void
    {
        $v = Validator::make(['name' => 'Jan'], ['name' => 'required']);
        $this->assertTrue($v->passes());
        $this->assertEquals('Jan', $v->validated()['name']);
    }

    public function testRequiredFails(): void
    {
        $v = Validator::make(['name' => ''], ['name' => 'required']);
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('name', $v->errors());
    }

    public function testEmailValid(): void
    {
        $v = Validator::make(['e' => 'jan@test.pl'], ['e' => 'required|email']);
        $this->assertTrue($v->passes());
    }

    public function testEmailInvalid(): void
    {
        $v = Validator::make(['e' => 'not-email'], ['e' => 'required|email']);
        $this->assertTrue($v->fails());
    }

    public function testMinMaxLength(): void
    {
        $v = Validator::make(['n' => 'ab'], ['n' => 'required|min:3']);
        $this->assertTrue($v->fails());

        $v = Validator::make(['n' => 'abc'], ['n' => 'required|min:3|max:5']);
        $this->assertTrue($v->passes());

        $v = Validator::make(['n' => 'abcdef'], ['n' => 'required|max:5']);
        $this->assertTrue($v->fails());
    }

    public function testNumeric(): void
    {
        $v = Validator::make(['a' => '123.45'], ['a' => 'required|numeric']);
        $this->assertTrue($v->passes());

        $v = Validator::make(['a' => 'abc'], ['a' => 'required|numeric']);
        $this->assertTrue($v->fails());
    }

    public function testMinMaxValue(): void
    {
        $v = Validator::make(['a' => '10'], ['a' => 'required|numeric|min_value:5|max_value:20']);
        $this->assertTrue($v->passes());

        $v = Validator::make(['a' => '3'], ['a' => 'required|numeric|min_value:5']);
        $this->assertTrue($v->fails());
    }

    public function testDate(): void
    {
        $v = Validator::make(['d' => '2024-01-15'], ['d' => 'required|date']);
        $this->assertTrue($v->passes());

        $v = Validator::make(['d' => '15-01-2024'], ['d' => 'required|date']);
        $this->assertTrue($v->fails());
    }

    public function testIn(): void
    {
        $v = Validator::make(['s' => 'aktywny'], ['s' => 'required|in:aktywny,zawieszony,wykreslony']);
        $this->assertTrue($v->passes());

        $v = Validator::make(['s' => 'deleted'], ['s' => 'required|in:aktywny,zawieszony']);
        $this->assertTrue($v->fails());
    }

    public function testPeselValid(): void
    {
        // 44051401359 — prawidłowy PESEL (checksumowany)
        $v = Validator::make(['p' => '44051401359'], ['p' => 'pesel']);
        $this->assertTrue($v->passes());
    }

    public function testPeselInvalid(): void
    {
        $v = Validator::make(['p' => '12345678901'], ['p' => 'required|pesel']);
        $this->assertTrue($v->fails());

        $v = Validator::make(['p' => 'abc'], ['p' => 'required|pesel']);
        $this->assertTrue($v->fails());
    }

    public function testPeselOptionalEmpty(): void
    {
        $v = Validator::make(['p' => ''], ['p' => 'pesel']);
        $this->assertTrue($v->passes());
        $this->assertNull($v->validated()['p']);
    }

    public function testPhone(): void
    {
        $v = Validator::make(['p' => '+48 123 456 789'], ['p' => 'phone']);
        $this->assertTrue($v->passes());

        $v = Validator::make(['p' => '123456789'], ['p' => 'phone']);
        $this->assertTrue($v->passes());
    }

    public function testOptionalFieldSkippedWhenEmpty(): void
    {
        $v = Validator::make(['email' => ''], ['email' => 'email']);
        $this->assertTrue($v->passes());
        $this->assertNull($v->validated()['email']);
    }

    public function testValidatedReturnsOnlyRuleKeys(): void
    {
        $v = Validator::make(
            ['name' => 'Jan', 'extra' => 'ignored'],
            ['name' => 'required']
        );
        $this->assertTrue($v->passes());
        $this->assertArrayHasKey('name', $v->validated());
        $this->assertArrayNotHasKey('extra', $v->validated());
    }

    public function testMultipleErrors(): void
    {
        $v = Validator::make(
            ['name' => '', 'email' => 'bad'],
            ['name' => 'required', 'email' => 'required|email']
        );
        $this->assertTrue($v->fails());
        $this->assertCount(2, $v->errors());
        $this->assertNotNull($v->firstError());
    }

    // ------------------------------------------------------------------
    // Additional edge-case tests (BLOK 3)
    // ------------------------------------------------------------------

    public function testUrlValid(): void
    {
        $v = Validator::make(['u' => 'https://example.com'], ['u' => 'required|url']);
        $this->assertTrue($v->passes());
    }

    public function testUrlInvalid(): void
    {
        $v = Validator::make(['u' => 'not a url'], ['u' => 'required|url']);
        $this->assertTrue($v->fails());
    }

    public function testIntegerValid(): void
    {
        $v = Validator::make(['n' => '42'], ['n' => 'required|integer']);
        $this->assertTrue($v->passes());
    }

    public function testIntegerInvalidFloat(): void
    {
        $v = Validator::make(['n' => '3.14'], ['n' => 'required|integer']);
        $this->assertTrue($v->fails());
    }

    public function testConfirmedPasses(): void
    {
        $v = Validator::make(
            ['password' => 'secret123', 'password_confirmation' => 'secret123'],
            ['password' => 'required|confirmed']
        );
        $this->assertTrue($v->passes());
    }

    public function testConfirmedFails(): void
    {
        $v = Validator::make(
            ['password' => 'secret123', 'password_confirmation' => 'different'],
            ['password' => 'required|confirmed']
        );
        $this->assertTrue($v->fails());
    }

    public function testAllErrorsFlat(): void
    {
        $v = Validator::make(
            ['a' => '', 'b' => ''],
            ['a' => 'required', 'b' => 'required']
        );
        $this->assertTrue($v->fails());
        $all = $v->allErrors();
        $this->assertCount(2, $all);
        $this->assertIsString($all[0]);
    }

    public function testWhitespaceTrimmed(): void
    {
        $v = Validator::make(['name' => '  Jan  '], ['name' => 'required']);
        $this->assertTrue($v->passes());
        $this->assertEquals('Jan', $v->validated()['name']);
    }

    public function testMaxValuePasses(): void
    {
        $v = Validator::make(['a' => '100'], ['a' => 'required|numeric|max_value:200']);
        $this->assertTrue($v->passes());
    }

    public function testMaxValueFails(): void
    {
        $v = Validator::make(['a' => '300'], ['a' => 'required|numeric|max_value:200']);
        $this->assertTrue($v->fails());
    }

    public function testPhoneInvalidChars(): void
    {
        $v = Validator::make(['p' => 'abc-phone'], ['p' => 'required|phone']);
        $this->assertTrue($v->fails());
    }

    public function testMissingFieldWithRequiredFails(): void
    {
        $v = Validator::make([], ['name' => 'required']);
        $this->assertTrue($v->fails());
    }

    public function testMissingFieldWithoutRequiredPasses(): void
    {
        $v = Validator::make([], ['nickname' => 'min:3']);
        $this->assertTrue($v->passes());
        $this->assertNull($v->validated()['nickname']);
    }
}
