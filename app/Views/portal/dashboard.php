<?php use App\Helpers\View; ?>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card p-3 h-100">
            <h6 class="text-muted">Moje składki (<?= date('Y') ?>)</h6>
            <div class="display-6"><?= format_money($totalThisYear) ?></div>
            <a href="<?= url('portal/fees') ?>" class="small stretched-link">Zobacz historię &rarr;</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 h-100">
            <h6 class="text-muted">Badanie lekarskie</h6>
            <?php if ($medical): ?>
                <div><strong>Ważne do:</strong> <?= format_date($medical['valid_until']) ?></div>
                <?php $days = days_until($medical['valid_until']); ?>
                <span class="badge bg-<?= alert_class($days) ?>">
                    <?php if ($days !== null && $days < 0): ?>
                        wygasło (<?= abs($days) ?> dni)
                    <?php elseif ($days !== null): ?>
                        <?= $days ?> dni
                    <?php endif; ?>
                </span>
            <?php else: ?>
                <div class="text-muted small">Brak zarejestrowanych badań.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 h-100">
            <h6 class="text-muted">Moje licencje</h6>
            <?php if (empty($licenses)): ?>
                <div class="text-muted small">Brak licencji.</div>
            <?php else: ?>
                <?php foreach ($licenses as $l): ?>
                    <div class="small">
                        <strong><?= View::e($l['license_type']) ?></strong> —
                        ważna do <?= format_date($l['valid_until']) ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3 mt-3">
    <div class="col-md-6">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-calendar-event"></i> Nadchodzące wydarzenia</h5>
            <?php if (empty($upcoming)): ?>
                <div class="text-muted">Brak zaplanowanych wydarzeń.</div>
            <?php else: ?>
                <?php foreach ($upcoming as $e): ?>
                    <div class="border-bottom py-2">
                        <strong><?= View::e($e['name']) ?></strong>
                        <small class="text-muted d-block"><?= format_datetime($e['event_date']) ?> • <?= View::e($e['location'] ?? '') ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-stopwatch"></i> Nadchodzące treningi</h5>
            <?php if (empty($trainings)): ?>
                <div class="text-muted">Brak zaplanowanych treningów.</div>
            <?php else: ?>
                <?php foreach ($trainings as $t): ?>
                    <div class="border-bottom py-2">
                        <strong><?= View::e($t['name']) ?></strong>
                        <small class="text-muted d-block"><?= format_datetime($t['start_time']) ?> • <?= View::e($t['location'] ?? '') ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
