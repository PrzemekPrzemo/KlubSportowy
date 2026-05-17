<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Pdf\AchievementCertificatePdf;
use App\Helpers\Pdf\InvoicePdf;
use App\Helpers\Pdf\MembershipCertificatePdf;
use App\Helpers\Pdf\MembershipContractPdf;
use App\Helpers\Pdf\TournamentProtocolPdf;
use App\Helpers\Translator;
use PHPUnit\Framework\TestCase;

/**
 * Smoke testy dla generatorow PDF z parametrem $locale.
 *
 * Sprawdzaja, ze:
 *   - generate(...) zwraca string HTML (mpdf rendering tested osobno)
 *   - HTML zawiera EN labels gdy locale='en'
 *   - HTML zawiera PL labels gdy locale='pl'
 *   - render nie crashuje dla zadnego locale
 */
class PdfLocaleTest extends TestCase
{
    protected function setUp(): void
    {
        Translator::setLocale('pl');
    }

    public function test_invoice_pdf_generates_html_in_pl(): void
    {
        $data = $this->sampleInvoice();
        $html = InvoicePdf::generate($data, 'pl');
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('FAKTURA', $html);
        $this->assertStringContainsString('Sprzedawca', $html);
        $this->assertStringContainsString('Nabywca', $html);
        $this->assertStringContainsString('html lang="pl"', $html);
    }

    public function test_invoice_pdf_generates_html_in_en(): void
    {
        $data = $this->sampleInvoice();
        $html = InvoicePdf::generate($data, 'en');
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('INVOICE', $html);
        $this->assertStringContainsString('Seller', $html);
        $this->assertStringContainsString('Buyer', $html);
        $this->assertStringContainsString('Issue date', $html);
        $this->assertStringContainsString('html lang="en"', $html);
    }

    public function test_invoice_pdf_locale_does_not_leak_after_generate(): void
    {
        Translator::setLocale('pl');
        InvoicePdf::generate($this->sampleInvoice(), 'en');
        // Po renderze locale 'pl' powinien byc przywrocony (Translator::withLocale)
        $this->assertSame('pl', Translator::getLocale());
    }

    public function test_membership_certificate_pdf_locale(): void
    {
        $data = $this->sampleMember();
        $htmlPl = MembershipCertificatePdf::generate($data, 'pl');
        $htmlEn = MembershipCertificatePdf::generate($data, 'en');

        $this->assertStringContainsString('ZAŚWIADCZENIE', $htmlPl);
        $this->assertStringContainsString('CERTIFICATE', $htmlEn);
        $this->assertStringContainsString('Member since', $htmlEn);
        $this->assertStringContainsString('Członek od', $htmlPl);
    }

    public function test_tournament_protocol_pdf_locale(): void
    {
        $data = $this->sampleTournament();
        $htmlPl = TournamentProtocolPdf::generate($data, 'pl');
        $htmlEn = TournamentProtocolPdf::generate($data, 'en');

        $this->assertStringContainsString('Protokół turnieju', $htmlPl);
        $this->assertStringContainsString('Tournament protocol', $htmlEn);
        $this->assertStringContainsString('Player', $htmlEn);
        $this->assertStringContainsString('Zawodnik', $htmlPl);
    }

    public function test_membership_contract_pdf_locale(): void
    {
        $data = $this->sampleContract();
        $htmlPl = MembershipContractPdf::generate($data, 'pl');
        $htmlEn = MembershipContractPdf::generate($data, 'en');

        $this->assertStringContainsString('UMOWA CZŁONKOWSKA', $htmlPl);
        $this->assertStringContainsString('MEMBERSHIP AGREEMENT', $htmlEn);
        $this->assertStringContainsString('Subject of the agreement', $htmlEn);
        $this->assertStringContainsString('Przedmiot umowy', $htmlPl);
    }

    public function test_achievement_certificate_pdf_locale(): void
    {
        $data = [
            'member'       => ['first_name' => 'Jan', 'last_name' => 'Kowalski', 'member_number' => '001'],
            'achievement'  => 'Test achievement',
            'issued_at'    => '2025-01-15',
            'issued_place' => 'Warszawa',
            'club_name'    => 'Test Club',
        ];
        $htmlPl = AchievementCertificatePdf::generate($data, 'pl');
        $htmlEn = AchievementCertificatePdf::generate($data, 'en');

        $this->assertStringContainsString('Niniejszym potwierdzamy', $htmlPl);
        $this->assertStringContainsString('We hereby confirm', $htmlEn);
        $this->assertStringContainsString('Head coach', $htmlEn);
    }

