<?php use App\Helpers\View; ?>

<?php if (empty($announcements)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>Brak aktualnych ogłoszeń.
</div>
<?php else: ?>
<div class="d-flex flex-column gap-3">
<?php foreach ($announcements as $a):
    $priorityBadge = match($a['priority']) {
        'urgent'    => ['danger',  'PILNE'],
        'important' => ['warning', 'Ważne'],
        default     => ['secondary', 'Info'],
    };
    $icon = match($a['priority']) {
        'urgent'    => 'bi-megaphone-fill text-danger',
        'important' => 'bi-exclamation-triangle-fill text-warning',
        default     => 'bi-info-circle text-secondary',
    };
?>
    <div class="card <?= $a['priority'] === 'urgent' ? 'border-danger' : ($a['priority'] === 'important' ? 'border-warning' : '') ?>">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <h6 class="card-title mb-0">
                    <i class="bi <?= $icon ?> me-2"></i><?= View::e($a['title']) ?>
                </h6>
                <span class="badge bg-<?= $priorityBadge[0] ?> ms-2"><?= $priorityBadge[1] ?></span>
            </div>
            <?php if (!empty($a['sport_name'])): ?>
                <div class="text-muted small mb-2"><i class="bi bi-trophy me-1"></i><?= View::e($a['sport_name']) ?></div>
            <?php endif; ?>
            <p class="card-text"><?= nl2br(View::e($a['content'])) ?></p>
            <div class="text-muted small">
                <?php if (!empty($a['author_name'])): ?><?= View::e($a['author_name']) ?> · <?php endif; ?>
                <?= date('d.m.Y H:i', strtotime($a['created_at'])) ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
