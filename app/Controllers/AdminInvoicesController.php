<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Pdf\InvoicePdf;
use App\Helpers\PdfHelper;
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
