<?php

declare(strict_types=1);

namespace App\Helpers\Ksef;

/**
 * Generator XML w schemacie KSeF FA(2) — Ministerstwo Finansów PL.
 *
 * Phase 2 scope: czysty XML zgodny ze schematem FA(2) (xmlns
 * http://crd.gov.pl/wzor/2023/06/29/12648/). XAdES signing + wysyłka to
 * Phase 3 — ten generator zwraca surowy XML do podglądu / archiwum / sign'a.
 *
 * Referencja schematu: https://www.podatki.gov.pl/ksef/struktury-dokumentow-ksef/
 *   - Schemat FA(2): https://www.podatki.gov.pl/media/9264/schemat-fa2-1-0e-20231019.xsd
 *
 * Walidacja: po wygenerowaniu sprawdzamy parsable-ność przez simplexml.
 * Pełna walidacja XSD wymaga `libxml` + lokalnego pliku schematu — w produkcji
 * powinno być uruchamiane przez `xmllint --schema fa2.xsd` przed wysyłką.
 */
final class FA2XmlGenerator
{
    public const NS = 'http://crd.gov.pl/wzor/2023/06/29/12648/';
    public const NS_ETD = 'http://crd.elektroniczna-administracja.pl/xml/schematy/dziedzinowe/mf/2022/01/05/eD/DefinicjeTypy/';
    public const SYSTEM_NAME = 'ClubDesk';
    public const SYSTEM_VERSION = '2.0';

    /**
     * @param array<string,mixed>       $invoice Header (jak z club_invoices)
     * @param array<int,array<string,mixed>> $items   club_invoice_items
     * @return string XML
     */
    public static function generate(array $invoice, array $items): string
    {
        // Buduj XML przez DOMDocument — bezpieczne escape'owanie zawartości.
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElementNS(self::NS, 'Faktura');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:etd', self::NS_ETD);
        $dom->appendChild($root);

        $root->appendChild(self::buildNaglowek($dom, $invoice));
        $root->appendChild(self::buildPodmiot1($dom, $invoice));
        $root->appendChild(self::buildPodmiot2($dom, $invoice));
        $root->appendChild(self::buildFa($dom, $invoice, $items));
        $root->appendChild(self::buildStopka($dom));

        $xml = $dom->saveXML();
        if ($xml === false) {
            throw new \RuntimeException('FA(2) XML generation failed (saveXML returned false).');
        }

        // Walidacja parse'owalności (catch malformed output rather than hand it to KSeF)
        $prev = libxml_use_internal_errors(true);
        $ok   = simplexml_load_string($xml) !== false;
        $errs = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$ok) {
            $first = $errs[0] ?? null;
            $msg   = $first instanceof \LibXMLError ? trim($first->message) : 'unknown';
            throw new \RuntimeException('FA(2) XML failed simplexml validation: ' . $msg);
        }

