<?php use App\Helpers\View; ?>
<?php if (empty($upcoming)): ?>
    <div class="card p-4 text-center text-muted">Brak nadchodzących wydarzeń.</div>
<?php else: ?>
    <?php foreach ($upcoming as $e): ?>
        <div class="card p-3 mb-2">
            <div class="d-flex justify-content-between">
                <div>
                    <h5 class="mb-1">
                        <span class="badge bg-info"><?= View::e($e['type'] ?? 'wydarzenie') ?></span>
                        <?= View::e($e['name']) ?>
                    </h5>
                    <small class="text-muted">
                        <i class="bi bi-calendar"></i> <?= format_datetime($e['event_date']) ?>
                        <?php if (!empty($e['location'])): ?>
                            • <i class="bi bi-geo-alt"></i> <?= View::e($e['location']) ?>
                        <?php endif; ?>
                        <?php if (!empty($e['sport_name'])): ?>
                            • sport: <?= View::e($e['sport_name']) ?>
                        <?php endif; ?>
                    </small>
                </div>
            </div>
            <?php if (!empty($e['description'])): ?>
                <div class="small mt-2"><?= nl2br(View::e($e['description'])) ?></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
