<?php use App\Helpers\View; ?>

<?php if (!empty($currentBelt)): ?>
<?php
$beltColors = [
    'white'  => ['bg' => '#f8f9fa', 'text' => '#333', 'border' => '#dee2e6'],
    'blue'   => ['bg' => '#0d6efd', 'text' => '#fff', 'border' => '#0d6efd'],
    'purple' => ['bg' => '#6f42c1', 'text' => '#fff', 'border' => '#6f42c1'],
    'brown'  => ['bg' => '#8B4513', 'text' => '#fff', 'border' => '#8B4513'],
    'black'  => ['bg' => '#212529', 'text' => '#fff', 'border' => '#212529'],
];
$bc = $beltColors[$currentBelt['belt_color']] ?? $beltColors['white'];
$beltLabels = ['white'=>'Biały','blue'=>'Niebieski','purple'=>'Fioletowy','brown'=>'Brązowy','black'=>'Czarny'];
?>
<div class="card p-3 mb-3" style="border-left: 5px solid <?= $bc['border'] ?>">
    <h6 class="text-muted small mb-2"><i class="bi bi-award me-1"></i>Aktualny pas BJJ</h6>
    <div class="d-flex align-items-center gap-3">
        <span class="badge fs-5 px-3 py-2 border"
              style="background:<?= $bc['bg'] ?>;color:<?= $bc['text'] ?>;border-color:<?= $bc['border'] ?>!important">
            <?= View::e($beltLabels[$currentBelt['belt_color']] ?? $currentBelt['belt_color']) ?>
        </span>
        <div>
            <?php for ($i = 0; $i < 4; $i++): ?>
                <i class="bi bi-<?= $i < (int)$currentBelt['stripes'] ? 'dash-square-fill text-warning' : 'dash-square text-muted' ?> fs-5"></i>
            <?php endfor; ?>
            <div class="small text-muted mt-1">
                <?= strtoupper($currentBelt['gi'] ?? '') ?> •
                Egzamin: <?= View::e($currentBelt['exam_date'] ?? '') ?>
                <?php if (!empty($currentBelt['examiner'])): ?> • <?= View::e($currentBelt['examiner']) ?><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Brak przypisanego pasa BJJ.</div>
<?php endif; ?>

<div class="card p-3">
    <h6 class="mb-3"><i class="bi bi-trophy me-1"></i>Ostatnie wyniki walk</h6>
    <?php if (empty($recentResults)): ?>
        <div class="text-muted small">Brak wyników.</div>
    <?php else: ?>
        <?php
        $total = count($recentResults);
        $wins  = count(array_filter($recentResults, fn($r) => $r['result'] === 'win'));
        $losses= count(array_filter($recentResults, fn($r) => $r['result'] === 'loss'));
        ?>
        <div class="d-flex gap-2 mb-3">
            <span class="badge bg-success"><?= $wins ?> W</span>
            <span class="badge bg-danger"><?= $losses ?> L</span>
            <span class="text-muted small align-self-center">(<?= $total ?> walk)</span>
        </div>
        <?php $badges=['win'=>'success','loss'=>'danger','draw'=>'secondary','dq'=>'warning']; ?>
        <?php foreach ($recentResults as $r): ?>
            <div class="border-bottom py-2 d-flex justify-content-between align-items-center">
                <div>
                    <strong><?= View::e($r['event_name']) ?></strong>
                    <?php if (!empty($r['opponent'])): ?><small class="text-muted"> vs <?= View::e($r['opponent']) ?></small><?php endif; ?>
                    <div class="text-muted small"><?= View::e($r['event_date']) ?> • <?= strtoupper($r['gi'] ?? '') ?></div>
                </div>
                <div class="d-flex gap-1 align-items-center">
                    <span class="badge bg-<?= $badges[$r['result']] ?? 'secondary' ?>"><?= strtoupper($r['result']) ?></span>
                    <?php if (!empty($r['method'])): ?><small class="text-muted"><?= View::e($r['method']) ?></small><?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
