<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Ksef\FA2XmlGenerator;
use App\Helpers\Pdf\InvoicePdf;
use App\Helpers\PdfHelper;
use App\Helpers\Session;
use App\Models\ClubInvoiceItemModel;
use App\Models\ClubInvoiceModel;
use App\Models\ClubKsefConfigModel;
use App\Models\ClubModel;
use App\Models\KsefSendQueueModel;
use App\Models\KsefUpoArchiveModel;
use App\Models\MemberModel;
use App\Models\PaymentModel;

/**
 * Faktury sprzedaży klubu (KSeF Phase 2).
 *
 * Workflow:
 *   draft  -> wystawiona (issued)  -> [Phase 3] sent_ksef -> accepted_ksef
 *   draft|issued -> cancelled
 *
 * Edytowalne są tylko drafty. Issued ma już oficjalny numer FV i nie może
 * być modyfikowana — można jedynie anulować (lub w Phase 3 wystawić
 * korektę).
 *
 * Multi-tenant: każda akcja przechodzi przez ClubInvoiceModel::findForClub()
 * z club_id z sesji. Brak danych z innego klubu nawet przy zgadnięciu ID.
 */
class ClubInvoicesController extends BaseController
{
    private ClubInvoiceModel $invoices;
    private ClubInvoiceItemModel $items;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        // Sekretariat (ksiegowy) wystawia faktury wraz z zarządem.
        $this->requireRole(['zarzad', 'ksiegowy', 'admin']);
        $this->invoices = new ClubInvoiceModel();
        $this->items    = new ClubInvoiceItemModel();
    }

    // -------------------------------------------------------------- index

    public function index(): void
    {
        $clubId = $this->currentClub();
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $filters = [
            'status'          => trim((string)($_GET['status'] ?? '')) ?: null,
            'buyer_member_id' => !empty($_GET['member_id']) ? (int)$_GET['member_id'] : null,
            'year'            => !empty($_GET['year']) ? (int)$_GET['year'] : null,
            'date_from'       => trim((string)($_GET['date_from'] ?? '')) ?: null,
            'date_to'         => trim((string)($_GET['date_to']   ?? '')) ?: null,
            'q'               => trim((string)($_GET['q']         ?? '')) ?: null,
        ];

        $pagination = $this->invoices->listForClub($filters, $page, 25);
        $stats      = $this->invoices->statsForClub($clubId, $filters['year']);

        $this->render('club/invoices/index', [
            'title'      => 'Faktury sprzedaży',
            'pagination' => $pagination,
            'stats'      => $stats,
            'filters'    => $filters,
        ]);
    }

    // -------------------------------------------------------------- create / store

    public function create(): void
    {
        $invoice = $this->blankDraft();
        $members = $this->membersList();
        $items   = [$this->blankItem()];

        $this->render('club/invoices/form', [
            'title'   => 'Nowa faktura',
            'invoice' => $invoice,
            'items'   => $items,
            'members' => $members,
            'mode'    => 'create',
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();

        [$header, $items, $errors] = $this->collectFromPost($clubId);
        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            $this->redirect('club/invoices/create');
        }

        $id = $this->invoices->createDraft($header);
        $this->items->replaceAll($id, $items);
        $this->invoices->recalculateTotals($id);

        Session::flash('success', 'Faktura zapisana jako szkic.');
        $this->redirect('club/invoices/' . $id);
    }

    // -------------------------------------------------------------- show

    public function show(string $id): void
    {
        $clubId = $this->currentClub();
        $invoice = $this->invoices->findForClub((int)$id, $clubId);
        if (!$invoice) {
            $this->notFoundAndBack();
        }
        $items = $this->items->listForInvoice((int)$id);

        $queueModel = new KsefSendQueueModel();
        $queueEntry = $queueModel->findByInvoice((int)$id, $clubId);
        $ksefCfg    = (new ClubKsefConfigModel())->findForClub($clubId);
        $upo        = (new KsefUpoArchiveModel())->findForInvoice((int)$id, $clubId);

        $this->render('club/invoices/show', [
            'title'      => 'Faktura ' . $invoice['invoice_number'],
            'invoice'    => $invoice,
            'items'      => $items,
            'queueEntry' => $queueEntry,
            'ksefEnabled'=> $ksefCfg !== null && (int)($ksefCfg['enabled'] ?? 0) === 1,
            'upo'        => $upo,
        ]);
    }

    // -------------------------------------------------------------- KSeF send / retry / UPO

    /**
     * Zakolejkuj fakture do wysylki do KSeF (Phase 3).
     */
    public function sendKsef(string $id): void
    {
        Csrf::verify();
        $clubId  = $this->currentClub();
        $invoice = $this->invoices->findForClub((int)$id, $clubId);
        if (!$invoice) {
            $this->notFoundAndBack();
        }
        if ($invoice['status'] !== 'issued') {
            Session::flash('error', 'Mozna wyslac tylko wystawione faktury (status=issued).');
            $this->redirect('club/invoices/' . (int)$id);
        }
        $cfgModel = new ClubKsefConfigModel();
        $cfg      = $cfgModel->findForClub($clubId);
        if (!$cfg || (int)($cfg['enabled'] ?? 0) !== 1) {
            Session::flash('error', 'KSeF nie jest aktywny dla tego klubu (skontaktuj sie z administratorem platformy).');
            $this->redirect('club/invoices/' . (int)$id);
        }

        $queueModel = new KsefSendQueueModel();
        $qid = $queueModel->enqueue($clubId, (int)$id);
        if ($qid === null) {
            Session::flash('warning', 'Faktura juz znajduje sie w kolejce KSeF.');
        } else {
            $cfgModel->audit($clubId, 'queue_enqueued', 'invoice_id=' . (int)$id);
            Session::flash('success', 'Faktura zakolejkowana do wyslania do KSeF.');
        }
        $this->redirect('club/invoices/' . (int)$id);
    }

    /**
     * Ponow probe wysylki po failure (resetuje attempts, status=queued).
     */
    public function retryKsef(string $id): void
    {
        Csrf::verify();
        $clubId  = $this->currentClub();
        $invoice = $this->invoices->findForClub((int)$id, $clubId);
        if (!$invoice) {
            $this->notFoundAndBack();
        }
        $queueModel = new KsefSendQueueModel();
        $entry = $queueModel->findByInvoice((int)$id, $clubId);
        if (!$entry) {
            Session::flash('error', 'Brak wpisu w kolejce — uzyj "Wyslij do KSeF".');
            $this->redirect('club/invoices/' . (int)$id);
        }
        if (!in_array((string)$entry['status'], ['failed', 'retrying'], true)) {
            Session::flash('warning', 'Faktura nie jest w stanie wymagajacym ponowienia.');
            $this->redirect('club/invoices/' . (int)$id);
        }
        $queueModel->forceRetry((int)$entry['id']);
        (new ClubKsefConfigModel())->audit($clubId, 'queue_force_retry', 'invoice_id=' . (int)$id);
        Session::flash('success', 'Wymuszono ponowienie wysylki.');
        $this->redirect('club/invoices/' . (int)$id);
    }

    /**
     * Pobierz UPO XML jesli istnieje w archiwum.
     */
    public function downloadUpo(string $id): void
    {
        $clubId  = $this->currentClub();
        $invoice = $this->invoices->findForClub((int)$id, $clubId);
        if (!$invoice) {
            $this->notFoundAndBack();
        }
        $upo = (new KsefUpoArchiveModel())->findForInvoice((int)$id, $clubId);
        if (!$upo) {
            Session::flash('error', 'UPO niedostepne dla tej faktury.');
            $this->redirect('club/invoices/' . (int)$id);
        }
        $path = (string)$upo['upo_xml_path'];
        // Sanity check — sciezka MUSI byc w storage/ksef/upo/{clubId}/
        $expectedPrefix = ROOT_PATH . '/storage/ksef/upo/' . $clubId . '/';
        $realPath       = realpath($path);
        if ($realPath === false || !str_starts_with($realPath, realpath(ROOT_PATH . '/storage/ksef/upo/' . $clubId) ?: $expectedPrefix)) {
            Session::flash('error', 'UPO niedostepne (sciezka odrzucona).');
            $this->redirect('club/invoices/' . (int)$id);
        }
        if (!is_readable($realPath)) {
            Session::flash('error', 'UPO niedostepne (brak pliku).');
            $this->redirect('club/invoices/' . (int)$id);
        }
        $fname = 'upo-' . preg_replace('/[^a-z0-9_\-]/i', '_', (string)$invoice['invoice_number']) . '.xml';
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Content-Length: ' . (string)filesize($realPath));
        readfile($realPath);
        exit;
    }

    // -------------------------------------------------------------- edit / update

    public function edit(string $id): void
    {
        $clubId = $this->currentClub();
        $invoice = $this->invoices->findForClub((int)$id, $clubId);
        if (!$invoice) {
            $this->notFoundAndBack();
        }
        if ($invoice['status'] !== 'draft') {
            Session::flash('warning', 'Tylko faktury w stanie "szkic" można edytować.');
            $this->redirect('club/invoices/' . (int)$id);
        }
        $items = $this->items->listForInvoice((int)$id);
        if (empty($items)) {
            $items = [$this->blankItem()];
        }
        $members = $this->membersList();

        $this->render('club/invoices/form', [
            'title'   => 'Edycja faktury (szkic)',
            'invoice' => $invoice,
            'items'   => $items,
            'members' => $members,
            'mode'    => 'edit',
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        $existing = $this->invoices->findForClub((int)$id, $clubId);
        if (!$existing) {
            $this->notFoundAndBack();
        }
        if ($existing['status'] !== 'draft') {
            Session::flash('error', 'Faktura już wystawiona — nie można edytować.');
            $this->redirect('club/invoices/' . (int)$id);
        }

        [$header, $items, $errors] = $this->collectFromPost($clubId);
        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            $this->redirect('club/invoices/' . (int)$id . '/edit');
        }

        // Update header; numbering and status nie ruszamy tutaj.
        $this->invoices->updateForClub((int)$id, $clubId, $header);
        $this->items->replaceAll((int)$id, $items);
        $this->invoices->recalculateTotals((int)$id);

        Session::flash('success', 'Zmiany zapisane.');
        $this->redirect('club/invoices/' . (int)$id);
    }

    // -------------------------------------------------------------- issue

    public function issue(string $id): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        $invoice = $this->invoices->findForClub((int)$id, $clubId);
        if (!$invoice) {
            $this->notFoundAndBack();
        }
        $items = $this->items->listForInvoice((int)$id);
        if (empty($items)) {
            Session::flash('error', 'Faktura musi mieć przynajmniej jedną pozycję.');
            $this->redirect('club/invoices/' . (int)$id);
        }
        if ((float)$invoice['total_gross'] <= 0) {
            Session::flash('error', 'Wartość brutto musi być większa od zera.');
            $this->redirect('club/invoices/' . (int)$id);
        }

        $num = $this->invoices->issue((int)$id, $clubId);
        if ($num === null) {
            Session::flash('error', 'Nie udało się wystawić faktury (sprawdź status).');
        } else {
            Session::flash('success', 'Faktura wystawiona pod numerem ' . $num . '.');
        }
        $this->redirect('club/invoices/' . (int)$id);
    }

    // -------------------------------------------------------------- cancel

    public function cancel(string $id): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        $invoice = $this->invoices->findForClub((int)$id, $clubId);
        if (!$invoice) {
            $this->notFoundAndBack();
        }
        if (in_array($invoice['status'], ['sent_ksef','accepted_ksef'], true)) {
            Session::flash('error', 'Faktura wysłana do KSeF — wymagana korekta (Phase 3).');
            $this->redirect('club/invoices/' . (int)$id);
        }
        if ($this->invoices->cancel((int)$id, $clubId)) {
            Session::flash('success', 'Faktura anulowana.');
        } else {
            Session::flash('error', 'Nie udało się anulować faktury.');
        }
        $this->redirect('club/invoices/' . (int)$id);
    }

    // -------------------------------------------------------------- PDF

    public function downloadPdf(string $id): void
    {
        $clubId  = $this->currentClub();
        $invoice = $this->invoices->findForClub((int)$id, $clubId);
        if (!$invoice) {
            $this->notFoundAndBack();
        }
        $items = $this->items->listForInvoice((int)$id);

        $pdfData = $this->mapForPdf($invoice, $items, $clubId);
        $name    = 'faktura-' . preg_replace('/[^a-z0-9_\-]/i', '_', (string)$invoice['invoice_number']) . '.pdf';
        // InvoicePdf::download() exits — wrap in try and re-throw the noreturn via redirect on error.
        InvoicePdf::download($pdfData, $name);
    }

    // -------------------------------------------------------------- XML preview (no send)

    public function previewXml(string $id): void
    {
        $clubId  = $this->currentClub();
        $invoice = $this->invoices->findForClub((int)$id, $clubId);
        if (!$invoice) {
            $this->notFoundAndBack();
        }
        $items = $this->items->listForInvoice((int)$id);

        try {
            $xml = FA2XmlGenerator::generate($invoice, $items);
        } catch (\Throwable $e) {
            Session::flash('error', 'Błąd generowania XML: ' . $e->getMessage());
            $this->redirect('club/invoices/' . (int)$id);
        }

        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: inline; filename="ksef-' . (int)$id . '.xml"');
        echo $xml;
        exit;
    }

    // -------------------------------------------------------------- create from payment

    public function createFromPayment(string $paymentId): void
    {
        $clubId = $this->currentClub();
        $pid    = (int)$paymentId;

        // Bezpieczne pobranie płatności (PaymentModel jest club-scoped — findById
        // dopilnuje izolacji)
        $payment = (new PaymentModel())->findById($pid);
        if (!$payment) {
            Session::flash('error', 'Płatność nie istnieje.');
            $this->redirect('club/invoices');
        }
        if ($this->invoices->existsForPayment($pid, $clubId)) {
            Session::flash('warning', 'Dla tej płatności już istnieje faktura.');
            $this->redirect('club/invoices');
        }

        $invoice = $this->draftFromPayment($payment, $clubId);
        $id      = $this->invoices->createDraft($invoice['header']);
        $this->items->replaceAll($id, $invoice['items']);
        $this->invoices->recalculateTotals($id);

        Session::flash('success', 'Utworzono szkic faktury z płatności #' . $pid . '.');
        $this->redirect('club/invoices/' . $id . '/edit');
    }

    public function bulkFromPayments(): void
    {
        $clubId = $this->currentClub();
        // Lista nieobsłużonych płatności (bez powiązanej faktury)
        $pdo = \App\Helpers\Database::pdo();
        $st  = $pdo->prepare(
            "SELECT p.*, m.first_name, m.last_name, m.member_number,
                    fr.name AS fee_name
               FROM payments p
               JOIN members m ON m.id = p.member_id
          LEFT JOIN fee_rates fr ON fr.id = p.fee_rate_id
          LEFT JOIN club_invoices ci ON ci.source_payment_id = p.id AND ci.status <> 'cancelled'
              WHERE p.club_id = ?
                AND ci.id IS NULL
           ORDER BY p.payment_date DESC, p.id DESC
              LIMIT 500"
        );
        $st->execute([$clubId]);
        $payments = $st->fetchAll() ?: [];

        $this->render('club/invoices/bulk_from_payments', [
            'title'    => 'Wystaw faktury z płatności',
            'payments' => $payments,
        ]);
    }

    public function bulkFromPaymentsStore(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        $ids    = $_POST['payment_ids'] ?? [];
        if (!is_array($ids) || empty($ids)) {
            Session::flash('warning', 'Nie zaznaczono płatności.');
            $this->redirect('club/invoices/bulk-from-payments');
        }
        $created = 0;
        $skipped = 0;
        $paymentModel = new PaymentModel();

        foreach ($ids as $pid) {
            $pid = (int)$pid;
            if ($pid <= 0) continue;
            $payment = $paymentModel->findById($pid);
            if (!$payment) { $skipped++; continue; }
            if ($this->invoices->existsForPayment($pid, $clubId)) { $skipped++; continue; }

            $invoice = $this->draftFromPayment($payment, $clubId);
            $id      = $this->invoices->createDraft($invoice['header']);
            $this->items->replaceAll($id, $invoice['items']);
            $this->invoices->recalculateTotals($id);
            $created++;
        }

        Session::flash(
            'success',
            "Utworzono {$created} szkic(ów) faktur." . ($skipped > 0 ? " Pominięto: {$skipped}." : '')
        );
        $this->redirect('club/invoices');
    }

    // ================================================================= helpers

    /**
     * @return array{0:array<string,mixed>,1:array<int,array<string,mixed>>,2:array<int,string>}
     */
    private function collectFromPost(int $clubId): array
    {
        $club = (new ClubModel())->findById($clubId);

        $buyerMemberId = !empty($_POST['buyer_member_id']) ? (int)$_POST['buyer_member_id'] : null;
        $buyerName  = trim((string)($_POST['buyer_name']  ?? ''));
        $buyerNip   = preg_replace('/\D/', '', (string)($_POST['buyer_nip'] ?? '')) ?? '';
        $buyerAddr  = trim((string)($_POST['buyer_address'] ?? ''));
        $buyerEmail = trim((string)($_POST['buyer_email']   ?? ''));

        // Auto-fill from member if selected and buyer fields empty
        if ($buyerMemberId !== null && $buyerName === '') {
            $member = (new MemberModel())->findById($buyerMemberId);
            if ($member && (int)($member['club_id'] ?? 0) === $clubId) {
                $buyerName  = trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''));
                $buyerEmail = $buyerEmail !== '' ? $buyerEmail : (string)($member['email'] ?? '');
                $buyerAddr  = $buyerAddr  !== '' ? $buyerAddr  : trim(
                    ($member['address_street'] ?? '') . "\n" .
                    trim(($member['address_postal'] ?? '') . ' ' . ($member['address_city'] ?? ''))
                );
            }
        }

        $sellerName = (string)($club['name'] ?? '');
        // Seller NIP z club_ksef_config (preferowane — clean 10 digits)
        $cfg = (new ClubKsefConfigModel())->findForClub($clubId);
        $sellerNip = (string)($cfg['nip'] ?? preg_replace('/\D/', '', (string)($club['nip'] ?? '')));
        $sellerAddr = trim(
            (string)($club['address'] ?? '') . "\n" . (string)($club['city'] ?? '')
        );

        $invoiceType = in_array(($_POST['invoice_type'] ?? 'VAT'), ['VAT','VAT_korekta','VAT_RR','proforma','paragon'], true)
            ? $_POST['invoice_type'] : 'VAT';

        $issueDate = $this->validDate((string)($_POST['issue_date'] ?? '')) ?? date('Y-m-d');
        $saleDate  = $this->validDate((string)($_POST['sale_date']  ?? '')) ?? $issueDate;
        $dueDate   = $this->validDate((string)($_POST['due_date']   ?? ''));

        $header = [
            'invoice_type'   => $invoiceType,
            'seller_name'    => mb_substr($sellerName, 0, 200),
            'seller_nip'     => $sellerNip !== '' ? $sellerNip : '0000000000',
            'seller_address' => $sellerAddr,
            'buyer_member_id'=> $buyerMemberId,
            'buyer_name'     => mb_substr($buyerName, 0, 200),
            'buyer_nip'      => ($buyerNip !== '' && strlen((string)$buyerNip) === 10) ? $buyerNip : null,
            'buyer_address'  => $buyerAddr,
            'buyer_email'    => $buyerEmail !== '' ? mb_substr($buyerEmail, 0, 255) : null,
            'issue_date'     => $issueDate,
            'sale_date'      => $saleDate,
            'due_date'       => $dueDate,
            'currency'       => 'PLN',
            'notes'          => trim((string)($_POST['notes'] ?? '')) ?: null,
            'created_by'     => \App\Helpers\Auth::id(),
        ];

        // Items
        $descs   = $_POST['item_description']    ?? [];
        $qtys    = $_POST['item_quantity']       ?? [];
        $units   = $_POST['item_unit']           ?? [];
        $prices  = $_POST['item_unit_price_net'] ?? [];
        $rates   = $_POST['item_vat_rate']       ?? [];
        $pkwius  = $_POST['item_pkwiu']          ?? [];
        $gtus    = $_POST['item_gtu_code']       ?? [];

        $items = [];
        if (is_array($descs)) {
            foreach ($descs as $i => $d) {
                $d = trim((string)$d);
                if ($d === '') continue;
                $items[] = [
                    'description'    => $d,
                    'quantity'       => (float)($qtys[$i]   ?? 1),
                    'unit'           => (string)($units[$i] ?? 'szt.'),
                    'unit_price_net' => (float)($prices[$i] ?? 0),
                    'vat_rate'       => (float)($rates[$i]  ?? 23),
                    'pkwiu'          => trim((string)($pkwius[$i] ?? '')) ?: null,
                    'gtu_code'       => trim((string)($gtus[$i]   ?? '')) ?: null,
                ];
            }
        }

        // Walidacja
        $errors = [];
        if ($header['buyer_name'] === '') {
            $errors[] = 'Nazwa nabywcy jest wymagana.';
        }
        if ($header['buyer_nip'] !== null && !ClubKsefConfigModel::validateNip((string)$header['buyer_nip'])) {
            $errors[] = 'Niepoprawny NIP nabywcy (10 cyfr, suma kontrolna).';
        }
        if (empty($items)) {
            $errors[] = 'Wymagana co najmniej jedna pozycja.';
        }

        return [$header, $items, $errors];
    }

    /** @return array{header:array<string,mixed>,items:array<int,array<string,mixed>>} */
    private function draftFromPayment(array $payment, int $clubId): array
    {
        $member = (new MemberModel())->findById((int)$payment['member_id']);
        $club   = (new ClubModel())->findById($clubId);
        $cfg    = (new ClubKsefConfigModel())->findForClub($clubId);

        $buyerName = $member
            ? trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''))
            : 'Nabywca';
        $sellerNip = (string)($cfg['nip'] ?? preg_replace('/\D/', '', (string)($club['nip'] ?? '')));

        $amount = (float)($payment['amount'] ?? 0);

        $header = [
            'invoice_type'    => 'VAT',
            'seller_name'     => (string)($club['name'] ?? ''),
            'seller_nip'      => $sellerNip !== '' ? $sellerNip : '0000000000',
            'seller_address'  => trim((string)($club['address'] ?? '') . "\n" . (string)($club['city'] ?? '')),
            'buyer_member_id' => (int)$payment['member_id'],
            'buyer_name'      => $buyerName,
            'buyer_nip'       => null,
            'buyer_address'   => $member ? trim(
                (string)($member['address_street'] ?? '') . "\n" .
                trim((string)($member['address_postal'] ?? '') . ' ' . (string)($member['address_city'] ?? ''))
            ) : null,
            'buyer_email'     => $member['email'] ?? null,
            'issue_date'      => date('Y-m-d'),
            'sale_date'       => (string)($payment['payment_date'] ?? date('Y-m-d')),
            'due_date'        => null,
            'currency'        => 'PLN',
            'source_payment_id' => (int)$payment['id'],
            'notes'           => 'Faktura wystawiona z płatności #' . (int)$payment['id'],
            'created_by'      => \App\Helpers\Auth::id(),
            'payment_status'  => 'paid',
            'payment_paid_amount' => $amount,
        ];

        // Domyślnie 1 pozycja, 23% VAT, kwota traktowana jako brutto:
        // net = round(amount / 1.23, 2)
        $vatR = 23.0;
        $net  = round($amount / 1.23, 2);
        $vat  = round($amount - $net, 2);

        $items = [[
            'description'    => 'Składka członkowska — ' . ($payment['period_year'] ?? date('Y')),
            'quantity'       => 1,
            'unit'           => 'szt.',
            'unit_price_net' => $net,
            'vat_rate'       => $vatR,
            'net_amount'     => $net,
            'vat_amount'     => $vat,
            'gross_amount'   => round($net + $vat, 2),
        ]];

        return ['header' => $header, 'items' => $items];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function membersList(): array
    {
        $clubId = $this->currentClub();
        $pdo = \App\Helpers\Database::pdo();
        $st = $pdo->prepare(
            "SELECT id, member_number, first_name, last_name, email
               FROM members
              WHERE club_id = ? AND status = 'aktywny'
           ORDER BY last_name ASC, first_name ASC
              LIMIT 1000"
        );
        $st->execute([$clubId]);
        return $st->fetchAll() ?: [];
    }

    private function blankDraft(): array
    {
        $clubId = $this->currentClub();
        $club   = (new ClubModel())->findById($clubId);
        $cfg    = (new ClubKsefConfigModel())->findForClub($clubId);
        return [
            'id'             => 0,
            'invoice_number' => 'DRAFT',
            'invoice_type'   => 'VAT',
            'seller_name'    => (string)($club['name'] ?? ''),
            'seller_nip'     => (string)($cfg['nip'] ?? preg_replace('/\D/', '', (string)($club['nip'] ?? ''))),
            'seller_address' => trim((string)($club['address'] ?? '') . "\n" . (string)($club['city'] ?? '')),
            'buyer_member_id'=> null,
            'buyer_name'     => '',
            'buyer_nip'      => '',
            'buyer_address'  => '',
            'buyer_email'    => '',
            'issue_date'     => date('Y-m-d'),
            'sale_date'      => date('Y-m-d'),
            'due_date'       => date('Y-m-d', strtotime('+14 days')),
            'status'         => 'draft',
            'currency'       => 'PLN',
            'notes'          => '',
            'total_net'      => 0,
            'total_vat'      => 0,
            'total_gross'    => 0,
        ];
    }

    private function blankItem(): array
    {
        return [
            'description'    => '',
            'quantity'       => 1,
            'unit'           => 'szt.',
            'unit_price_net' => 0,
            'vat_rate'       => 23,
            'pkwiu'          => '',
            'gtu_code'       => '',
        ];
    }

    private function validDate(string $d): ?string
    {
        if ($d === '') return null;
        $ts = strtotime($d);
        return $ts === false ? null : date('Y-m-d', $ts);
    }

    private function notFoundAndBack(): never
    {
        Session::flash('error', 'Nie znaleziono faktury.');
        $this->redirect('club/invoices');
    }

    /**
     * Mapuje wewnętrzną fakturę do struktury oczekiwanej przez InvoicePdf::generate().
     *
     * @return array<string,mixed>
     */
    private function mapForPdf(array $invoice, array $items, int $clubId): array
    {
        $club = (new ClubModel())->findById($clubId);
        $clubHeader = PdfHelper::getClubHeader($clubId);

        $pdfItems = array_map(static function (array $it): array {
            return [
                'name'        => (string)($it['description'] ?? ''),
                'qty'         => (float)($it['quantity'] ?? 1),
                'unit'        => (string)($it['unit'] ?? 'szt.'),
                'net_price'   => (float)($it['unit_price_net'] ?? 0),
                'vat_rate'    => max(0.0, (float)($it['vat_rate'] ?? 0)),
                'net_total'   => (float)($it['net_amount']   ?? 0),
                'gross_total' => (float)($it['gross_amount'] ?? 0),
            ];
        }, $items);

        return [
            'club_header_html' => $clubHeader,
            'seller' => [
                'name'    => (string)($invoice['seller_name'] ?? ($club['name'] ?? '')),
                'address' => (string)($invoice['seller_address'] ?? ''),
                'city'    => (string)($club['city'] ?? ''),
                'nip'     => (string)($invoice['seller_nip'] ?? ''),
                'regon'   => (string)($club['regon'] ?? ''),
            ],
            'buyer' => [
                'name'    => (string)($invoice['buyer_name'] ?? ''),
                'address' => (string)($invoice['buyer_address'] ?? ''),
                'city'    => '',
                'nip'     => (string)($invoice['buyer_nip'] ?? ''),
            ],
            'invoice' => [
                'number'         => (string)($invoice['invoice_number'] ?? ''),
                'issue_date'     => (string)($invoice['issue_date'] ?? date('Y-m-d')),
                'sale_date'      => (string)($invoice['sale_date']  ?? date('Y-m-d')),
                'due_date'       => (string)($invoice['due_date']   ?? date('Y-m-d')),
                'status'         => $invoice['status'] === 'cancelled' ? 'cancelled'
                                   : ($invoice['payment_status'] === 'paid' ? 'paid'
                                   : ($invoice['status'] === 'draft' ? 'draft' : 'issued')),
                'payment_method' => 'przelew',
                'notes'          => (string)($invoice['notes'] ?? ''),
            ],
            'items'  => $pdfItems,
            'totals' => [
                'net'   => (float)($invoice['total_net']   ?? 0),
                'vat'   => (float)($invoice['total_vat']   ?? 0),
                'gross' => (float)($invoice['total_gross'] ?? 0),
            ],
        ];
    }
}