    public function test_invoice_pdf_accepts_locale_param(): void
    {
        $ref = new \ReflectionMethod(InvoicePdf::class, 'generate');
        $params = $ref->getParameters();
        $names = array_map(fn(\ReflectionParameter $p) => $p->getName(), $params);
        $this->assertContains('locale', $names);
    }

    public function test_all_pdf_generators_accept_locale_param(): void
    {
        $classes = [
            InvoicePdf::class,
            MembershipCertificatePdf::class,
            MembershipContractPdf::class,
            TournamentProtocolPdf::class,
            AchievementCertificatePdf::class,
            \App\Helpers\BeltCertificatePdf::class,
        ];
        foreach ($classes as $cls) {
            $ref = new \ReflectionMethod($cls, 'generate');
            $names = array_map(fn(\ReflectionParameter $p) => $p->getName(), $ref->getParameters());
            $this->assertContains('locale', $names, "$cls::generate must accept \$locale param");
        }
    }

    private function sampleInvoice(): array
    {
        return [
            'seller' => ['name' => 'KS Test', 'address' => 'ul. Sportowa 1', 'city' => '00-001 Warszawa', 'nip' => '1234567890'],
            'buyer'  => ['name' => 'Jan Kowalski', 'address' => 'ul. Klienta 5', 'city' => '00-002 Warszawa'],
            'invoice' => [
                'number'    => 'FV/2025/001',
                'issue_date' => '2025-01-15',
                'sale_date'  => '2025-01-15',
                'due_date'   => '2025-01-29',
                'status'     => 'issued',
                'payment_method' => 'przelew',
            ],
            'items' => [
                ['name' => 'Składka klubowa', 'qty' => 1, 'unit' => 'szt.', 'net_price' => 100.0, 'vat_rate' => 0, 'net_total' => 100.0, 'gross_total' => 100.0],
            ],
            'totals' => ['net' => 100.0, 'vat' => 0.0, 'gross' => 100.0],
        ];
    }

    private function sampleMember(): array
    {
        return [
            'club' => ['name' => 'KS Test', 'city' => 'Warszawa', 'address' => 'ul. Sportowa 1'],
            'member' => [
                'first_name' => 'Jan',
                'last_name'  => 'Kowalski',
                'pesel'      => '90010112345',
                'member_number' => '001',
                'join_date'  => '2024-09-01',
            ],
            'sport_label' => 'Judo',
            'paid_until'  => '2025-12-31',
            'issued_at'   => '2025-01-15',
            'issued_place' => 'Warszawa',
        ];
    }

    private function sampleTournament(): array
    {
        return [
            'tournament' => ['id' => 1, 'name' => 'Puchar Klubu', 'sport_key' => 'judo', 'date_start' => '2025-01-10', 'status' => 'finished'],
            'sport' => ['name' => 'Judo'],
            'participants' => [
                ['first_name' => 'Jan', 'last_name' => 'Kowalski', 'member_number' => '001', 'place' => 1, 'score' => 12],
                ['first_name' => 'Adam', 'last_name' => 'Nowak',    'member_number' => '002', 'place' => 2, 'score' => 8],
            ],
            'matches' => [],
        ];
    }

    private function sampleContract(): array
    {
        return [
            'club' => ['name' => 'KS Test', 'city' => 'Warszawa', 'address' => 'ul. Sportowa 1', 'nip' => '1234567890', 'regon' => '123456789'],
            'member' => [
                'first_name' => 'Jan',
                'last_name'  => 'Kowalski',
                'pesel'      => '90010112345',
                'birth_date' => '1990-01-01',
                'address_street' => 'ul. Klienta 5',
                'address_city'   => 'Warszawa',
                'address_postal' => '00-002',
                'email' => 'jan@test',
                'phone' => '+48 123 456 789',
                'member_number' => '001',
            ],
            'sport_label' => 'Judo',
            'fee' => ['amount' => 100.0, 'frequency' => 'miesięcznie', 'method' => 'przelew bankowy'],
            'signed_at'   => '2025-01-15',
            'signed_place' => 'Warszawa',
        ];
    }
}
