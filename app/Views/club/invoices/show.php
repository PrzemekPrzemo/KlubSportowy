<?php
/**
 * @var array               $invoice
 * @var array<int,array>    $items
 * @var array<string,mixed>|null $queueEntry
 * @var bool                $ksefEnabled
 * @var array<string,mixed>|null $upo
 */
use App\Helpers\View;

$statusColors = [
    'draft' => 'secondary', 'issued' => 'primary',
    'sent_ksef' => 'info', 'accepted_ksef' => 'success',
    'rejected_ksef' => 'danger', 'cancelled' => 'dark',
];
$statusLabels = [
    'draft' => 'Szkic', 'issued' => 'Wystawiona',
    'sent_ksef' => 'Wysłana do KSeF', 'accepted_ksef' => 'KSeF: zaakceptowana',
    'rejected_ksef' => 'KSeF: odrzucona', 'cancelled' => 'Anulowana',
];
$id     = (int)$invoice['id'];
$status = (string)$invoice['status'];
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h3 class="mb-0">
        <i class="bi bi-receipt me-2"></i>
        Faktura <code><?= View::e((string)$invoice['invoice_number']) ?></code>
        <span class="badge bg-<?= $statusColors[$status] ?? 'secondary' ?> ms-2">
            <?= View::e($statusLabels[$status] ?? $status) ?>
        </span>
    </h3>
    <a href="<?= url('club/invoices') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Lista
    </a>
</div>

<?php foreach (['success'=>'flashSuccess','error'=>'flashError','warning'=>'flashWarning','info'=>'flashInfo'] as $cls=>$flash): if (!empty($$flash)): ?>
    <div class="alert alert-<?= $cls ?>"><?= View::e($$flash) ?></div>
