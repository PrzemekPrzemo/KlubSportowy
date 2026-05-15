<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Pdf\InvoicePdf;
use App\Helpers\PdfHelper;
use App\Helpers\RateLimiter;
use App\Helpers\Session;
use App\Models\ActivityLogModel;
use App\Models\ClubModel;
use App\Models\InvoiceModel;
use App\Models\SettingModel;

class AdminInvoicesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    public function index(): void
    {
        $clubId = isset($_GET['club_id']) && $_GET['club_id'] !== '' ? (int)$_GET['club_id'] : null;
        $status = $_GET['status'] ?? null;
        $from   = $_GET['from'] ?? null;
        $to     = $_GET['to'] ?? null;
        $page   = max(1, (int)($_GET['page'] ?? 1));

        $model = new InvoiceModel();
        $pagination = $model->listAll($clubId, $status, $from, $to, $page, 30);
        $sums  = $model->sumByStatus();
        $clubs = (new ClubModel())->findAll('name', 'ASC');

        $this->render('admin/invoices/index', [
            'title'      => 'Faktury',
            'pagination' => $pagination,
            'sums'       => $sums,
            'clubs'      => $clubs,
            'filter'     => compact('clubId', 'status', 'from', 'to'),
        ]);
    }

    public function create(): void
    {
        $clubs = (new ClubModel())->findAll('name', 'ASC');
        $this->render('admin/invoices/create', [
            'title'       => 'Nowa faktura',
            'clubs'       => $clubs,
            'nextNumber'  => (new InvoiceModel())->getNextNumber(),
            'today'       => date('Y-m-d'),
            'defaultDue'  => date('Y-m-d', strtotime('+14 days')),
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $clubId = (int)($_POST['club_id'] ?? 0);
        $number = trim((string)($_POST['number'] ?? ''));
        $issue  = trim((string)($_POST['issue_date'] ?? ''));
        $due    = trim((string)($_POST['due_date'] ?? ''));
        $total  = (float)($_POST['total'] ?? 0);
        $status = in_array(($_POST['status'] ?? 'draft'), ['draft','issued','paid','cancelled'], true)
                  ? $_POST['status'] : 'draft';
        $notes  = trim((string)($_POST['notes'] ?? ''));

        if ($clubId <= 0 || $number === '' || $issue === '' || $due === '' || $total <= 0) {
            Session::flash('error', 'Uzupełnij wszystkie wymagane pola (klub, numer, daty, kwota > 0).');
            $this->redirect('admin/invoices/create');
        }

        $id = (new InvoiceModel())->createForAdmin([
            'club_id'    => $clubId,
            'number'     => $number,
            'issue_date' => $issue,
            'due_date'   => $due,
            'total'      => $total,
            'status'     => $status,
            'notes'      => $notes !== '' ? $notes : null,
        ]);
        (new ActivityLogModel())->log('invoice_create', 'invoice', $id, "club={$clubId};total={$total};status={$status}");
        Session::flash('success', "Utworzono fakturę {$number}.");
        $this->redirect('admin/invoices/' . $id);
    }

    public function show(string $id): void
    {
        $inv = (new InvoiceModel())->findWithClub((int)$id);
        if (!$inv) {
            Session::flash('error', 'Faktura nie istnieje.');
            $this->redirect('admin/invoices');
        }

        $this->render('admin/invoices/show', [
            'title'   => 'Faktura ' . $inv['number'],
            'invoice' => $inv,
        ]);
    }

    public function markPaid(string $id): void
    {
        Csrf::verify();
        $iid = (int)$id;
        (new InvoiceModel())->markPaid($iid);
        (new ActivityLogModel())->log('invoice_paid', 'invoice', $iid);
        Session::flash('success', 'Oznaczono jako zapłaconą.');
        $this->redirect('admin/invoices/' . $iid);
    }

    public function markCancelled(string $id): void
    {
        Csrf::verify();
        $iid = (int)$id;
        (new InvoiceModel())->markCancelled($iid);
        (new ActivityLogModel())->log('invoice_cancelled', 'invoice', $iid);
        Session::flash('success', 'Faktura anulowana.');
        $this->redirect('admin/invoices/' . $iid);
    }

    /**
     * Formularz bulk-generate: wybór miesiąca + klubów.
     */
    public function bulkForm(): void
    {
        $clubs = (new ClubModel())->findAll('name', 'ASC');
        $this->render('admin/invoices/bulk', [
            'title'      => 'Masowe generowanie faktur',
            'clubs'      => $clubs,
            'defaultMonth' => date('Y-m'),
        ]);
    }

    /**
     * Generuje faktury dla wybranych klubów za wybrany miesiąc.
     *
     * Idempotentnie: pomija kluby które już mają fakturę z wygenerowanym
     * prefiksem `FV/{Y}/{M}/` dla danego miesiąca.
     */
    public function bulkGenerate(): void
    {
        Csrf::verify();

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!RateLimiter::check($ip, 'bulk_invoice', 5, 60)) {
            Session::flash('error', 'Przekroczono limit bulk operacji (5/godz).');
            $this->redirect('admin/invoices/bulk');
        }
        RateLimiter::hit($ip, 'bulk_invoice', 5, 60);

        $month = trim((string)($_POST['month'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            Session::flash('error', 'Nieprawidłowy format miesiąca (YYYY-MM).');
            $this->redirect('admin/invoices/bulk');
        }
        [$year, $mm] = array_map('intval', explode('-', $month));
        $issueDate = $month . '-01';
        $dueDate   = date('Y-m-d', strtotime($issueDate . ' +14 days'));

        $clubIds = array_map('intval', (array)($_POST['club_ids'] ?? []));
        if (empty($clubIds)) {
            // Wszystkie kluby z aktywną subskrypcją
            $allClubs = (new ClubModel())->findAll('id', 'ASC');
            $clubIds  = array_map(fn($c) => (int)$c['id'], $allClubs);
        }

        $defaultAmount = (float)($_POST['amount'] ?? 99.00);

        $model      = new InvoiceModel();
        $db         = \App\Helpers\Database::pdo();
        $created    = 0;
        $skipped    = 0;
        $prefix     = sprintf('FV/%04d/%02d/', $year, $mm);

        foreach ($clubIds as $clubId) {
            if ($clubId <= 0) continue;
            // Idempotentnie: pomijamy gdy juz istnieje faktura w tym miesiacu dla klubu
            $chk = $db->prepare(
                "SELECT id FROM billing_invoices
                 WHERE club_id = ? AND number LIKE ? LIMIT 1"
            );
            $chk->execute([$clubId, $prefix . '%']);
            if ($chk->fetch()) { $skipped++; continue; }

            // Auto-numeracja sekwencyjna: FV/{Y}/{M}/{NNNN}
            $seqStmt = $db->prepare(
                "SELECT number FROM billing_invoices WHERE number LIKE ? ORDER BY id DESC LIMIT 1"
            );
            $seqStmt->execute([$prefix . '%']);
            $last = (string)($seqStmt->fetchColumn() ?: '');
            $next = 1;
            if ($last !== '' && preg_match('#/(\d+)$#', $last, $m2)) {
                $next = (int)$m2[1] + 1;
            }
            $number = $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);

            try {
                $id = $model->createForAdmin([
                    'club_id'    => $clubId,
                    'number'     => $number,
                    'issue_date' => $issueDate,
                    'due_date'   => $dueDate,
                    'total'      => $defaultAmount,
                    'status'     => 'issued',
                    'notes'      => 'Bulk generated for ' . $month,
                ]);
                (new ActivityLogModel())->log('invoice_bulk_create', 'invoice', $id, "club={$clubId};month={$month}");
                $created++;
            } catch (\Throwable $e) {
                $skipped++;
            }
        }

        Session::flash('success', "Bulk faktur: utworzono {$created}, pominięto {$skipped}.");
        $this->redirect('admin/invoices');
    }

    /**
     * Formularz JPK_FA: data od/do.
     */
    public function jpkFaForm(): void
    {
        $this->render('admin/invoices/jpk_fa', [
            'title'        => 'Eksport JPK_FA',
            'defaultFrom'  => date('Y-m-01'),
            'defaultTo'    => date('Y-m-t'),
        ]);
    }

    /**
     * Generuje uproszczony plik XML JPK_FA(4) dla faktur z danego zakresu dat.
     *
     * Stub: trzymamy się oficjalnej struktury MF (Naglowek, Podmiot1, Faktura, FakturaWiersz),
     * ale bez wszystkich opcjonalnych pól. Realna deklaracja wymaga uzupełnienia
     * danych sprzedawcy i pełnej VAT-owej dekompozycji per pozycja.
     */
    public function exportJpkFa(): void
    {
        Csrf::verify();
        $from = trim((string)($_POST['from'] ?? ''));
        $to   = trim((string)($_POST['to']   ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            Session::flash('error', 'Nieprawidłowy zakres dat.');
            $this->redirect('admin/invoices/jpk-fa');
        }

        $db = \App\Helpers\Database::pdo();
        $stmt = $db->prepare(
            "SELECT i.*, c.name AS club_name, c.nip AS club_nip,
                    c.address AS club_address, c.city AS club_city
             FROM billing_invoices i
             LEFT JOIN clubs c ON c.id = i.club_id
             WHERE i.issue_date BETWEEN ? AND ?
               AND i.status IN ('issued','paid')
             ORDER BY i.issue_date ASC, i.id ASC"
        );
        $stmt->execute([$from, $to]);
        $invoices = $stmt->fetchAll();

        $settings = new SettingModel();
        $sellerName = (string)$settings->get('platform_company_name', 'ClubDesk');
        $sellerNip  = (string)$settings->get('platform_company_nip',  '');
        $sellerCity = (string)$settings->get('platform_company_city', '');
        $sellerAddr = (string)$settings->get('platform_company_address', '');

        $sumNet = 0.0; $sumVat = 0.0; $sumGross = 0.0;
        foreach ($invoices as $inv) {
            $total = (float)$inv['total'];
            $sumGross += $total;
            $sumNet   += round($total / 1.23, 2);
            $sumVat   += round($total - $total / 1.23, 2);
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<JPK xmlns="http://jpk.mf.gov.pl/wzor/2019/09/27/09271/">' . "\n";
        $xml .= "  <Naglowek>\n";
        $xml .= "    <KodFormularza>JPK_FA</KodFormularza>\n";
        $xml .= "    <WariantFormularza>4</WariantFormularza>\n";
        $xml .= "    <CelZlozenia>1</CelZlozenia>\n";
        $xml .= '    <DataWytworzeniaJPK>' . date('Y-m-d\TH:i:s') . "</DataWytworzeniaJPK>\n";
        $xml .= '    <DataOd>' . htmlspecialchars($from, ENT_XML1, 'UTF-8') . "</DataOd>\n";
        $xml .= '    <DataDo>' . htmlspecialchars($to,   ENT_XML1, 'UTF-8') . "</DataDo>\n";
        $xml .= "    <KodWaluty>PLN</KodWaluty>\n";
        $xml .= "  </Naglowek>\n";

        $xml .= "  <Podmiot1>\n";
        $xml .= '    <NIP>' . htmlspecialchars($sellerNip, ENT_XML1, 'UTF-8') . "</NIP>\n";
        $xml .= '    <PelnaNazwa>' . htmlspecialchars($sellerName, ENT_XML1, 'UTF-8') . "</PelnaNazwa>\n";
        $xml .= '    <Miasto>' . htmlspecialchars($sellerCity, ENT_XML1, 'UTF-8') . "</Miasto>\n";
        $xml .= '    <Adres>' . htmlspecialchars($sellerAddr, ENT_XML1, 'UTF-8') . "</Adres>\n";
        $xml .= "  </Podmiot1>\n";

        foreach ($invoices as $inv) {
            $total  = (float)$inv['total'];
            $net    = round($total / 1.23, 2);
            $vat    = round($total - $net, 2);
            $xml .= "  <Faktura>\n";
            $xml .= '    <P_1>' . htmlspecialchars((string)$inv['issue_date'], ENT_XML1, 'UTF-8') . "</P_1>\n";
            $xml .= '    <P_2A>' . htmlspecialchars((string)$inv['number'],     ENT_XML1, 'UTF-8') . "</P_2A>\n";
            $xml .= '    <P_3A>' . htmlspecialchars((string)($inv['club_name']    ?? ''), ENT_XML1, 'UTF-8') . "</P_3A>\n";
            $xml .= '    <P_3B>' . htmlspecialchars((string)($inv['club_address'] ?? ''), ENT_XML1, 'UTF-8') . "</P_3B>\n";
            $xml .= '    <P_5B>' . htmlspecialchars((string)($inv['club_nip']     ?? ''), ENT_XML1, 'UTF-8') . "</P_5B>\n";
            $xml .= "    <P_13_1>" . number_format($net,   2, '.', '') . "</P_13_1>\n";
            $xml .= "    <P_14_1>" . number_format($vat,   2, '.', '') . "</P_14_1>\n";
            $xml .= "    <P_15>"   . number_format($total, 2, '.', '') . "</P_15>\n";
            $xml .= "  </Faktura>\n";
        }

        $xml .= "  <FakturaCtrl>\n";
        $xml .= '    <LiczbaFaktur>' . count($invoices) . "</LiczbaFaktur>\n";
        $xml .= "    <WartoscFaktur>" . number_format($sumGross, 2, '.', '') . "</WartoscFaktur>\n";
        $xml .= "  </FakturaCtrl>\n";

        $xml .= "</JPK>\n";

        $filename = 'JPK_FA_' . $from . '_' . $to . '.xml';
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo $xml;
        exit;
    }

    /**
     * Eksport faktury do PDF (zgodny szablon FV).
     */
    public function pdf(string $id): void
    {
        $inv = (new InvoiceModel())->findWithClub((int)$id);
        if (!$inv) {
            Session::flash('error', 'Faktura nie istnieje.');
            $this->redirect('admin/invoices');
        }

        $settings = new SettingModel();
        $sellerName    = (string)$settings->get('platform_company_name', 'ClubDesk');
        $sellerAddress = (string)$settings->get('platform_company_address', '');
        $sellerCity    = (string)$settings->get('platform_company_city', '');
        $sellerNip     = (string)$settings->get('platform_company_nip', '');
        $sellerRegon   = (string)$settings->get('platform_company_regon', '');

        $total = (float)$inv['total'];

        InvoicePdf::download([
            'seller' => [
                'name'    => $sellerName,
                'address' => $sellerAddress,
                'city'    => $sellerCity,
                'nip'     => $sellerNip,
                'regon'   => $sellerRegon,
            ],
            'buyer' => [
                'name'    => (string)($inv['club_name']    ?? ''),
                'address' => (string)($inv['club_address'] ?? ''),
                'city'    => (string)($inv['club_city']    ?? ''),
                'nip'     => (string)($inv['club_nip']     ?? ''),
            ],
            'invoice' => [
                'number'         => (string)$inv['number'],
                'issue_date'     => (string)$inv['issue_date'],
                'sale_date'      => (string)$inv['issue_date'],
                'due_date'       => (string)$inv['due_date'],
                'status'         => (string)$inv['status'],
                'payment_method' => 'przelew bankowy',
                'notes'          => (string)($inv['notes'] ?? ''),
                'total'          => $total,
            ],
            'items' => [[
                'name'        => 'Subskrypcja ClubDesk — okres rozliczeniowy',
                'qty'         => 1,
                'unit'        => 'szt.',
                'net_price'   => round($total / 1.23, 2),
                'vat_rate'    => 23,
                'net_total'   => round($total / 1.23, 2),
                'gross_total' => $total,
            ]],
            'totals' => [
                'net'   => round($total / 1.23, 2),
                'vat'   => round($total - $total / 1.23, 2),
                'gross' => $total,
            ],
            'club_header_html' => PdfHelper::getSystemFooter(),
        ], 'faktura-' . preg_replace('/[^a-z0-9_\-]/i', '_', (string)$inv['number']) . '.pdf');
    }
}
