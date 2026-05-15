<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1"><i class="bi bi-arrow-repeat text-primary me-2"></i>Subskrypcje cykliczne</h3>
        <p class="text-muted mb-0 small">Automatyczne opłacanie składek klubowych — bez logowania co miesiąc</p>
    </div>
    <a href="<?= url('portal/dues') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót do należności
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('info')): ?>
    <div class="alert alert-info"><?= View::e($flash) ?></div>
<?php endif; ?>

<?php if (empty($subs)): ?>
    <div class="card p-4 text-center">
        <h5 class="text-muted">Nie masz żadnych aktywnych subskrypcji</h5>
        <p class="text-muted small">
            Możesz włączyć automatyczne pobieranie składek — wybierz interesującą Cię opłatę
            na stronie <a href="<?= url('portal/fees') ?>">Moich składek</a> i kliknij
            „Skonfiguruj cykliczną płatność”.
        </p>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Opłata</th>
                        <th>Cykl</th>
                        <th>Kwota</th>
                        <th>Dostawca</th>
                        <th>Status</th>
                        <th>Następne pobranie</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($subs as $s): ?>
                    <tr>
                        <td><?= View::e($s['fee_name'] ?? '—') ?></td>
                        <td><?= View::e($periods[$s['billing_period']]['label'] ?? $s['billing_period']) ?></td>
                        <td><strong><?= format_money((float)$s['amount']) ?></strong></td>
                        <td>
                            <?php if ($s['gateway_provider'] === 'stripe'): ?>
                                <span class="badge bg-info">Stripe</span>
                            <?php elseif ($s['gateway_provider'] === 'przelewy24'): ?>
                                <span class="badge bg-secondary">Przelewy24</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $statusClass = match ($s['status']) {
                                'active'        => 'bg-success',
                                'paused'        => 'bg-warning',
                                'past_due'      => 'bg-danger',
                                'cancelled', 'expired' => 'bg-secondary',
                                default         => 'bg-light text-dark',
                            };
                            ?>
                            <span class="badge <?= $statusClass ?>"><?= View::e($statuses[$s['status']] ?? $s['status']) ?></span>
                            <?php if ((int)$s['failed_charges_count'] > 0): ?>
                                <small class="text-danger d-block">⚠ Nieudane: <?= (int)$s['failed_charges_count'] ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($s['next_charge_at']): ?>
                                <?= View::e(date('Y-m-d', strtotime($s['next_charge_at']))) ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($s['status'] === 'active' && $s['gateway_provider'] === 'stripe'): ?>
                                <form method="POST" action="<?= url('portal/subscriptions/' . (int)$s['id'] . '/pause') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-warning"
                                            onclick="return confirm('Wstrzymać subskrypcję?')">
                                        <i class="bi bi-pause"></i> Wstrzymaj
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if ($s['status'] === 'paused'): ?>
                                <form method="POST" action="<?= url('portal/subscriptions/' . (int)$s['id'] . '/resume') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-play"></i> Wznów
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if (in_array($s['status'], ['active','paused','past_due'], true)): ?>
                                <form method="POST" action="<?= url('portal/subscriptions/' . (int)$s['id'] . '/cancel') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Anulować subskrypcję? Składka będzie pobrana jeszcze raz do końca okresu.')">
                                        <i class="bi bi-x-circle"></i> Anuluj
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
