<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-credit-card text-primary me-2"></i>
        Twoja subskrypcja
    </h3>
    <a href="<?= url('club/subscription/addons') ?>" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Dokup zasoby
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<!-- Plan + limity -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 h-100 border-primary">
            <small class="text-muted">Plan bazowy</small>
            <h4 class="mb-0"><?= View::e($subscription['plan_name'] ?? '—') ?></h4>
            <small class="text-muted">
                Ważny do <?= View::e($subscription['valid_until'] ?? '—') ?>
                <span class="badge bg-<?= ($subscription['status'] ?? '') === 'active' ? 'success' : 'warning' ?>">
                    <?= View::e($subscription['status'] ?? '') ?>
                </span>
            </small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 h-100">
            <small class="text-muted">Zawodnicy (zużycie)</small>
            <?php
            $maxM = $limits['max_members'];
            $pctM = $maxM ? min(100, (int)round(100 * $usedMembers / $maxM)) : 0;
            $colorM = $pctM >= 90 ? 'danger' : ($pctM >= 70 ? 'warning' : 'success');
            ?>
            <h4 class="mb-1"><?= (int)$usedMembers ?> / <?= $maxM === null ? '∞' : $maxM ?></h4>
            <?php if ($maxM !== null): ?>
                <div class="progress mb-1" style="height: 6px;">
                    <div class="progress-bar bg-<?= $colorM ?>" style="width: <?= $pctM ?>%"></div>
                </div>
                <small class="text-muted">
                    Plan: <?= (int)$limits['plan_max_members'] ?>
                    <?php if ($limits['addon_members_boost'] > 0): ?>
                        + addony: <strong>+<?= (int)$limits['addon_members_boost'] ?></strong>
                    <?php endif; ?>
                </small>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 h-100">
            <small class="text-muted">Sekcje sportowe (zużycie)</small>
            <?php
            $maxS = $limits['max_sports'];
            $pctS = $maxS ? min(100, (int)round(100 * $usedSports / $maxS)) : 0;
            $colorS = $pctS >= 90 ? 'danger' : ($pctS >= 70 ? 'warning' : 'success');
            ?>
            <h4 class="mb-1"><?= (int)$usedSports ?> / <?= $maxS === null ? '∞' : $maxS ?></h4>
            <?php if ($maxS !== null): ?>
                <div class="progress mb-1" style="height: 6px;">
                    <div class="progress-bar bg-<?= $colorS ?>" style="width: <?= $pctS ?>%"></div>
                </div>
                <small class="text-muted">
                    Plan: <?= (int)$limits['plan_max_sports'] ?>
                    <?php if ($limits['addon_sports_boost'] > 0): ?>
                        + addony: <strong>+<?= (int)$limits['addon_sports_boost'] ?></strong>
                    <?php endif; ?>
                </small>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Aktywne addony -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-puzzle"></i> Aktywne dodatki (addon-y)</strong>
        <?php if ($addonCost > 0): ?>
            <span class="badge bg-primary fs-6">
                Łącznie: <?= number_format($addonCost, 2, ',', ' ') ?> zł / m-c
            </span>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Addon</th>
                    <th>Ilość</th>
                    <th>Boost</th>
                    <th class="text-end">Cena/m-c</th>
                    <th>Aktywny od</th>
                    <th>Wygasa</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($addons)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">
                    Brak aktywnych addonów.
                    <a href="<?= url('club/subscription/addons') ?>">Dokup zasoby</a> aby zwiększyć limity bez zmiany planu.
                </td></tr>
            <?php else: foreach ($addons as $a): ?>
                <tr>
                    <td>
                        <strong><?= View::e($a['addon_name']) ?></strong>
                        <small class="text-muted d-block"><?= View::e($a['description']) ?></small>
                    </td>
                    <td><strong>×<?= (int)$a['quantity'] ?></strong></td>
                    <td>
                        <?php if (!empty($a['boost_field']) && !empty($a['boost_amount'])): ?>
                            <span class="badge bg-success">
                                +<?= (int)$a['boost_amount'] * (int)$a['quantity'] ?>
                                <?= $a['boost_field'] === 'max_members' ? 'zaw.' : 'sek.' ?>
                            </span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td class="text-end font-monospace fw-bold">
                        <?= number_format((float)$a['monthly_price'], 2, ',', ' ') ?> zł
                    </td>
                    <td class="small"><?= View::e($a['valid_from']) ?></td>
                    <td class="small">
                        <?= !empty($a['valid_until']) ? View::e($a['valid_until']) : '<span class="text-muted">bezterminowo</span>' ?>
                    </td>
                    <td>
                        <?php
                        $statusBadge = match ($a['status']) {
                            'active'    => 'success',
                            'cancelled' => 'warning',
                            'suspended' => 'secondary',
                            'expired'   => 'dark',
                            default     => 'secondary',
                        };
                        ?>
                        <span class="badge bg-<?= $statusBadge ?>"><?= View::e($a['status']) ?></span>
                    </td>
                    <td class="text-end">
                        <?php if ($a['status'] === 'active'): ?>
                            <form method="POST" action="<?= url('club/subscription/addons/' . (int)$a['id'] . '/cancel') ?>" class="d-inline"
                                  onsubmit="return confirm('Anulować addon? Zostanie aktywny do końca okresu rozliczeniowego.')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i></button>
                            </form>
                        <?php elseif ($a['status'] === 'cancelled'): ?>
                            <form method="POST" action="<?= url('club/subscription/addons/' . (int)$a['id'] . '/reactivate') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-success" title="Wznów">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i>
    <strong>Jak to działa?</strong> Addon-y rozszerzają limity Twojego planu bez konieczności
    upgrade do wyższego pakietu. Płacisz tylko za to czego potrzebujesz dziś. Możesz anulować w każdej chwili — addon
    pozostanie aktywny do końca opłaconego okresu.
</div>
