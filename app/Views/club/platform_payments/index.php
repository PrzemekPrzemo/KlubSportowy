<?php use App\Helpers\View; ?>
<div class="row">
<div class="col-lg-8">
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-bank2"></i> Stripe Connect — konto klubu</h5>
            <?php if (!$platformKeyReady): ?>
                <div class="alert alert-warning">
                    Klucz Stripe platformy ClubDesk nie jest skonfigurowany. Skontaktuj się z administratorem
                    platformy (Sendormeco Holding) — onboarding chwilowo niedostępny.
                </div>
            <?php endif; ?>

            <?php if (empty($stripeAccount)): ?>
                <p class="text-muted">Konto nie jest skonfigurowane. Aby przyjmować płatności online z auto-podziałem
                    (klub dostaje brutto minus platform fee ClubDesk), musisz przejść onboarding Stripe.</p>
                <form method="POST" action="<?= url('club/platform-payment/onboard') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-primary" <?= $platformKeyReady ? '' : 'disabled' ?>>
                        <i class="bi bi-box-arrow-up-right"></i> Rozpocznij onboarding Stripe
                    </button>
                </form>
            <?php else: ?>
                <table class="table table-sm">
                    <tr><th>External ID</th><td><code><?= View::e($stripeAccount['external_account_id']) ?></code></td></tr>
                    <tr><th>KYC</th><td>
                        <?php
                        $kyc = (string)$stripeAccount['kyc_status'];
                        $bg = match($kyc) {'verified'=>'success','rejected'=>'danger','restricted'=>'warning',default=>'secondary'};
                        ?>
                        <span class="badge bg-<?= $bg ?>"><?= View::e($kyc) ?></span>
                    </td></tr>
                    <tr><th>Charges enabled</th><td><?= (int)$stripeAccount['charges_enabled'] ? '<span class="badge bg-success">tak</span>' : '<span class="badge bg-warning">nie</span>' ?></td></tr>
                    <tr><th>Payouts enabled</th><td><?= (int)$stripeAccount['payouts_enabled'] ? '<span class="badge bg-success">tak</span>' : '<span class="badge bg-warning">nie</span>' ?></td></tr>
                    <tr><th>Onboarding</th><td><?= (int)$stripeAccount['onboarding_complete'] ? '<span class="badge bg-success">kompletne</span>' : '<span class="badge bg-warning">w toku</span>' ?></td></tr>
                    <tr><th>Data ukończenia</th><td><?= View::e((string)($stripeAccount['onboarded_at'] ?? '—')) ?></td></tr>
                </table>

                <form method="POST" action="<?= url('club/platform-payment/onboard') ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-primary">
                        <i class="bi bi-arrow-clockwise"></i> Kontynuuj/odśwież onboarding
                    </button>
                </form>
                <a href="<?= url('club/platform-payment/return') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-repeat"></i> Sprawdź status
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-credit-card"></i> Przelewy24 Marketplace</h5>
            <div class="alert alert-info">
                <?= View::e($p24Notice) ?>
            </div>
        </div>
    </div>
</div>

<div class="col-lg-4">
    <div class="card">
        <div class="card-body">
            <h6><i class="bi bi-info-circle"></i> Jak to działa?</h6>
            <p class="small">Gdy członek opłaca składkę online (Stripe Checkout):</p>
            <ol class="small">
                <li>Kwota brutto trafia od razu na <strong>twoje</strong> konto Stripe Connect.</li>
                <li>ClubDesk automatycznie pobiera platform fee (domyślnie 2%) z transakcji.</li>
                <li>Stripe wykonuje payout do banku klubu wg cyklu Stripe (domyślnie co tydzień).</li>
            </ol>
            <p class="small text-muted mt-3"><a href="<?= url('docs/platform-payments') ?>">Pełna dokumentacja →</a></p>
        </div>
    </div>
</div>
</div>
