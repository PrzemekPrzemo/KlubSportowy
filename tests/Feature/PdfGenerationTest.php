<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\Pdf\MembershipCertificatePdf;
use App\Models\MemberModel;

/**
 * Feature: generowanie PDF zaświadczeń + tenant isolation w DocumentsController.
 *
 *  - MembershipCertificatePdf::generate() zwraca pełny HTML > 1KB
 *  - Brak crash przy minimalnych danych (graceful defaults)
 *  - Tenant isolation: MemberModel::findById() z klubu B nie zwraca member-a klubu A,
 *    co replikuje guard w DocumentsController::loadMember().
 */
class PdfGenerationTest extends FeatureTestCase
{
    public function testGenerateReturnsSubstantialHtml(): void
    {
        $html = MembershipCertificatePdf::generate([
            'club' => [
                'name' => 'Klub Sportowy Testowy',
                'address' => 'ul. Sportowa 1',
                'city' => 'Warszawa',
                'nip' => '1234567890',
            ],
            'member' => [
                'first_name' => 'Jan',
                'last_name' => 'Kowalski',
                'pesel' => '90010112345',
                'member_number' => 'M-001',
                'join_date' => '2022-01-15',
            ],
            'sport_label' => 'Judo',
            'paid_until' => '2025-12-31',
            'issued_place' => 'Warszawa',
            'club_header_html' => '<div>HDR</div>',
        ]);

        $this->assertIsString($html);
        $this->assertGreaterThan(1024, strlen($html), 'PDF HTML musi mieć > 1KB');
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('ZAŚWIADCZENIE', $html);
        $this->assertStringContainsString('Jan', $html);
        $this->assertStringContainsString('Kowalski', $html);
        $this->assertStringContainsString('Judo', $html);
    }

    public function testGenerateDoesNotCrashOnMinimalData(): void
    {
        // Tylko najbardziej minimalne dane — nie powinno wybuchać.
        $html = MembershipCertificatePdf::generate([
            'member' => ['first_name' => 'A', 'last_name' => 'B'],
        ]);

        $this->assertIsString($html);
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('ZAŚWIADCZENIE', $html);
        // Brak PESEL line w widoku.
        $this->assertStringNotContainsString('PESEL <strong></strong>', $html);
    }

    public function testHtmlIsEscapedAgainstXss(): void
    {
        $html = MembershipCertificatePdf::generate([
            'member' => [
                'first_name' => '<script>alert(1)</script>',
                'last_name' => '"; DROP TABLE',
            ],
            'club' => ['name' => '<img src=x>'],
        ]);

        // htmlspecialchars musi escape-ować raw <script> i atrybuty.
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testTenantIsolationOnLoadMember(): void
    {
        // Symuluje guard z DocumentsController::loadMember:
        //   $member = (new MemberModel())->findById($id);
        //   if ($member['club_id'] !== ClubContext::current()) → 403
        // Tutaj findById już respektuje scope, więc zwraca null = effective 403/404 w controllerze.
        $clubA = $this->createClub('PDF Tenant A');
        $clubB = $this->createClub('PDF Tenant B');

        $memberA = $this->createMember($clubA, 'Foreign', 'Member');

        $this->asClub($clubB);
        $row = (new MemberModel())->findById($memberA);
        $this->assertNull(
            $row,
            'DocumentsController::loadMember (via MemberModel scope) musi zwrócić null dla member z innego klubu'
        );
    }

    public function testLoadMemberReturnsRowForOwnClub(): void
    {
        $clubId = $this->createClub('PDF Own Club');
        $memberId = $this->createMember($clubId, 'Own', 'Member');

        $this->asClub($clubId);
        $row = (new MemberModel())->findById($memberId);
        $this->assertNotNull($row);
        $this->assertSame('Own', $row['first_name']);
    }
}
