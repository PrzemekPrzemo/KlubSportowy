<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1"><i class="bi bi-arrow-repeat text-primary me-2"></i>Subskrypcje cykliczne składek</h3>
        <p class="text-muted mb-0 small">Automatyczne pobieranie składek członkowskich — Stripe + Przelewy24</p>
    </div>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('warning')): ?>
    <div class="alert alert-warning"><?= View::e($flash) ?></div>
<?php endif; ?>

<!-- Status filters -->
<div class="card p-3 mb-3">
    <div class="btn-group" role="group">
        <a href="<?= url('admin/member-subscriptions') ?>"
           class="btn btn-sm <?= empty($status) ? 'btn-primary' : 'btn-outline-primary' ?>">
            Wszystkie (<?= (int)($counts['all'] ?? 0) ?>)
        </a>
        <?php foreach ($statuses as $st => $label): ?>
            <a href="<?= url('admin/member-subscriptions?status=' . $st) ?>"
               class="btn btn-sm <?= $status === $st ? 'btn-primary' : 'btn-outline-primary' ?>">
                <?= View::e($label) ?> (<?= (int)($counts[$st] ?? 0) ?>)
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Członek</th>
                    <th>Opłata</th>
                    <th>Cykl</th>
                    <th>Kwota</th>
                    <th>Dostawca</th>
                    <th>Status</th>
                    <th>Następne</th>
                    <th>Ostatnia płatność</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($subs)): ?>
                <tr><td colspan="10" class="text-center text-muted p-4">Brak subskrypcji w tym filtrze.</td></tr>
            <?php endif; ?>
            <?php foreach ($subs as $s): ?>
                <tr>
                    <td><?= (int)$s['id'] ?></td>
                    <td>
                        <strong><?= View::e(trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? ''))) ?></strong>
                        <small class="text-muted d-block"><?= View::e($s['email'] ?? '') ?></small>
                    </td>
                    <td><?= View::e($s['fee_name'] ?? '—') ?></td>
                    <td><?= View::e($periods[$s['billing_period']]['label'] ?? $s['billing_period']) ?></td>
                    <td><strong><?= format_money((float)$s['amount']) ?></strong></td>
                    <td>
                        <?php if ($s['gateway_provider'] === 'stripe'): ?>
                            <span class="badge bg-info">Stripe</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">P24</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php $cls = match ($s['status']) {
                            'active' => 'bg-success', 'paused' => 'bg-warning',
                            'past_due' => 'bg-danger', 'cancelled', 'expired' => 'bg-secondary',
                            default => 'bg-light text-dark',
                        }; ?>
                        <span class="badge <?= $cls ?>"><?= View::e($statuses[$s['status']] ?? $s['status']) ?></span>
                    </td>
                    <td>
                        <?= $s['next_charge_at'] ? View::e(date('Y-m-d', strtotime($s['next_charge_at']))) : '—' ?>
                    </td>
                    <td>
                        <?php if ($s['last_payment_at']): ?>
                            <?= View::e(date('Y-m-d', strtotime($s['last_payment_at']))) ?>
                            <small class="d-block text-<?= $s['last_payment_status'] === 'succeeded' ? 'success' : 'danger' ?>">
                                <?= View::e($s['last_payment_status'] ?? '') ?>
                            </small>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= url('admin/member-subscriptions/' . (int)$s['id']) ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
