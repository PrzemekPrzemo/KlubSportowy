<?php use App\Helpers\View; ?>
<div class="container py-4">
    <h2 class="mb-3"><i class="bi bi-broadcast"></i> Live: <?= View::e($club['name'] ?? '') ?></h2>

    <?php if (empty($channels)): ?>
        <div class="alert alert-info">Brak aktywnych transmisji live.</div>
    <?php else: foreach ($channels as $ch): ?>
        <div class="card mb-3">
            <div class="card-body">
                <?= View::partial('live/_widget', ['channel' => $ch['channel'], 'title' => $ch['title']]) ?>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>
