<?php use App\Helpers\View; ?>

<?php if (!empty($seasons)): ?>
<div class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <a href="<?= url('portal/results') ?>" class="btn btn-sm <?= $season === null ? 'btn-primary' : 'btn-outline-secondary' ?>">Wszystkie sezony</a>
    <?php foreach ($seasons as $s): ?>
        <a href="?season=<?= urlencode($s) ?>" class="btn btn-sm <?= $season === $s ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <?= View::e($s) ?>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($rankings)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Brak wyników i rankingów<?= $season ? ' dla sezonu ' . View::e($season) : '' ?>.
    Wyniki pojawiają się tutaj po zarejestrowaniu przez trenera.
</div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($rankings as $r):
    $pos = (int)$r['ranking_position'];
    $medalClass = match($pos) { 1 => 'border-warning bg-warning bg-opacity-10', 2 => 'border-secondary bg-secondary bg-opacity-10', 3 => 'border-danger bg-danger bg-opacity-10', default => '' };
    $medal = match($pos) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' };
?>
    <div class="col-md-6">
        <div class="card <?= $medalClass ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="fw-semibold"><?= View::e($r['sport_key']) ?></div>
                        <div class="text-muted small">Sezon: <?= View::e($r['season']) ?></div>
                    </div>
                    <div class="text-end">
                        <?php if ($pos > 0): ?>
                            <div class="fs-4"><?= $medal ?> <?= $pos ?>. miejsce</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="fw-bold fs-5 text-primary"><?= (int)$r['ranking_points'] ?></div>
                        <div class="text-muted small">Punkty</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-5"><?= (int)$r['competitions_count'] ?></div>
                        <div class="text-muted small">Starty</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-5 text-success"><?= (int)$r['wins'] ?></div>
                        <div class="text-muted small">Zwycięstwa</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
