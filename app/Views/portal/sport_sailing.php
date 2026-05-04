<?php use App\Helpers\View; ?>

<?php if (!empty($myBoats)): ?>
<div class="card p-3 mb-3">
    <h6 class="mb-3"><i class="bi bi-water me-1"></i>Moje łodzie / jachty</h6>
    <?php foreach ($myBoats as $b): ?>
        <div class="border-bottom py-2 d-flex justify-content-between align-items-center">
            <div>
                <strong><?= View::e($b['name']) ?></strong>
                <?php if ($b['class']): ?><small class="text-muted"> (<?= View::e($b['class']) ?>)</small><?php endif; ?>
                <div class="small text-muted">Rola: <?= View::e($b['role']) ?><?= $b['is_permanent'] ? ' · Stała załoga' : '' ?></div>
            </div>
            <?php if ($b['insurance_expiry']):
                $d = (int)floor((strtotime($b['insurance_expiry']) - time()) / 86400);
            ?>
            <span class="badge bg-<?= $d < 0 ? 'danger' : ($d <= 30 ? 'warning' : 'success') ?>">
                OC: <?= $b['insurance_expiry'] ?>
            </span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Nie jesteś przypisany/a do żadnej łodzi.</div>
<?php endif; ?>

<?php if (!empty($recentRaces)): ?>
<div class="card p-3">
    <h6 class="mb-3"><i class="bi bi-trophy me-1"></i>Historia regat</h6>
    <?php foreach ($recentRaces as $r): ?>
        <div class="border-bottom py-2">
            <strong><?= View::e($r['name']) ?></strong>
            <div class="small text-muted">
                <?= View::e($r['race_date']) ?>
                · <?= ucfirst($r['race_type']) ?>
                <?= $r['distance_nm'] ? ' · ' . $r['distance_nm'] . ' Mm' : '' ?>
                <?= !empty($r['location']) ? ' · ' . View::e($r['location']) : '' ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
