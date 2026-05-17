<?php use App\Helpers\View; ?>
<div class="mb-3">
    <p class="text-muted mb-0">Konta merchanta klubów u dostawców split payments (Stripe Connect Express, P24 Marketplace).</p>
</div>
<div class="card">
<table class="table table-hover mb-0">
    <thead class="table-light"><tr>
        <th>Klub</th>
        <th>Provider</th>
        <th>External ID</th>
        <th>KYC</th>
        <th>Charges</th>
        <th>Payouts</th>
        <th>Onboarding</th>
        <th>Data onboard.</th>
    </tr></thead>
    <tbody>
    <?php foreach ($clubs as $row): ?>
        <tr>
            <td><strong><?= View::e($row['name']) ?></strong></td>
            <td><?php if ($row['provider']): ?>
                <span class="badge bg-info"><?= View::e($row['provider']) ?></span>
                <?php else: ?><span class="text-muted">— brak konta —</span><?php endif; ?>
            </td>
            <td><code><?= View::e((string)($row['external_account_id'] ?? '')) ?></code></td>
            <td><?php
                $kyc = (string)($row['kyc_status'] ?? '');
                $bg  = match($kyc) {'verified'=>'success','rejected'=>'danger','restricted'=>'warning',default=>'secondary'};
                ?><?php if ($kyc): ?><span class="badge bg-<?= $bg ?>"><?= View::e($kyc) ?></span><?php endif; ?>
            </td>
            <td><?= ($row['charges_enabled'] ?? 0) ? '<span class="badge bg-success">tak</span>' : '<span class="text-muted">nie</span>' ?></td>
            <td><?= ($row['payouts_enabled'] ?? 0) ? '<span class="badge bg-success">tak</span>' : '<span class="text-muted">nie</span>' ?></td>
            <td><?= ($row['onboarding_complete'] ?? 0) ? '<span class="badge bg-success">kompletne</span>' : '<span class="badge bg-warning">w toku</span>' ?></td>
            <td class="text-muted small"><?= View::e((string)($row['onboarded_at'] ?? '—')) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
