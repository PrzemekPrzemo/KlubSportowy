<?php use App\Helpers\View; ?>

<?php $statusColors = ['draft' => 'secondary', 'issued' => 'primary', 'paid' => 'success', 'cancelled' => 'dark']; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-receipt me-2"></i>Faktura <?= View::e($invoice['number']) ?>
        <span class="badge bg-<?= $statusColors[$invoice['status']] ?? 'secondary' ?> ms-2"><?= View::e($invoice['status']) ?></span>
    </h4>
    <a href="<?= url('admin/invoices') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Powrót</a>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5>Dane klubu</h5>
                <dl class="row small mb-3">
                    <dt class="col-sm-3">Nazwa</dt>
                    <dd class="col-sm-9"><?= View::e($invoice['club_name'] ?? '—') ?></dd>
                    <dt class="col-sm-3">NIP</dt>
                    <dd class="col-sm-9"><?= View::e($invoice['club_nip'] ?? '—') ?></dd>
                    <dt class="col-sm-3">Miasto</dt>
                    <dd class="col-sm-9"><?= View::e($invoice['club_city'] ?? '—') ?></dd>
                    <dt class="col-sm-3">Adres</dt>
                    <dd class="col-sm-9"><?= View::e($invoice['club_address'] ?? '—') ?></dd>
                </dl>

                <h5>Dane faktury</h5>
                <dl class="row small mb-0">
                    <dt class="col-sm-3">Numer</dt>
                    <dd class="col-sm-9"><code><?= View::e($invoice['number']) ?></code></dd>
                    <dt class="col-sm-3">Wystawiono</dt>
                    <dd class="col-sm-9"><?= View::e($invoice['issue_date']) ?></dd>
                    <dt class="col-sm-3">Termin</dt>
                    <dd class="col-sm-9"><?= View::e($invoice['due_date']) ?></dd>
                    <dt class="col-sm-3">Kwota</dt>
                    <dd class="col-sm-9"><strong class="fs-5"><?= number_format((float)$invoice['total'], 2, ',', ' ') ?> zł</strong></dd>
                    <dt class="col-sm-3">Zapłacono</dt>
                    <dd class="col-sm-9"><?= $invoice['paid_at'] ? format_datetime($invoice['paid_at']) : '—' ?></dd>
                    <?php if (!empty($invoice['notes'])): ?>
                        <dt class="col-sm-3">Notatki</dt>
                        <dd class="col-sm-9"><?= nl2br(View::e($invoice['notes'])) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header py-2"><strong>Akcje</strong></div>
            <div class="card-body d-grid gap-2">
                <?php if ($invoice['status'] !== 'paid' && $invoice['status'] !== 'cancelled'): ?>
                    <form method="POST" action="<?= url('admin/invoices/' . (int)$invoice['id'] . '/pay') ?>"
                          onsubmit="return confirm('Oznaczyć fakturę jako zapłaconą?');">
                        <?= csrf_field() ?>
                        <button class="btn btn-success w-100"><i class="bi bi-check-circle"></i> Oznacz jako zapłaconą</button>
                    </form>
                <?php endif; ?>
                <?php if ($invoice['status'] !== 'cancelled'): ?>
                    <form method="POST" action="<?= url('admin/invoices/' . (int)$invoice['id'] . '/cancel') ?>"
                          onsubmit="return confirm('Anulować fakturę? Operacji nie można cofnąć.');">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-danger w-100"><i class="bi bi-x-circle"></i> Anuluj fakturę</button>
                    </form>
                <?php endif; ?>
                <a href="<?= url('admin/clubs/' . (int)$invoice['club_id'] . '/analytics') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-graph-up"></i> Analityka klubu
                </a>
            </div>
        </div>
    </div>
</div>
