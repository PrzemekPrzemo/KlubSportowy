<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Lang parity test:
 *   - lang/pl/messages.php i lang/en/messages.php maja taka sama liczbe kluczy
 *   - zbior kluczy jest identyczny (zaden klucz PL bez tlumaczenia EN i vice versa)
 *   - zadna wartosc nie jest pusta
 *
 * Cel: zapobiec sytuacji, w ktorej __()  zwraca raw key dla EN locale,
 *       bo ktos zapomnial dodac tlumaczenia.
 */
class I18nKeyParityTest extends TestCase
{
    private array $pl;
    private array $en;

    protected function setUp(): void
    {
        $this->pl = require ROOT_PATH . '/lang/pl/messages.php';
        $this->en = require ROOT_PATH . '/lang/en/messages.php';
    }

    public function test_pl_and_en_have_same_key_count(): void
    {
        $this->assertSame(
            count($this->pl),
            count($this->en),
            'PL and EN message files must have the same number of keys. ' .
            'PL=' . count($this->pl) . ' EN=' . count($this->en)
        );
    }

    public function test_pl_and_en_have_identical_key_sets(): void
    {
        $plOnly = array_diff_key($this->pl, $this->en);
        $enOnly = array_diff_key($this->en, $this->pl);

        $this->assertSame(
            [],
            array_keys($plOnly),
            'Keys present in PL but missing in EN: ' . implode(', ', array_keys($plOnly))
        );
        $this->assertSame(
            [],
            array_keys($enOnly),
            'Keys present in EN but missing in PL: ' . implode(', ', array_keys($enOnly))
        );
    }

    public function test_no_empty_values_in_pl(): void
    {
        $empty = array_filter($this->pl, fn($v) => $v === '' || $v === null);
        $this->assertSame([], array_keys($empty),
            'PL keys with empty value: ' . implode(', ', array_keys($empty)));
    }

    public function test_no_empty_values_in_en(): void
    {
        $empty = array_filter($this->en, fn($v) => $v === '' || $v === null);
        $this->assertSame([], array_keys($empty),
            'EN keys with empty value: ' . implode(', ', array_keys($empty)));
    }

    public function test_pdf_keys_present_in_both_locales(): void
    {
        // Sanity check, ze migracja PDF i18n nie zostala czesciowo cofnieta.
        $requiredKeys = [
            'pdf.invoice.title',
            'pdf.invoice.label.seller',
            'pdf.invoice.label.buyer',
            'pdf.belt_cert.intro',
            'pdf.member_cert.title',
            'pdf.tournament_protocol.title',
            'pdf.contract.title',
            'pdf.achievement.title',
        ];
        foreach ($requiredKeys as $k) {
            $this->assertArrayHasKey($k, $this->pl, "PL missing PDF key: $k");
            $this->assertArrayHasKey($k, $this->en, "EN missing PDF key: $k");
        }
    }
}
