<?php use App\Helpers\View; ?>

<?php if ($unreadCount > 0): ?>
<div class="alert alert-primary mb-3">
    <i class="bi bi-bell-fill me-2"></i>
    Masz <strong><?= $unreadCount ?></strong> nieprzeczytane <?= $unreadCount === 1 ? 'powiadomienie' : 'powiadomienia' ?>.
</div>
<?php endif; ?>

<?php if (empty($notifications)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>Brak powiadomień.
</div>
<?php else: ?>
<div class="d-flex flex-column gap-2">
<?php foreach ($notifications as $n):
    $isUnread = $n['read_at'] === null;
    $typeIcon = match($n['type']) {
        'medical_expiry'  => 'bi-heart-pulse text-danger',
        'license_expiry'  => 'bi-patch-exclamation text-warning',
        'announcement'    => 'bi-megaphone text-primary',
        'result'          => 'bi-trophy text-warning',
        'belt_promotion'  => 'bi-award text-success',
        default           => 'bi-bell text-secondary',
    };
?>
    <div class="card <?= $isUnread ? 'border-primary bg-primary bg-opacity-5' : '' ?>">
        <div class="card-body py-2 px-3">
            <div class="d-flex justify-content-between align-items-start">
                <div class="d-flex gap-2 align-items-center">
                    <i class="bi <?= $typeIcon ?> fs-5"></i>
                    <div>
                        <div class="<?= $isUnread ? 'fw-semibold' : '' ?>"><?= View::e($n['title']) ?></div>
                        <?php if (!empty($n['body'])): ?>
                            <div class="text-muted small"><?= View::e($n['body']) ?></div>
                        <?php endif; ?>
                        <div class="text-muted small"><?= date('d.m.Y H:i', strtotime($n['created_at'])) ?></div>
                    </div>
                </div>
                <div class="d-flex gap-1 ms-2">
                    <?php if ($isUnread): ?>
                        <form method="POST" action="<?= url('portal/notifications/' . (int)$n['id'] . '/read') ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-primary" title="Oznacz jako przeczytane">
                                <i class="bi bi-check2"></i>
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="badge bg-light text-muted border">Przeczytane</span>
                    <?php endif; ?>
                    <?php if (!empty($n['link'])): ?>
                        <a href="<?= url(ltrim($n['link'], '/')) ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