        return $xml;
    }

    // ----------------------------------------------------------------- Naglowek

    private static function buildNaglowek(\DOMDocument $dom, array $inv): \DOMElement
    {
        $n = $dom->createElementNS(self::NS, 'Naglowek');

        $kf = $dom->createElementNS(self::NS, 'KodFormularza', 'FA');
        $kf->setAttribute('kodSystemowy', 'FA (2)');
        $kf->setAttribute('wersjaSchemy', '1-0E');
        $n->appendChild($kf);

        $n->appendChild($dom->createElementNS(self::NS, 'WariantFormularza', '2'));
        $n->appendChild($dom->createElementNS(
            self::NS,
            'DataWytworzeniaFa',
            gmdate('Y-m-d\TH:i:s\Z')
        ));
        $n->appendChild($dom->createElementNS(self::NS, 'SystemInfo', self::SYSTEM_NAME . ' ' . self::SYSTEM_VERSION));

        return $n;
    }

    // ----------------------------------------------------------------- Podmiot1

    private static function buildPodmiot1(\DOMDocument $dom, array $inv): \DOMElement
    {
        $p = $dom->createElementNS(self::NS, 'Podmiot1');

        $d = $dom->createElementNS(self::NS, 'DaneIdentyfikacyjne');
        $d->appendChild($dom->createElementNS(self::NS, 'NIP', self::cleanNip((string)($inv['seller_nip'] ?? ''))));
        $d->appendChild(self::txt($dom, 'Nazwa', (string)($inv['seller_name'] ?? 'Sprzedawca')));
        $p->appendChild($d);

        $p->appendChild(self::buildAdres($dom, (string)($inv['seller_address'] ?? '')));

        return $p;
    }

    // ----------------------------------------------------------------- Podmiot2

    private static function buildPodmiot2(\DOMDocument $dom, array $inv): \DOMElement
    {
        $p = $dom->createElementNS(self::NS, 'Podmiot2');

        $d = $dom->createElementNS(self::NS, 'DaneIdentyfikacyjne');
        $buyerNip = self::cleanNip((string)($inv['buyer_nip'] ?? ''));
        if ($buyerNip !== '' && strlen($buyerNip) === 10) {
            $d->appendChild($dom->createElementNS(self::NS, 'NIP', $buyerNip));
        } else {
            // B2C — brak NIP, sygnalizujemy "BRAK"
            $d->appendChild($dom->createElementNS(self::NS, 'BrakID', '1'));
        }
        $d->appendChild(self::txt($dom, 'Nazwa', (string)($inv['buyer_name'] ?? 'Nabywca')));
        $p->appendChild($d);

        $p->appendChild(self::buildAdres($dom, (string)($inv['buyer_address'] ?? '')));

        if (!empty($inv['buyer_email'])) {
            $p->appendChild(self::txt($dom, 'Email', (string)$inv['buyer_email']));
        }

        return $p;
    }

    // ----------------------------------------------------------------- Adres

    private static function buildAdres(\DOMDocument $dom, string $address): \DOMElement
    {
        $a = $dom->createElementNS(self::NS, 'Adres');
        $a->appendChild($dom->createElementNS(self::NS, 'KodKraju', 'PL'));
        // W schemacie FA(2) Adres ma AdresL1 + AdresL2 (linie wolnotekstowe)
        // gdy nie chcemy rozbijać na ulica/miasto/kod.
        $lines = preg_split('/\r?\n/', trim($address)) ?: [];
        $l1 = trim($lines[0] ?? '');
        $l2 = trim(implode(', ', array_slice($lines, 1)));
        $a->appendChild(self::txt($dom, 'AdresL1', $l1 !== '' ? $l1 : '-'));
        if ($l2 !== '') {
            $a->appendChild(self::txt($dom, 'AdresL2', $l2));
        }
        return $a;
    }

    // ----------------------------------------------------------------- Fa

    private static function buildFa(\DOMDocument $dom, array $inv, array $items): \DOMElement
    {
        $f = $dom->createElementNS(self::NS, 'Fa');

        $f->appendChild($dom->createElementNS(self::NS, 'KodWaluty', (string)($inv['currency'] ?? 'PLN')));
        $f->appendChild($dom->createElementNS(self::NS, 'P_1', (string)($inv['issue_date'] ?? date('Y-m-d'))));
        $f->appendChild($dom->createElementNS(self::NS, 'P_2', (string)($inv['invoice_number'] ?? 'DRAFT')));
        $f->appendChild($dom->createElementNS(self::NS, 'P_6', (string)($inv['sale_date'] ?? $inv['issue_date'] ?? date('Y-m-d'))));

        // Sumy łączne (P_13_1 — netto wg stawki 23, P_14_1 — VAT wg stawki 23 itp.)
        // dla uproszczenia: sumujemy per stawka VAT.
        $perRate = self::aggregateByVatRate($items);
        foreach ($perRate as $rate => $sums) {
            $rateKey = self::rateKey((string)$rate);
            if ($rateKey !== null) {
                $f->appendChild($dom->createElementNS(self::NS, 'P_13_' . $rateKey, self::money($sums['net'])));
                $f->appendChild($dom->createElementNS(self::NS, 'P_14_' . $rateKey, self::money($sums['vat'])));
            }
        }
        $f->appendChild($dom->createElementNS(self::NS, 'P_15', self::money((float)($inv['total_gross'] ?? 0))));

        // Rodzaj faktury (VAT/KOR/RR/PRO/PAR)
        $rodzaj = self::mapInvoiceType((string)($inv['invoice_type'] ?? 'VAT'));
        $f->appendChild($dom->createElementNS(self::NS, 'RodzajFaktury', $rodzaj));

        // Pozycje
        $position = 0;
        foreach ($items as $it) {
            $position++;
            $f->appendChild(self::buildFaWiersz($dom, $position, $it));
        }

        return $f;
    }

    // ----------------------------------------------------------------- FaWiersz

    private static function buildFaWiersz(\DOMDocument $dom, int $pos, array $it): \DOMElement
    {
        $w = $dom->createElementNS(self::NS, 'FaWiersz');
        $w->appendChild($dom->createElementNS(self::NS, 'NrWierszaFa', (string)$pos));
        $w->appendChild(self::txt($dom, 'P_7', (string)($it['description'] ?? '')));
        $w->appendChild(self::txt($dom, 'P_8A', (string)($it['unit'] ?? 'szt.')));
        $w->appendChild($dom->createElementNS(self::NS, 'P_8B', self::qty((float)($it['quantity'] ?? 1))));
        $w->appendChild($dom->createElementNS(self::NS, 'P_9A', self::money((float)($it['unit_price_net'] ?? 0))));
        $w->appendChild($dom->createElementNS(self::NS, 'P_11', self::money((float)($it['net_amount'] ?? 0))));

        $rate = (float)($it['vat_rate'] ?? 23);
        $w->appendChild($dom->createElementNS(self::NS, 'P_12', self::vatRateLabel($rate)));

        if (!empty($it['pkwiu'])) {
            $w->appendChild(self::txt($dom, 'PKWiU', (string)$it['pkwiu']));
        }
        if (!empty($it['gtu_code'])) {
            // GTU_xx jako boolean atrybuty per Fa, ale schema FA(2) zezwala
            // na flagi GTU_01..GTU_13 na poziomie faktury — uproszczone:
            // zostawiamy info w PKWiU jeśli nie ma osobnego pola.
        }
        return $w;
    }

    // ----------------------------------------------------------------- Stopka

    private static function buildStopka(\DOMDocument $dom): \DOMElement
    {
        $s = $dom->createElementNS(self::NS, 'Stopka');
        $i = $dom->createElementNS(self::NS, 'Informacje');
        $i->appendChild(self::txt($dom, 'StopkaFaktury', 'Faktura wygenerowana w systemie ' . self::SYSTEM_NAME . '.'));
        $s->appendChild($i);
        return $s;
    }

    // ----------------------------------------------------------------- Helpers

    private static function txt(\DOMDocument $dom, string $name, string $value): \DOMElement
    {
        // createElement + textContent zapewnia escape XML.
        $el = $dom->createElementNS(self::NS, $name);
        $el->appendChild($dom->createTextNode($value));
        return $el;
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<string,array{net:float,vat:float}>
     */
    private static function aggregateByVatRate(array $items): array
    {
        $out = [];
        foreach ($items as $it) {
            $rate = (string)(float)($it['vat_rate'] ?? 23);
            if (!isset($out[$rate])) {
                $out[$rate] = ['net' => 0.0, 'vat' => 0.0];
            }
            $out[$rate]['net'] += (float)($it['net_amount'] ?? 0);
            $out[$rate]['vat'] += (float)($it['vat_amount'] ?? 0);
        }
        return $out;
    }

    /** Mapuje stawkę VAT na klucz w sumarium FA(2) (P_13_X, P_14_X). */
    private static function rateKey(string $rate): ?string
    {
        return match ((string)(float)$rate) {
            '23'  => '1',
            '8'   => '2',
            '5'   => '3',
            '0'   => '4',
            '-1'  => '7', // ZW
            '-2'  => '8', // NP
            default => null,
        };
    }

    private static function vatRateLabel(float $rate): string
    {
        if ($rate < 0) {
            return $rate <= -2 ? 'np' : 'zw';
        }
        return rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.');
    }

    private static function mapInvoiceType(string $t): string
    {
        return match ($t) {
            'VAT_korekta' => 'KOR',
            'VAT_RR'      => 'RR',
            'proforma'    => 'PRO',
            'paragon'     => 'PAR',
            default       => 'VAT',
        };
    }

    private static function money(float $v): string
    {
        return number_format($v, 2, '.', '');
    }

    private static function qty(float $v): string
    {
        return rtrim(rtrim(number_format($v, 4, '.', ''), '0'), '.') ?: '0';
    }

    private static function cleanNip(string $nip): string
    {
        return preg_replace('/\D/', '', $nip) ?? '';
    }
}
