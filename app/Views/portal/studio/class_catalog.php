<?php use App\Helpers\Csrf; use App\Helpers\View; ?>

<div class="container-md py-3">
    <h4 class="mb-3">
        <i class="bi bi-grid-3x3-gap text-primary me-2"></i>Katalog klas studio
    </h4>

    <div class="mb-3 d-flex gap-2 flex-wrap">
        <a href="<?= url('portal/studio/my-schedule') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-calendar-check"></i> Moje zajęcia
        </a>
        <a href="<?= url('portal/studio/my-passes') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-card-checklist"></i> Moje karnety
        </a>
        <a href="<?= url('portal/studio/buy-pass') ?>" class="btn btn-success btn-sm">
            <i class="bi bi-cart-plus"></i> Kup karnet
        </a>
    </div>

    <?php
    $sportLabels = ['yoga' => 'Joga', 'fitness' => 'Fitness', 'pilates' => 'Pilates'];
    $sportIcons  = ['yoga' => 'bi-flower1', 'fitness' => 'bi-activity', 'pilates' => 'bi-heart-pulse'];

    $hasAny = false;
    foreach ($catalog as $sport => $matrix) {
        foreach ($matrix as $items) { if (!empty($items)) { $hasAny = true; break 2; } }
    }
    ?>

    <?php if (!$hasAny): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Klub nie udostępnił jeszcze żadnych klas studio.
        </div>
    <?php endif; ?>

    <?php foreach ($catalog as $sport => $matrix): ?>
        <?php
        $hasItems = false;
        foreach ($matrix as $items) { if (!empty($items)) { $hasItems = true; break; } }
        if (!$hasItems) continue;
        ?>
        <h5 class="mt-4 mb-2">
            <i class="bi <?= $sportIcons[$sport] ?? 'bi-circle' ?>"></i>
            <?= View::e($sportLabels[$sport] ?? ucfirst($sport)) ?>
        </h5>
        <div class="row g-3">
            <?php for ($dow = 1; $dow <= 7; $dow++): ?>
                <?php if (empty($matrix[$dow])) continue; ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light">
                            <strong><?= View::e($dayLabels[$dow] ?? '?') ?></strong>
                            <small class="text-muted">— <?= View::e($nextDates[$dow]) ?></small>
                        </div>
                        <div class="card-body p-2">
                            <?php foreach ($matrix[$dow] as $c): ?>
                                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                    <div>
                                        <strong><?= View::e($c['name']) ?></strong>
                                        <small class="d-block text-muted">
                                            <?= View::e(substr((string)$c['time_start'], 0, 5)) ?>
                                            · <?= (int)$c['duration_min'] ?>min
                                            · <?= View::e($c['difficulty']) ?>
                                            <?php if (!empty($c['room'])): ?>
                                                · sala <?= View::e($c['room']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <form action="<?= url('portal/studio/book') ?>" method="POST" class="m-0">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="schedule_id" value="<?= (int)$c['id'] ?>">
                                        <input type="hidden" name="class_date" value="<?= View::e($nextDates[$dow]) ?>">
                                        <button class="btn btn-sm btn-primary">
                                            <i class="bi bi-plus-circle"></i> Zapisz
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    <?php endforeach; ?>
</div>
