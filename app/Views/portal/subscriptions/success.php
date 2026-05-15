<?php use App\Helpers\View; ?>

<div class="card p-4 text-center">
    <div class="display-1 text-success mb-3"><i class="bi bi-check-circle"></i></div>
    <h3>Subskrypcja skonfigurowana!</h3>

    <?php if ($sub && $sub['status'] === 'active'): ?>
        <p class="text-muted">
            Twoja składka <strong><?= View::e($sub['fee_name'] ?? '') ?></strong> będzie
            pobierana automatycznie w wysokości <strong><?= format_money((float)$sub['amount']) ?></strong>.
        </p>
        <?php if (!empty($sub['next_charge_at'])): ?>
            <p class="small text-muted">
                Następne pobranie: <strong><?= View::e(date('Y-m-d', strtotime($sub['next_charge_at']))) ?></strong>
            </p>
        <?php endif; ?>
    <?php elseif ($sub && $sub['status'] === 'pending_setup'): ?>
        <p class="text-muted">
            Płatność jest przetwarzana. Po potwierdzeniu od dostawcy subskrypcja zostanie
            uruchomiona — może to potrwać kilka sekund.
        </p>
    <?php else: ?>
        <p class="text-muted">
            Status: <?= View::e($sub['status'] ?? 'nieznany') ?>.
            Jeśli wystąpił problem, sprawdź listę subskrypcji.
        </p>
    <?php endif; ?>

    <div class="mt-3">
        <a href="<?= url('portal/subscriptions') ?>" class="btn btn-primary">
            <i class="bi bi-list"></i> Moje subskrypcje
        </a>
        <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-house"></i> Dashboard
        </a>
    </div>
</div>
