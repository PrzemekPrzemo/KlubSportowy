<?php use App\Helpers\View; ?>

<?php if (empty($belts)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Brak zapisanych awansów pasów / stopni. Dane pojawią się tutaj po zarejestrowaniu egzaminu przez trenera.
</div>
<?php else: ?>

<?php
$polishMonths = ['','stycznia','lutego','marca','kwietnia','maja','czerwca',
                  'lipca','sierpnia','września','października','listopada','grudnia'];
$prevSport = null;
?>

<div class="timeline">
<?php foreach ($belts as $b):
    $sportName = $b['sport_name'] ?? '';
    $date = new \DateTime($b['exam_date']);
?>
    <?php if ($sportName !== $prevSport): $prevSport = $sportName; ?>
    <div class="d-flex align-items-center my-4">
        <div class="text-muted small border-bottom flex-grow-1 me-2"></div>
        <span class="badge bg-secondary fs-6 fw-bold"><?= View::e($sportName) ?></span>
        <div class="text-muted small border-bottom flex-grow-1 ms-2"></div>
    </div>
    <?php endif; ?>

    <div class="d-flex gap-3 mb-3 align-items-start">
        <div class="text-center" style="min-width:70px">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center border border-3"
                 style="width:50px;height:50px;background:<?= View::e($b['belt_color'] ?: '#ddd') ?>;border-color:<?= View::e($b['belt_color'] ?: '#ccc') ?> !important;">
                <span class="fw-bold text-white" style="text-shadow:0 1px 2px rgba(0,0,0,.5);"><?= (int)$b['belt_level'] ?></span>
            </div>
            <div class="text-muted small mt-1">
                <?= $date->format('j') ?> <?= $polishMonths[(int)$date->format('n')] ?><br><?= $date->format('Y') ?>
            </div>
        </div>
        <div class="card flex-grow-1 p-3">
            <div class="fw-semibold">
                <i class="bi bi-award text-warning me-2"></i>
                <?= View::e($b['belt_color'] ?: 'Pas') ?>
                <?php if ($b['belt_level'] > 0): ?>· Poziom <?= (int)$b['belt_level'] ?><?php endif; ?>
            </div>
            <?php if (!empty($b['examiner'])): ?>
                <div class="text-muted small mt-1"><i class="bi bi-person me-1"></i>Egzaminator: <?= View::e($b['examiner']) ?></div>
            <?php endif; ?>
            <?php if (!empty($b['location'])): ?>
                <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i><?= View::e($b['location']) ?></div>
            <?php endif; ?>
            <?php if (!empty($b['notes'])): ?>
                <div class="text-muted small mt-1"><?= View::e($b['notes']) ?></div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
