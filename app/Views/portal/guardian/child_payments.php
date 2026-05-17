<?php
use App\Helpers\Csrf;
use App\Helpers\View;
?>
<a href="<?= View::e(url('portal/guardian/child/' . (int)$member['id'])) ?>" class="small text-decoration-none">
    <i class="bi bi-arrow-left"></i> Wroc do profilu
</a>

<h2 class="h5 mt-2">Platnosci — <?= View::e(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')) ?></h2>

<div class="gp-card">
    <h3 class="h6">Zaleglosci</h3>
    <?php if (empty($unpaid)): ?>
        <p class="text-success mb-0"><i class="bi bi-check-circle"></i> Brak zaleglosci.</p>
    <?php else: ?>
        <?php foreach ($unpaid as $d): ?>
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                <div>
                    <div class="fw-semibold"><?= View::e($d['title'] ?? 'Naleznosc') ?></div>
                    <div class="small text-muted">do: <?= View::e($d['due_date'] ?? '—') ?></div>
                </div>
                <div class="text-end">
                    <div class="fw-semibold"><?= number_format((float)($d['amount'] ?? 0), 2, ',', ' ') ?> zl</div>
                    <form method="post" action="<?= View::e(url('portal/guardian/child/' . (int)$member['id'] . '/pay')) ?>" class="d-inline">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="due_id" value="<?= (int)$d['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-success mt-1">
                            <i class="bi bi-credit-card"></i> Zaplac
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="gp-card">
    <h3 class="h6">Historia wplat</h3>
    <?php if (empty($paid)): ?>
        <p class="text-muted small mb-0">Brak historii.</p>
    <?php else: ?>
        <?php foreach ($paid as $p): ?>
            <div class="d-flex justify-content-between border-bottom py-2">
                <div>
                    <div class="small fw-semibold"><?= View::e($p['title'] ?? $p['description'] ?? 'Wplata') ?></div>
                    <div class="text-muted small"><?= View::e($p['paid_date'] ?? $p['created_at'] ?? '—') ?></div>
                </div>
                <div class="fw-semibold"><?= number_format((float)($p['amount'] ?? 0), 2, ',', ' ') ?> zl</div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
