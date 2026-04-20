<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-ban me-2"></i>Podejrzane / zablokowane IP</h4>
        <small class="text-muted">Adresy z wieloma nieudanymi logowaniami w ostatnich 7 dniach.</small>
    </div>
    <a href="<?= url('admin/security') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Dziennik</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>IP</th>
                    <th>Liczba błędnych prób</th>
                    <th>Ostatnia próba</th>
                    <th>Rate limiter: attempts</th>
                    <th>Blokada do</th>
                    <th>Akcja</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ips)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Brak podejrzanych IP.</td></tr>
                <?php else: ?>
                    <?php foreach ($ips as $r):
                        $isBlocked = !empty($r['rl_blocked_until']) && strtotime($r['rl_blocked_until']) > time();
                    ?>
                    <tr class="<?= $isBlocked ? 'table-danger' : '' ?>">
                        <td><code><?= View::e($r['ip_address']) ?></code></td>
                        <td><strong><?= (int)$r['fail_count'] ?></strong></td>
                        <td><small><?= format_datetime($r['last_fail']) ?></small></td>
                        <td><?= $r['rl_attempts'] !== null ? (int)$r['rl_attempts'] : '—' ?></td>
                        <td>
                            <?php if ($isBlocked): ?>
                                <span class="badge bg-danger"><?= View::e($r['rl_blocked_until']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="<?= url('admin/security/unblock/' . urlencode($r['ip_address'])) ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-success" <?= $r['rl_attempts'] === null ? 'disabled' : '' ?>>
                                    <i class="bi bi-unlock"></i> Odblokuj
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
