<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-arrow-repeat text-primary me-2"></i>Skonfiguruj cykliczną składkę</h3>
    <a href="<?= url('portal/subscriptions') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-x"></i> Anuluj
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="card p-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h5><?= View::e($feeRate['name']) ?></h5>
            <p class="text-muted small mb-1">
                Stawka bazowa: <strong><?= format_money((float)$feeRate['amount']) ?></strong>
            </p>
            <?php if (!empty($feeRate['description'])): ?>
                <p class="small"><?= View::e($feeRate['description']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($activeProviders)): ?>
        <div class="alert alert-warning">
            <strong>Brak aktywnych dostawców płatności online</strong> w tym klubie.
            Skonfiguruj Stripe lub Przelewy24 w panelu administratora klubu.
        </div>
    <?php else: ?>
        <form method="POST" action="<?= url('portal/subscriptions/setup/' . (int)$feeRate['id']) ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Dostawca płatności</label>
                <?php foreach ($activeProviders as $i => $p): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="provider"
                               id="provider_<?= $p ?>" value="<?= $p ?>"
                               <?= $i === 0 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="provider_<?= $p ?>">
                            <?php if ($p === 'stripe'): ?>
                                <strong>Stripe</strong> — karta płatnicza, auto-charge co miesiąc
                            <?php elseif ($p === 'przelewy24'): ?>
                                <strong>Przelewy24</strong> — BLIK / przelew, automatyczne pobieranie
                            <?php endif; ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Częstotliwość</label>
                <select name="billing_period" class="form-select">
                    <?php foreach ($periods as $key => $meta): ?>
                        <option value="<?= $key ?>" <?= $key === 'monthly' ? 'selected' : '' ?>>
                            <?= View::e($meta['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="alert alert-info small">
                <i class="bi bi-info-circle"></i>
                Po kliknięciu „Konfiguruj” zostaniesz przekierowany do bezpiecznej strony
                płatności. Tam podasz dane karty/BLIK — system zapamięta je do
                automatycznego pobierania kolejnych składek. Możesz w każdej chwili
                anulować subskrypcję z poziomu portalu.
            </div>

            <button type="submit" class="btn btn-success btn-lg">
                <i class="bi bi-lock"></i> Konfiguruj automatyczną płatność
            </button>
        </form>
    <?php endif; ?>
</div>