<?php endif; endforeach; ?>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted small text-uppercase">Sprzedawca</h6>
                        <strong><?= View::e((string)$invoice['seller_name']) ?></strong><br>
                        <?= nl2br(View::e((string)($invoice['seller_address'] ?? ''))) ?><br>
                        <small>NIP: <code><?= View::e((string)$invoice['seller_nip']) ?></code></small>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted small text-uppercase">Nabywca</h6>
                        <strong><?= View::e((string)$invoice['buyer_name']) ?></strong><br>
                        <?= nl2br(View::e((string)($invoice['buyer_address'] ?? ''))) ?><br>
                        <?php if (!empty($invoice['buyer_nip'])): ?>
                            <small>NIP: <code><?= View::e((string)$invoice['buyer_nip']) ?></code></small><br>
                        <?php endif; ?>
                        <?php if (!empty($invoice['buyer_email'])): ?>
                            <small><?= View::e((string)$invoice['buyer_email']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <hr>
                <dl class="row small mb-0">
                    <dt class="col-sm-3">Data wystawienia</dt><dd class="col-sm-3"><?= View::e((string)$invoice['issue_date']) ?></dd>
                    <dt class="col-sm-3">Data sprzedaży</dt> <dd class="col-sm-3"><?= View::e((string)$invoice['sale_date']) ?></dd>
                    <dt class="col-sm-3">Termin płatności</dt><dd class="col-sm-3"><?= View::e((string)($invoice['due_date'] ?? '—')) ?></dd>
                    <dt class="col-sm-3">Rodzaj</dt>          <dd class="col-sm-3"><?= View::e((string)$invoice['invoice_type']) ?></dd>
                </dl>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header py-2"><strong><i class="bi bi-list-ul"></i> Pozycje</strong></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Lp</th><th>Opis</th><th>PKWiU</th>
                            <th>Ilość</th><th>J.m.</th>
                            <th class="text-end">Cena netto</th>
                            <th class="text-end">Netto</th>
                            <th>VAT %</th>
                            <th class="text-end">VAT</th>
                            <th class="text-end">Brutto</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $i => $it): ?>
                        <tr>
                            <td><?= (int)($it['position'] ?? ($i + 1)) ?></td>
                            <td><?= View::e((string)$it['description']) ?></td>
                            <td><?= View::e((string)($it['pkwiu'] ?? '')) ?></td>
                            <td><?= rtrim(rtrim(number_format((float)$it['quantity'], 3, ',', ''), '0'), ',') ?></td>
                            <td><?= View::e((string)$it['unit']) ?></td>
                            <td class="text-end"><?= number_format((float)$it['unit_price_net'], 2, ',', ' ') ?></td>
                            <td class="text-end"><?= number_format((float)$it['net_amount'], 2, ',', ' ') ?></td>
                            <td><?php $v = (float)$it['vat_rate']; echo $v < 0 ? ($v <= -2 ? 'NP' : 'ZW') : (string)(int)$v . '%'; ?></td>
                            <td class="text-end"><?= number_format((float)$it['vat_amount'], 2, ',', ' ') ?></td>
                            <td class="text-end"><strong><?= number_format((float)$it['gross_amount'], 2, ',', ' ') ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="6" class="text-end">Razem:</th>
                            <th class="text-end"><?= number_format((float)$invoice['total_net'],   2, ',', ' ') ?></th>
                            <th></th>
                            <th class="text-end"><?= number_format((float)$invoice['total_vat'],   2, ',', ' ') ?></th>
                            <th class="text-end"><?= number_format((float)$invoice['total_gross'], 2, ',', ' ') ?> PLN</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <?php if (!empty($invoice['notes'])): ?>
            <div class="card mb-3"><div class="card-body small">
                <strong>Uwagi:</strong><br>
                <?= nl2br(View::e((string)$invoice['notes'])) ?>
            </div></div>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header py-2"><strong>Akcje</strong></div>
            <div class="card-body d-grid gap-2">
                <?php if ($status === 'draft'): ?>
                    <a href="<?= url('club/invoices/' . $id . '/edit') ?>" class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edytuj szkic
                    </a>
                    <form method="POST" action="<?= url('club/invoices/' . $id . '/issue') ?>"
                          onsubmit="return confirm('Wystawić fakturę? Nada zostanie ostateczny numer FV.');">
                        <?= csrf_field() ?>
                        <button class="btn btn-success w-100">
                            <i class="bi bi-check-circle"></i> Wystaw fakturę
                        </button>
                    </form>
                <?php endif; ?>
                <a href="<?= url('club/invoices/' . $id . '/pdf') ?>" target="_blank" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-pdf"></i> Pobierz PDF
                </a>
                <a href="<?= url('club/invoices/' . $id . '/xml') ?>" target="_blank" class="btn btn-outline-secondary">
                    <i class="bi bi-filetype-xml"></i> Podgląd XML KSeF
                </a>
                <?php if (!in_array($status, ['cancelled','sent_ksef','accepted_ksef'], true)): ?>
                    <form method="POST" action="<?= url('club/invoices/' . $id . '/cancel') ?>"
                          onsubmit="return confirm('Anulować fakturę?');">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-danger w-100">
                            <i class="bi bi-x-circle"></i> Anuluj
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header py-2"><strong>KSeF</strong></div>
            <div class="card-body small">
                <?php
                $queueEntry  = $queueEntry  ?? null;
                $ksefEnabled = $ksefEnabled ?? false;
                $upo         = $upo         ?? null;
                $queueStatus = $queueEntry !== null ? (string)$queueEntry['status'] : null;
                $queueBadge  = match($queueStatus) {
                    'queued'      => 'secondary',
                    'signing'     => 'info',
                    'sending'     => 'info',
                    'awaiting_upo'=> 'warning text-dark',
                    'completed'   => 'success',
                    'failed'      => 'danger',
                    'retrying'    => 'warning text-dark',
                    default       => null,
                };
                $queueLabel  = match($queueStatus) {
                    'queued'      => 'W kolejce',
                    'signing'     => 'Podpisywanie XAdES',
                    'sending'     => 'Wysylanie do KSeF',
                    'awaiting_upo'=> 'Oczekiwanie na UPO',
                    'completed'   => 'Zaakceptowana (UPO)',
                    'failed'      => 'Bład - wymaga interwencji',
                    'retrying'    => 'Ponawianie...',
                    default       => null,
                };
                ?>
                <?php if (!$ksefEnabled): ?>
                    <div class="alert alert-secondary py-2 mb-2 small">
                        <i class="bi bi-lock"></i> KSeF nie jest aktywny dla tego klubu.
                    </div>
                <?php endif; ?>

                <?php if ($queueEntry !== null): ?>
                    <div class="mb-2">
                        Status wysyłki:
                        <span class="badge bg-<?= $queueBadge ?? 'secondary' ?>"><?= View::e($queueLabel ?? $queueStatus ?? '-') ?></span>
                        <?php if ((int)$queueEntry['attempts'] > 0): ?>
                            <small class="text-muted">(prob: <?= (int)$queueEntry['attempts'] ?>)</small>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($queueEntry['ksef_reference'])): ?>
                        <div>Reference KSeF: <code><?= View::e((string)$queueEntry['ksef_reference']) ?></code></div>
                    <?php endif; ?>
                    <?php if (!empty($queueEntry['last_error_message']) && in_array($queueStatus, ['failed','retrying'], true)): ?>
                        <div class="alert alert-warning py-1 px-2 mt-2 mb-2 small">
                            <strong>Ostatni blad:</strong>
                            <?= View::e(mb_substr((string)$queueEntry['last_error_message'], 0, 240)) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($queueStatus === 'failed' || $queueStatus === 'retrying'): ?>
                        <form method="POST" action="<?= url('club/invoices/' . $id . '/retry-ksef') ?>" class="d-grid mt-2">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-arrow-clockwise"></i> Ponow probe wysylki
                            </button>
                        </form>
                    <?php endif; ?>
                <?php elseif ($status === 'issued' && $ksefEnabled): ?>
                    <form method="POST" action="<?= url('club/invoices/' . $id . '/send-ksef') ?>" class="d-grid mb-2"
                          onsubmit="return confirm('Zakolejkowac fakture do wyslania do KSeF?');">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-primary">
                            <i class="bi bi-send"></i> Wyslij do KSeF
                        </button>
                    </form>
                <?php elseif ($status === 'draft'): ?>
                    <p class="text-muted mb-1">Faktura jest szkicem. Wystaw, aby otrzymać oficjalny numer.</p>
                <?php endif; ?>

                <?php if (!empty($invoice['ksef_reference_number'])): ?>
                    <div>Numer KSeF: <code><?= View::e((string)$invoice['ksef_reference_number']) ?></code></div>
                <?php endif; ?>
                <?php if ($upo !== null): ?>
                    <div class="mt-2">
                        <a href="<?= url('club/invoices/' . $id . '/upo') ?>" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-file-earmark-zip"></i> Pobierz UPO (XML)
                        </a>
                        <small class="d-block text-muted mt-1">
                            Otrzymano: <?= View::e((string)$upo['acquisition_timestamp']) ?>
                        </small>
                    </div>
                <?php endif; ?>

                <hr class="my-2">
                <div>Status płatności:
                    <span class="badge bg-<?= $invoice['payment_status'] === 'paid' ? 'success' : 'warning text-dark' ?>">
                        <?= View::e((string)$invoice['payment_status']) ?>
                    </span>
                </div>
                <?php if ((float)$invoice['payment_paid_amount'] > 0): ?>
                    <div>Zapłacono: <?= number_format((float)$invoice['payment_paid_amount'], 2, ',', ' ') ?> PLN</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
