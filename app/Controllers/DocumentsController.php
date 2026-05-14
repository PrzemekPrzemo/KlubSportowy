<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Pdf\AchievementCertificatePdf;
use App\Helpers\Pdf\MembershipCertificatePdf;
use App\Helpers\Pdf\MembershipContractPdf;
use App\Helpers\PdfHelper;
use App\Models\ClubCustomizationModel;
use App\Models\ClubModel;
use App\Models\MemberModel;
use App\Models\PaymentDueModel;

class DocumentsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    /**
     * Document templates index page.
     */
    public function index(): void
    {
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $this->render('documents/index', [
            'title'   => 'Dokumenty',
            'members' => $members,
        ]);
    }

    /**
     * Generate member agreement PDF (legacy view-based template).
     */
    public function memberAgreement(string $memberId): void
    {
        $member = $this->loadMember((int)$memberId);
        $clubId = $this->currentClub();
        $clubHeader = PdfHelper::getClubHeader($clubId);

        $club = (new ClubModel())->findById($clubId);

        ob_start();
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        include ROOT_PATH . '/app/Views/pdf/member_agreement.php';
        $html = ob_get_clean();

        PdfHelper::renderToPdf($html, 'umowa-czlonkowska-' . $member['id'] . '.pdf');
    }

    /**
     * Generate training consent form PDF.
     */
    public function trainingConsent(string $memberId): void
    {
        $member = $this->loadMember((int)$memberId);
        $clubId = $this->currentClub();
        $clubHeader = PdfHelper::getClubHeader($clubId);

        $club = (new ClubModel())->findById($clubId);

        ob_start();
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        include ROOT_PATH . '/app/Views/pdf/training_consent.php';
        $html = ob_get_clean();

        PdfHelper::renderToPdf($html, 'zgoda-treningowa-' . $member['id'] . '.pdf');
    }

    /**
     * Generate liability waiver PDF.
     */
    public function liabilityWaiver(string $memberId): void
    {
        $member = $this->loadMember((int)$memberId);
        $clubId = $this->currentClub();
        $clubHeader = PdfHelper::getClubHeader($clubId);

        $club = (new \App\Models\ClubModel())->findById($clubId);

        ob_start();
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        include ROOT_PATH . '/app/Views/pdf/liability_waiver.php';
        $html = ob_get_clean();

        PdfHelper::renderToPdf($html, 'oswiadczenie-odpowiedzialnosci-' . $member['id'] . '.pdf');
    }

    /**
     * Zaświadczenie o członkostwie (nowy generator z app/Helpers/Pdf).
     */
    public function membershipCertificate(string $memberId): void
    {
        $this->requireRole(['zarzad', 'trener', 'admin']);

        $member = $this->loadMember((int)$memberId);
        $clubId = $this->currentClub();
        $club   = (new ClubModel())->findById($clubId) ?? [];

        // Pierwsza sekcja sportu — jeśli istnieje
        $sportLabel = '—';
        if (!empty($member['sports']) && is_array($member['sports'])) {
            $first = $member['sports'][0] ?? null;
            if ($first) {
                $sportLabel = (string)($first['sport_name'] ?? $first['sport_key'] ?? '—');
            }
        }

        // Składki opłacone do — najwcześniejszy due w statusie 'paid'
        $paidUntil = null;
        try {
            $dues = (new PaymentDueModel())->forMember((int)$member['id'], 'paid');
            if (!empty($dues)) {
                $latest = max(array_column($dues, 'period_end') ?: [0]);
                $paidUntil = is_string($latest) ? $latest : null;
            }
        } catch (\Throwable) {}

        MembershipCertificatePdf::download([
            'club'             => $club,
            'member'           => $member,
            'sport_label'      => $sportLabel,
            'paid_until'       => $paidUntil,
            'issued_at'        => date('d.m.Y'),
            'issued_place'     => $club['city'] ?? '',
            'club_header_html' => PdfHelper::getClubHeader($clubId),
        ]);
    }

    /**
     * Pełna umowa członkowska (nowy szablon).
     */
    public function membershipContract(string $memberId): void
    {
        $this->requireRole(['zarzad', 'trener', 'admin']);

        $member = $this->loadMember((int)$memberId);
        $clubId = $this->currentClub();
        $club   = (new ClubModel())->findById($clubId) ?? [];

        $sportLabel = '—';
        if (!empty($member['sports']) && is_array($member['sports'])) {
            $first = $member['sports'][0] ?? null;
            if ($first) {
                $sportLabel = (string)($first['sport_name'] ?? $first['sport_key'] ?? '—');
            }
        }

        $feeAmount = isset($_GET['fee']) ? (float)$_GET['fee'] : 100.0;
        $feeFreq   = (string)($_GET['freq']   ?? 'miesięcznie');
        $feeMethod = (string)($_GET['method'] ?? 'przelew bankowy');

        MembershipContractPdf::download([
            'club'        => $club,
            'member'      => $member,
            'sport_label' => $sportLabel,
            'fee'         => [
                'amount'    => $feeAmount,
                'frequency' => $feeFreq,
                'method'    => $feeMethod,
            ],
            'duration'         => (string)($_GET['duration'] ?? 'czas nieokreślony'),
            'guardian'         => null,
            'custom_terms'     => (string)($_GET['terms'] ?? ''),
            'signed_at'        => date('d.m.Y'),
            'signed_place'     => $club['city'] ?? '',
            'club_header_html' => PdfHelper::getClubHeader($clubId),
        ]);
    }

    /**
     * Certyfikat osiągnięcia (np. ukończenie kursu, miejsce w turnieju).
     * Treść osiągnięcia w query param ?achievement=...
     */
    public function achievementCertificate(string $memberId): void
    {
        $this->requireRole(['zarzad', 'trener', 'admin']);

        $member = $this->loadMember((int)$memberId);
        $clubId = $this->currentClub();
        $club   = (new ClubModel())->findById($clubId) ?? [];
        $cust   = (new ClubCustomizationModel())->findForClub($clubId) ?? [];

        $achievement = trim((string)($_GET['achievement'] ?? 'osiągnięcie sportowe'));

        AchievementCertificatePdf::download([
            'member'         => $member,
            'achievement'    => $achievement,
            'issued_at'      => date('d.m.Y'),
            'issued_place'   => $club['city'] ?? '',
            'club_name'      => $club['name']  ?? 'Klub Sportowy',
            'president_name' => (string)($_GET['president'] ?? 'Prezes Zarządu'),
            'coach_name'     => (string)($_GET['coach']     ?? 'Trener prowadzący'),
            'accent_color'   => $cust['primary_color'] ?? '#0d6efd',
        ]);
    }

    /**
     * Sprawdza dostęp do danych członka: musi należeć do aktywnego klubu,
     * a użytkownik musi być w odpowiedniej roli LUB tym konkretnym członkiem.
     */
    private function loadMember(int $memberId): array
    {
        $member = (new MemberModel())->findById($memberId);
        if (!$member) {
            http_response_code(404);
            echo 'Nie znaleziono zawodnika.';
            exit;
        }

        // Tenant isolation — member musi należeć do aktywnego klubu
        $clubId = ClubContext::current();
        if ($clubId !== null && (int)($member['club_id'] ?? 0) !== (int)$clubId) {
            http_response_code(403);
            echo 'Brak dostępu do danych z innego klubu.';
            exit;
        }

        return $member;
    }
}
