<?php
use App\Helpers\View;
use App\Sports\Mma\Models\MmaResultModel;
use App\Sports\Mma\Models\MmaFighterModel;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-person-bounding-box text-primary me-2"></i>MMA</h3>
        <p class="text-muted mb-0">Mój rekord i sposób wygranych</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<?php if ($fighter): ?>
<div class="card shadow-sm mb-4 border-primary">
    <div class="card-body">
        <h5 class="card-title"><?= View::e($fighter['nickname'] ?? 'Profil MMA') ?></h5>
        <div class="d-flex gap-3 flex-wrap">
            <span class="badge bg-secondary"><?= View::e(MmaFighterModel::$STANCES[$fighter['stance']] ?? $fighter['stance']) ?></span>
            <?php if ($fighter['weight_class']): ?><span class="badge bg-dark"><?= View::e($fighter['weight_class']) ?></span><?php endif; ?>
            <span class="badge bg-info"><?= View::e(MmaFighterModel::$STYLES[$fighter['primary_style']] ?? $fighter['primary_style']) ?></span>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center"><div class="card-body"><div class="text-muted small">Wygrane</div><h2 class="mb-0 text-success"><?= (int)($record['W'] ?? 0) ?></h2></div></div></div>
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center"><div class="card-body"><div class="text-muted small">Porażki</div><h2 class="mb-0 text-danger"><?= (int)($record['L'] ?? 0) ?></h2></div></div></div>
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center"><div class="card-body"><div class="text-muted small">Remisy / NC</div><h2 class="mb-0"><?= (int)($record['D'] ?? 0) + (int)($record['NC'] ?? 0) ?></h2></div></div></div>
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center bg-dark text-white"><div class="card-body"><div class="small opacity-75">Rekord</div><h2 class="mb-0 font-monospace"><?= (int)($record['W'] ?? 0) ?>-<?= (int)($record['L'] ?? 0) ?>-<?= (int)($record['D'] ?? 0) ?></h2></div></div></div>
</div>

<?php
// Pelna kartoteka MMA (po promocji PARTIAL -> FULL)
echo View::partial('portal/sport/mma/my_record', [
    'mmaCard' => $mmaCard ?? null,
]);
?>

<?php if (!empty($winMethods)): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header"><i class="bi bi-lightning-fill me-1"></i> Sposoby wygranych</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Sposób</th><th class="text-end">Liczba</th></tr></thead>
            <tbody>
            <?php foreach ($winMethods as $w): ?>
                <tr>
                    <td><?= View::e(MmaResultModel::$METHODS[$w['method']] ?? $w['method']) ?></td>
                    <td class="text-end"><span class="badge bg-success"><?= (int)$w['cnt'] ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header">Moje walki</div>
    <div class="card-body p-0">
        <?php if (empty($myResults)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak walk.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Data</th><th>Przeciwnik</th><th>Wynik</th><th>Sposób</th><th>R/T</th></tr></thead>
                    <tbody>
                    <?php foreach ($myResults as $r):
                        $ri = MmaResultModel::$RESULTS[$r['result']] ?? ['label' => '—', 'class' => 'secondary'];
                    ?>
                        <tr>
                            <td class="small"><?= View::e($r['event_date']) ?></td>
                            <td><?= View::e($r['opponent_name'] ?? '—') ?></td>
                            <td><?php if ($r['result']): ?><span class="badge bg-<?= $ri['class'] ?>"><?= View::e($ri['label']) ?></span><?php endif; ?></td>
                            <td><small><?= View::e(MmaResultModel::$METHODS[$r['method']] ?? '—') ?></small></td>
                            <td class="small font-monospace"><?php if ($r['round']): ?>R<?= (int)$r['round'] ?><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
