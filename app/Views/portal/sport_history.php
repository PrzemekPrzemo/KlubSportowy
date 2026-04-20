<?php use App\Helpers\View; ?>
<h4 class="mb-4"><i class="bi bi-clock-history me-2"></i>Moja historia sportowa</h4>

<?php if (empty($history)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Brak danych historycznych. Twoje pasy i wyniki zawodów pojawią się tutaj.
</div>
<?php else: ?>

<?php
$sportColors = [
    'judo'      => 'primary', 'karate'   => 'danger',   'taekwondo' => 'warning',
    'aikido'    => 'info',    'sambo'    => 'dark',     'swimming'  => 'info',
    'wrestling' => 'secondary', 'athletics' => 'success',
];
$polishMonths = ['','stycznia','lutego','marca','kwietnia','maja','czerwca',
                  'lipca','sierpnia','września','października','listopada','grudnia'];

$prevYear = null;
?>

<div class="timeline">
<?php foreach ($history as $item):
    $date = new \DateTime($item['event_date']);
    $year = $date->format('Y');
    $sportColor = $sportColors[$item['sport_key']] ?? 'secondary';
    $medal = '';
    if ($item['type'] === 'result' && isset($item['placement'])) {
        $medal = match((int)$item['placement']) { 1 => ' 🥇', 2 => ' 🥈', 3 => ' 🥉', default => '' };
    }
?>
    <?php if ($year !== $prevYear): $prevYear = $year; ?>
    <div class="d-flex align-items-center my-3">
        <div class="text-muted small border-bottom flex-grow-1 me-2"></div>
        <span class="badge bg-light text-dark border fw-bold fs-6"><?= $year ?></span>
        <div class="text-muted small border-bottom flex-grow-1 ms-2"></div>
    </div>
    <?php endif; ?>

    <div class="d-flex gap-3 mb-3">
        <div class="text-center" style="min-width:60px">
            <span class="badge bg-<?= $sportColor ?> rounded-pill"><?= View::e($item['sport_name']) ?></span>
            <div class="text-muted small mt-1">
                <?= $date->format('j') ?> <?= $polishMonths[(int)$date->format('n')] ?>
            </div>
        </div>
        <div class="flex-grow-1 card p-2">
            <?php if ($item['type'] === 'belt'): ?>
                <div class="fw-bold"><i class="bi bi-award text-warning me-1"></i>
                    Nadanie pasa: <?= View::e($item['detail']) ?></div>
                <?php if (!empty($item['examiner'])): ?>
                    <div class="text-muted small">Egzaminator: <?= View::e($item['examiner']) ?></div>
                <?php endif; ?>
                <?php if (!empty($item['location'])): ?>
                    <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i><?= View::e($item['location']) ?></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="fw-bold"><i class="bi bi-trophy text-warning me-1"></i>
                    <?= View::e($item['detail']) ?><?= $medal ?></div>
                <?php if (!empty($item['placement'])): ?>
                    <div class="text-muted small">Miejsce: <?= View::e($item['placement']) ?>.</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
