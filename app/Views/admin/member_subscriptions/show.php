<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Subskrypcja #<?= (int)$sub['id'] ?></h3>
        <p class="text-muted mb-0 small">
            Provider: <strong><?= View::e($sub['gateway_provider']) ?></strong>
            <?php if ($sub['external_subscription_id']): ?>
                · external ID: <code><?= View::e($sub['external_subscription_id']) ?></code>
            <?php endif; ?>
        </p>
    </div>
    <a href="<?= url('admin/member-subscriptions') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Lista
    </a>
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

<div class="row g-3">
    <div class="col-md-5">
        <div class="card p-3">
            <h5>Szczegóły</h5>
            <table class="table table-sm mb-0">
                <tr><td>Status</td><td><strong><?= View::e($statuses[$sub['status']] ?? $sub['status']) ?></strong></td></tr>
                <tr><td>Kwota</td><td><strong><?= format_money((float)$sub['amount']) ?></strong> <?= View::e($sub['currency']) ?></td></tr>
                <tr><td>Cykl</td><td><?= View::e($periods[$sub['billing_period']]['label'] ?? $sub['billing_period']) ?></td></tr>
                <tr><td>Klub ID</td><td><?= (int)$sub['club_id'] ?></td></tr>
                <tr><td>Member ID</td><td><?= (int)$sub['member_id'] ?></td></tr>
                <tr><td>Fee rate ID</td><td><?= (int)$sub['fee_rate_id'] ?></td></tr>
                <tr><td>External customer</td><td><code><?= View::e($sub['external_customer_id'] ?? '—') ?></code></td></tr>
                <tr><td>Current period start</td><td><?= View::e($sub['current_period_start'] ?? '—') ?></td></tr>
                <tr><td>Current period end</td><td><?= View::e($sub['current_period_end'] ?? '—') ?></td></tr>
                <tr><td>Next charge at</td><td><?= View::e($sub['next_charge_at'] ?? '—') ?></td></tr>
                <tr><td>Failed charges</td><td><?= (int)$sub['failed_charges_count'] ?></td></tr>
                <tr><td>Created at</td><td><?= View::e($sub['created_at']) ?></td></tr>
                <?php if ($sub['cancelled_at']): ?>
                    <tr><td>Cancelled at</td><td><?= View::e($sub['cancelled_at']) ?></td></tr>
                    <tr><td>Cancel reason</td><td><?= View::e($sub['cancellation_reason'] ?? '') ?></td></tr>
                <?php endif; ?>
            </table>
        </div>

        <?php if (in_array($sub['status'], ['active','past_due','paused'], true)): ?>
            <div class="card p-3 mt-3">
                <h6>Akcje administracyjne</h6>
                <form method="POST" action="<?= url('admin/member-subscriptions/' . (int)$sub['id'] . '/force-charge') ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-warning btn-sm"
                            onclick="return confirm('Wykonać ponowną próbę pobrania składki?')">
                        <i class="bi bi-arrow-clockwise"></i> Force charge
                    </button>
                </form>
                <form method="POST" action="<?= url('admin/member-subscriptions/' . (int)$sub['id'] . '/cancel') ?>" class="mt-2">
                    <?= csrf_field() ?>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="at_period_end" id="atPeriodEnd" value="1" checked>
                        <label class="form-check-label" for="atPeriodEnd">Anuluj na koniec okresu</label>
                    </div>
                    <input type="text" name="reason" class="form-control form-control-sm mt-1" placeholder="Powód anulowania">
                    <button type="submit" class="btn btn-outline-danger btn-sm mt-2"
                            onclick="return confirm('Anulować subskrypcję?')">
                        <i class="bi bi-x-circle"></i> Anuluj subskrypcję
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-md-7">
        <div class="card p-3">
            <h5>Historia chargeów (<?= count($charges) ?>)</h5>
            <?php if (empty($charges)): ?>
                <p class="text-muted small mb-0">Brak chargeów — subskrypcja nie była jeszcze rozliczona.</p>
            <?php else: ?>
                <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Kwota</th>
                            <th>Status</th>
                            <th>External</th>
                            <th>Okres</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($charges as $c): ?>
                        <tr>
                            <td><?= View::e($c['charged_at'] ?? $c['created_at']) ?></td>
                            <td><?= format_money((float)$c['amount']) ?></td>
                            <td>
                                <?php $sc = match ($c['status']) {
                                    'succeeded' => 'bg-success', 'failed' => 'bg-danger',
                                    'refunded' => 'bg-info', default => 'bg-secondary',
                                }; ?>
                                <span class="badge <?= $sc ?>"><?= View::e($c['status']) ?></span>
                                <?php if (!empty($c['failure_reason'])): ?>
                                    <small class="d-block text-danger"><?= View::e($c['failure_reason']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><code><?= View::e($c['external_invoice_id'] ?? $c['external_payment_id'] ?? '—') ?></code></small>
                            </td>
                            <td>
                                <small>
                                <?php if ($c['period_start'] && $c['period_end']): ?>
                                    <?= View::e(date('Y-m-d', strtotime($c['period_start']))) ?>
                                    →
                                    <?= View::e(date('Y-m-d', strtotime($c['period_end']))) ?>
                                <?php endif; ?>
                                </small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
