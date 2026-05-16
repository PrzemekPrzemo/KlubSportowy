<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Ksef\FA2XmlGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for KSeF FA(2) XML generator.
 *
 * Pure function — no DB / network dependencies. Sprawdza:
 *   - poprawność namespace + root element
 *   - obecność wymaganych elementów FA(2) (P_1, P_2, P_6, P_15, Naglowek)
 *   - parsability XML (simplexml_load_string)
 *   - obsługa B2B (z NIP) vs B2C (BrakID)
 *   - agregacja sum per stawka VAT (P_13_1, P_14_1)
 *   - eskape'owanie znaków specjalnych w treści
 */
class FA2XmlGeneratorTest extends TestCase
{
    private function sampleInvoice(?string $buyerNip = '1234563218'): array
    {
        return [
            'id' => 1,
            'invoice_number' => 'FV/1/2026',
            'invoice_type'   => 'VAT',
            'seller_name'    => 'AZS Warszawa',
            'seller_nip'     => '5252289373',
            'seller_address' => "ul. Sportowa 5\n00-001 Warszawa",
            'buyer_name'     => 'Jan Kowalski',
            'buyer_nip'      => $buyerNip,
            'buyer_address'  => "ul. Polna 3\n00-010 Warszawa",
            'buyer_email'    => 'jan@example.com',
            'issue_date'     => '2026-05-16',
            'sale_date'      => '2026-05-15',
            'currency'       => 'PLN',
            'total_net'      => 162.60,
            'total_vat'      => 37.40,
            'total_gross'    => 200.00,
        ];
    }

    private function sampleItems(): array
    {
        return [
            ['description' => 'Skladka 2026', 'quantity' => 1, 'unit' => 'szt.',
             'unit_price_net' => 81.30, 'vat_rate' => 23,
             'net_amount' => 81.30, 'vat_amount' => 18.70, 'gross_amount' => 100.00],
            ['description' => 'Licencja', 'quantity' => 1, 'unit' => 'szt.',
             'unit_price_net' => 81.30, 'vat_rate' => 23,
             'net_amount' => 81.30, 'vat_amount' => 18.70, 'gross_amount' => 100.00],
        ];
    }

    public function testGeneratesValidXml(): void
    {
        $xml = FA2XmlGenerator::generate($this->sampleInvoice(), $this->sampleItems());
        $this->assertNotEmpty($xml);
        $this->assertStringStartsWith('<?xml version="1.0"', $xml);

        $sx = simplexml_load_string($xml);
        $this->assertNotFalse($sx, 'XML must be parsable');
        $this->assertSame('Faktura', $sx->getName());

        $ns = $sx->getNamespaces();
        $this->assertSame(FA2XmlGenerator::NS, $ns[''] ?? null);
    }

    public function testContainsRequiredHeaderElements(): void
    {
        $xml = FA2XmlGenerator::generate($this->sampleInvoice(), $this->sampleItems());
        $this->assertStringContainsString('<Naglowek>', $xml);
        $this->assertStringContainsString('<KodFormularza kodSystemowy="FA (2)" wersjaSchemy="1-0E">FA</KodFormularza>', $xml);
        $this->assertStringContainsString('<WariantFormularza>2</WariantFormularza>', $xml);
        $this->assertStringContainsString('<DataWytworzeniaFa>', $xml);
    }

    public function testContainsInvoiceFields(): void
    {
        $xml = FA2XmlGenerator::generate($this->sampleInvoice(), $this->sampleItems());
        $this->assertStringContainsString('<P_1>2026-05-16</P_1>', $xml);  // issue date
        $this->assertStringContainsString('<P_2>FV/1/2026</P_2>', $xml);  // number
        $this->assertStringContainsString('<P_6>2026-05-15</P_6>', $xml); // sale date
        $this->assertStringContainsString('<P_15>200.00</P_15>', $xml);   // total gross
        $this->assertStringContainsString('<RodzajFaktury>VAT</RodzajFaktury>', $xml);
    }

    public function testAggregatesVatByRate(): void
    {
        $xml = FA2XmlGenerator::generate($this->sampleInvoice(), $this->sampleItems());
        // 2 items 23% — suma netto 162.60, VAT 37.40 → P_13_1 + P_14_1
        $this->assertStringContainsString('<P_13_1>162.60</P_13_1>', $xml);
        $this->assertStringContainsString('<P_14_1>37.40</P_14_1>', $xml);
    }

    public function testRendersAllFaWierszLines(): void
    {
        $xml = FA2XmlGenerator::generate($this->sampleInvoice(), $this->sampleItems());
        $sx  = simplexml_load_string($xml);
        $this->assertNotFalse($sx);
        $rows = $sx->Fa->FaWiersz;
        $this->assertCount(2, $rows);
        $this->assertSame('1', (string)$rows[0]->NrWierszaFa);
        $this->assertSame('Skladka 2026', (string)$rows[0]->P_7);
        $this->assertSame('2', (string)$rows[1]->NrWierszaFa);
    }

    public function testB2BIncludesNip(): void
    {
        $xml = FA2XmlGenerator::generate($this->sampleInvoice('1234563218'), $this->sampleItems());
        $sx  = simplexml_load_string($xml);
        $this->assertNotFalse($sx);
        $this->assertSame('1234563218', (string)$sx->Podmiot2->DaneIdentyfikacyjne->NIP);
    }

    public function testB2CUsesBrakID(): void
    {
        $xml = FA2XmlGenerator::generate($this->sampleInvoice(null), $this->sampleItems());
        $this->assertStringContainsString('<BrakID>1</BrakID>', $xml);
        $this->assertStringNotContainsString('<NIP></NIP>', $xml);
    }

    public function testEscapesSpecialCharsInDescription(): void
    {
        $items = [[
            'description' => 'A & B <test> "quoted"',
            'quantity' => 1, 'unit' => 'szt.',
            'unit_price_net' => 100.00, 'vat_rate' => 23,
            'net_amount' => 100.00, 'vat_amount' => 23.00, 'gross_amount' => 123.00,
        ]];
        $xml = FA2XmlGenerator::generate($this->sampleInvoice(), $items);
        $this->assertStringNotContainsString('<test>', $xml);
        $this->assertStringContainsString('&amp;', $xml);
        $this->assertStringContainsString('&lt;test&gt;', $xml);

        $sx = simplexml_load_string($xml);
        $this->assertNotFalse($sx);
    }

    public function testSpecialVatRates(): void
    {
        $items = [
            // ZW (zwolniona)
            ['description' => 'Usługa zwolniona', 'quantity' => 1, 'unit' => 'szt.',
             'unit_price_net' => 50.00, 'vat_rate' => -1,
             'net_amount' => 50.00, 'vat_amount' => 0.0, 'gross_amount' => 50.00],
            // NP (niepodlegająca)
            ['description' => 'Usługa niepodlegająca', 'quantity' => 1, 'unit' => 'szt.',
             'unit_price_net' => 30.00, 'vat_rate' => -2,
             'net_amount' => 30.00, 'vat_amount' => 0.0, 'gross_amount' => 30.00],
        ];
        $xml = FA2XmlGenerator::generate($this->sampleInvoice(), $items);
        $this->assertStringContainsString('<P_12>zw</P_12>', $xml);
        $this->assertStringContainsString('<P_12>np</P_12>', $xml);
    }
}
