<?php
use App\Helpers\View;
/**
 * @var array $rows
 * @var string $status
 * @var array $stats
 */
$statusLabel = [
    'pending'   => ['Pending',   'secondary'],
    'qualified' => ['Aktywny',   'success'],
    'paid'      => ['Wyplacony', 'primary'],
    'expired'   => ['Wygasl',    'warning'],
    'cancelled' => ['Anulowany', 'danger'],
];
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-share me-2"></i> Affiliate program</h1>
        <a href="<?= url('admin/platform/referrals/rewards') ?>" class="btn btn-outline-primary">
            <i class="bi bi-gift"></i> Konfiguracja rewardow
        </a>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-people fs-3 text-primary"></i>
                    <div class="text-muted small mt-2">Lacznie referrali</div>
                    <div class="h4 mb-0"><?= (int)$stats['total_referrals'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-percent fs-3 text-success"></i>
                    <div class="text-muted small mt-2">Conversion rate</div>
                    <div class="h4 mb-0"><?= number_format((float)$stats['conversion_pct'], 1) ?>%</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-wallet2 fs-3 text-warning"></i>
                    <div class="text-muted small mt-2">Total reward (PLN/%)</div>
                    <div class="h4 mb-0"><?= number_format((float)$stats['total_rewards'], 2, ',', ' ') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-ticket-perforated fs-3 text-info"></i>
                    <div class="text-muted small mt-2">Aktywne kody</div>
                    <div class="h4 mb-0"><?= (int)$stats['active_codes'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <form method="get" action="" class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 me-1">Status:</label>
                <select name="status" class="form-select form-select-sm w-auto" onchange="this.form.submit();">
                    <option value="">— wszystkie —</option>
                    <?php foreach (['pending','qualified','paid','expired','cancelled'] as $st): ?>
                        <option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>>
                            <?= View::e($statusLabel[$st][0]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="card-body p-0">
            <?php if (empty($rows)): ?>
                <div class="p-4 text-center text-muted">Brak rekordow.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Referrer</th>
                                <th>Polecony</th>
                                <th>Kod</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Reward</th>
                                <th>Qualified at</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <?php
                                $st = (string)$r['status'];
                                [$slabel, $scolor] = $statusLabel[$st] ?? [$st, 'secondary'];
                                ?>
                                <tr>
                                    <td><?= (int)$r['id'] ?></td>
                                    <td><?= View::e($r['referrer_name'] ?? '#' . $r['referrer_club_id']) ?></td>
                                    <td><?= View::e($r['referred_name'] ?? '#' . $r['referred_club_id']) ?></td>
                                    <td><code><?= View::e($r['referral_code']) ?></code></td>
                                    <td><?= View::e($r['referred_at']) ?></td>
                                    <td><span class="badge bg-<?= $scolor ?>"><?= View::e($slabel) ?></span></td>
                                    <td>
                                        <?php if (!empty($r['reward_value'])): ?>
                                            <?= View::e((string)$r['reward_value']) ?>
                                            <small class="text-muted">(<?= View::e((string)$r['reward_type']) ?>)</small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= View::e((string)($r['qualified_at'] ?? '-')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
