<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\CsvExporter;
use App\Helpers\Feature;
use App\Helpers\PdfHelper;
use App\Helpers\View;
use App\Models\ClubCustomizationModel;
use App\Models\EventModel;
use App\Models\MemberModel;
use App\Models\MemberSportModel;
use App\Models\PaymentModel;

class ReportsController extends BaseController
{
    /**
     * Reports dashboard — card grid with available report types.
     */
    public function index(): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $this->render('reports/index', [
            'title' => 'Raporty',
        ]);
    }

    /**
     * PDF list of all club members with sports and status.
     */
    public function membersPdf(): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        // Feature flag guard — kluby na planie bez `pdf_export` dostają 403.
        // Master Admin może nadać override per klub (np. trial Pro feature).
        Feature::requireEnabled('pdf_export');

        $clubId  = $this->currentClub();
        $members = $this->getMembersWithSports($clubId);
        $header  = PdfHelper::getClubHeader($clubId);
        $footer  = PdfHelper::getSystemFooter();

        $html = View::partial('pdf/members_list', [
            'clubHeader'   => $header,
            'systemFooter' => $footer,
            'members'    => $members,
            'generated'  => date('d.m.Y H:i'),
        ]);

        PdfHelper::renderToPdf($html, 'zawodnicy_' . date('Y-m-d') . '.pdf', 'L');
    }

    /**
     * CSV export of members.
     */
    public function membersCsv(): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $clubId  = $this->currentClub();
        $members = $this->getMembersWithSports($clubId);

        $headers = ['Lp.', 'Nr członkowski', 'Imię', 'Nazwisko', 'E-mail', 'Telefon', 'Data urodzenia', 'Status', 'Sekcje sportowe', 'Data dołączenia'];

        $rows = [];
        $lp   = 0;
        foreach ($members as $m) {
            $lp++;
            $sports = array_map(fn($s) => $s['sport_name'], $m['sports'] ?? []);
            $rows[] = [
                $lp,
                $m['member_number'] ?? '',
                $m['first_name'] ?? '',
                $m['last_name'] ?? '',
                $m['email'] ?? '',
                $m['phone'] ?? '',
                $m['birth_date'] ?? '',
                $m['status'] ?? '',
                implode(', ', $sports),
                $m['join_date'] ?? '',
            ];
        }

        CsvExporter::download('zawodnicy_' . date('Y-m-d') . '.csv', $headers, $rows);
    }

    /**
     * PDF financial report for a year (from GET param).
     */
    public function financesPdf(): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $clubId = $this->currentClub();
        $year   = (int)($_GET['year'] ?? date('Y'));
        if ($year < 2000 || $year > 2099) {
            $year = (int)date('Y');
        }

        $paymentModel = new PaymentModel();
        $result       = $paymentModel->listForClub(null, $year, 1, 10000);
        $payments     = $result['data'] ?? [];

        $totalAmount = 0.0;
        foreach ($payments as $p) {
            $totalAmount += (float)($p['amount'] ?? 0);
        }

        $header = PdfHelper::getClubHeader($clubId);
        $footer  = PdfHelper::getSystemFooter();

        $html = View::partial('pdf/finances', [
            'clubHeader'   => $header,
            'systemFooter' => $footer,
            'payments'    => $payments,
            'year'        => $year,
            'totalAmount' => $totalAmount,
            'generated'   => date('d.m.Y H:i'),
        ]);

        PdfHelper::renderToPdf($html, 'finanse_' . $year . '.pdf', 'L');
    }

    /**
     * CSV export of payments.
     */
    public function financesCsv(): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $year = (int)($_GET['year'] ?? date('Y'));
        if ($year < 2000 || $year > 2099) {
            $year = (int)date('Y');
        }

        $paymentModel = new PaymentModel();
        $result       = $paymentModel->listForClub(null, $year, 1, 10000);
        $payments     = $result['data'] ?? [];

        $headers = ['Lp.', 'Data płatności', 'Zawodnik', 'Nr członkowski', 'Typ opłaty', 'Sport', 'Kwota', 'Okres (rok)', 'Okres (miesiąc)'];

        $rows = [];
        $lp   = 0;
        foreach ($payments as $p) {
            $lp++;
            $rows[] = [
                $lp,
                $p['payment_date'] ?? '',
                ($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''),
                $p['member_number'] ?? '',
                $p['fee_name'] ?? '',
                $p['sport_name'] ?? '',
                number_format((float)($p['amount'] ?? 0), 2, ',', ''),
                $p['period_year'] ?? '',
                $p['period_month'] ?? '',
            ];
        }

        CsvExporter::download('finanse_' . $year . '.csv', $headers, $rows);
    }

    /**
     * PDF protocol for a single event.
     */
    public function eventProtocolPdf(string $eventId): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $clubId = $this->currentClub();
        $event  = (new EventModel())->findById((int)$eventId);

        if ($event === null) {
            $this->redirect('events');
        }

        // Try to load event entries/participants
        $entries = $this->getEventEntries((int)$eventId);

        $header = PdfHelper::getClubHeader($clubId);
        $footer  = PdfHelper::getSystemFooter();

        $html = View::partial('pdf/event_protocol', [
            'clubHeader'   => $header,
            'systemFooter' => $footer,
            'event'      => $event,
            'entries'     => $entries,
            'generated'  => date('d.m.Y H:i'),
        ]);

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $event['name'] ?? 'event');
        PdfHelper::renderToPdf($html, 'protokol_' . $safeName . '_' . date('Y-m-d') . '.pdf', 'P');
    }

    /**
     * PDF member card (A5 format) with QR placeholder.
     */
    public function memberCardPdf(string $memberId): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $clubId = $this->currentClub();
        $member = (new MemberModel())->withSports((int)$memberId);

        if (empty($member)) {
            $this->redirect('members');
        }

        $branding = (new ClubCustomizationModel())->findForClub($clubId) ?? ClubCustomizationModel::defaults();
        $header   = PdfHelper::getClubHeader($clubId);
        $footer  = PdfHelper::getSystemFooter();

        $html = View::partial('pdf/member_card', [
            'clubHeader'   => $header,
            'systemFooter' => $footer,
            'member'     => $member,
            'branding'   => $branding,
            'clubId'     => $clubId,
            'generated'  => date('d.m.Y H:i'),
        ]);

        $safeName = ($member['last_name'] ?? 'member') . '_' . ($member['first_name'] ?? '');
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $safeName);
        PdfHelper::renderToPdf($html, 'karta_' . $safeName . '.pdf', 'L');
    }

    /**
     * Y.2 — PDF raport miesięczny zaległości (overdue dues).
     * Idealny dla zarządu: kto zalega, ile, od kiedy.
     */
    public function monthlyDuesPdf(): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $clubId = $this->currentClub();
        $db     = \App\Helpers\Database::pdo();

        // Pobierz wszystkie zaległości (overdue + pending past due)
        $stmt = $db->prepare(
            "SELECT pd.*, m.first_name, m.last_name, m.member_number, m.email, m.phone,
                    fr.name AS rate_name, fr.fee_type
             FROM payment_dues pd
             JOIN members m ON m.id = pd.member_id
             LEFT JOIN fee_rates fr ON fr.id = pd.fee_rate_id
             WHERE pd.club_id = ?
               AND (pd.status = 'overdue'
                    OR (pd.status IN ('pending','partial') AND pd.due_date < CURDATE()))
             ORDER BY pd.due_date ASC, m.last_name"
        );
        $stmt->execute([$clubId]);
        $dues = $stmt->fetchAll();

        $totalOutstanding = 0.0;
        foreach ($dues as $d) {
            $totalOutstanding += (float)$d['net_amount'] - (float)$d['paid_amount'];
        }

        $header = PdfHelper::getClubHeader($clubId);
        $footer = PdfHelper::getSystemFooter();

        $html = View::partial('pdf/monthly_dues', [
            'clubHeader'       => $header,
            'systemFooter'     => $footer,
            'dues'             => $dues,
            'totalOutstanding' => $totalOutstanding,
            'generated'        => date('d.m.Y H:i'),
        ]);

        PdfHelper::renderToPdf($html, 'zaleglosci_' . date('Y-m-d') . '.pdf', 'L');
    }

    // ── Private helpers ──────────────────────────────────────

    /**
     * Get all members with their sports assignments.
     */
    private function getMembersWithSports(int $clubId): array
    {
        $memberModel = new MemberModel();
        $result      = $memberModel->search('', null, null, 1, 10000);
        $members     = $result['data'] ?? [];

        foreach ($members as &$m) {
            $full = $memberModel->withSports((int)$m['id']);
            $m['sports'] = $full['sports'] ?? [];
        }
        unset($m);

        return $members;
    }

    /**
     * Load entries for an event (if event_entries table exists).
     */
    private function getEventEntries(int $eventId): array
    {
        try {
            $db   = \App\Helpers\Database::pdo();
            $stmt = $db->prepare(
                "SELECT ee.*, m.first_name, m.last_name, m.member_number
                 FROM event_entries ee
                 JOIN members m ON m.id = ee.member_id
                 WHERE ee.event_id = ?
                 ORDER BY m.last_name, m.first_name"
            );
            $stmt->execute([$eventId]);
            return $stmt->fetchAll();
        } catch (\Throwable) {
            // Table may not exist; return empty
            return [];
        }
    }
}
