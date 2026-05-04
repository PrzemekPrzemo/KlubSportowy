<?php
use App\Helpers\View;
use App\Sports\Kickboxing\Models\KickboxingBeltModel;
use App\Sports\Kickboxing\Models\KickboxingResultModel;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-trophy text-primary me-2"></i>Kickboxing</h3>
        <p class="text-muted mb-0">Mój pas i rekord walk</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<?php if ($currentBelt):
    $bi = KickboxingBeltModel::$BELTS[$currentBelt['belt_color']] ?? ['label' => $currentBelt['belt_color'], 'color' => '#aaa'];
?>
<div class="card shadow-sm mb-4" style="border-color:<?= $bi['color'] ?>;border-width:3px;">
    <div class="card-body text-center">
        <div class="text-muted small">Aktualny pas</div>
        <div class="mx-auto my-2" style="width:120px;height:25px;background:<?= $bi['color'] ?>;border:2px solid #333;border-radius:4px;"></div>
        <h3 class="mb-0"><?= View::e($bi['label']) ?></h3>
        <?php if ((int)$currentBelt['dan'] > 0): ?><span class="badge bg-dark mt-2"><?= (int)$currentBelt['dan'] ?> dan</span><?php endif; ?>
        <small class="text-muted d-block mt-2">Egzamin: <?= View::e($currentBelt['exam_date']) ?></small>
    </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center"><div class="card-body"><div class="text-muted small">Zwycięstwa</div><h2 class="mb-0 text-success"><?= (int)($record['W'] ?? 0) ?></h2></div></div></div>
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center"><div class="card-body"><div class="text-muted small">Porażki</div><h2 class="mb-0 text-danger"><?= (int)($record['L'] ?? 0) ?></h2></div></div></div>
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center"><div class="card-body"><div class="text-muted small">Remisy</div><h2 class="mb-0"><?= (int)($record['D'] ?? 0) ?></h2></div></div></div>
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center bg-dark text-white"><div class="card-body"><div class="small opacity-75">Rekord</div><h2 class="mb-0 font-monospace"><?= (int)($record['W'] ?? 0) ?>-<?= (int)($record['L'] ?? 0) ?>-<?= (int)($record['D'] ?? 0) ?></h2></div></div></div>
</div>

<div class="card shadow-sm">
    <div class="card-header">Moje walki</div>
    <div class="card-body p-0">
        <?php if (empty($recent)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak walk.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Data</th><th>Styl</th><th>Przeciwnik</th><th>Wynik</th><th>Sposób</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent as $r):
                        $ri = KickboxingResultModel::$RESULTS[$r['result']] ?? ['label' => '—', 'class' => 'secondary'];
                    ?>
                        <tr>
                            <td class="small"><?= View::e($r['event_date']) ?></td>
                            <td><small><?= View::e(KickboxingResultModel::$STYLES[$r['style']] ?? $r['style']) ?></small></td>
                            <td><?= View::e($r['opponent_name'] ?? '—') ?></td>
                            <td><?php if ($r['result']): ?><span class="badge bg-<?= $ri['class'] ?>"><?= View::e($ri['label']) ?></span><?php endif; ?></td>
                            <td><small><?= View::e(KickboxingResultModel::$METHODS[$r['method']] ?? '—') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
