<?php

namespace App\Controllers;

use App\Helpers\ClubContext;
use App\Helpers\PdfHelper;
use App\Models\MemberModel;

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
     * Generate member agreement PDF.
     */
    public function memberAgreement(string $memberId): void
    {
        $member = $this->loadMember((int)$memberId);
        $clubId = $this->currentClub();
        $clubHeader = PdfHelper::getClubHeader($clubId);

        $club = (new \App\Models\ClubModel())->findById($clubId);

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

        $club = (new \App\Models\ClubModel())->findById($clubId);

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

    private function loadMember(int $memberId): array
    {
        $member = (new MemberModel())->findById($memberId);
        if (!$member) {
            http_response_code(404);
            echo 'Nie znaleziono zawodnika.';
            exit;
        }
        return $member;
    }
}
