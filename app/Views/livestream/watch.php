<?php use App\Helpers\View; ?>
<div class="row g-3">
    <div class="col-md-9">
        <div class="card p-3">
            <div class="ratio ratio-16x9">
                <?= $stream['embed_code'] ?? '<p class="text-muted p-5">Brak embed kodu</p>' ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <h5><?= View::e($stream['title']) ?></h5>
            <span class="badge bg-<?= $stream['status'] === 'na_zywo' ? 'danger' : 'secondary' ?> mb-2">
                <?= $stream['status'] === 'na_zywo' ? '● NA ŻYWO' : View::e($stream['status']) ?>
            </span>
            <dl class="small mb-0">
                <dt>Platforma</dt><dd><?= View::e($stream['platform']) ?></dd>
                <?php if ($stream['scheduled_at']): ?>
                    <dt>Zaplanowana</dt><dd><?= format_datetime($stream['scheduled_at']) ?></dd>
                <?php endif; ?>
                <?php if ($stream['started_at']): ?>
                    <dt>Rozpoczęto</dt><dd><?= format_datetime($stream['started_at']) ?></dd>
                <?php endif; ?>
                <?php if ($stream['viewers_peak']): ?>
                    <dt>Widzowie (peak)</dt><dd><?= (int)$stream['viewers_peak'] ?></dd>
                <?php endif; ?>
            </dl>
            <hr>
            <a href="<?= View::e($stream['stream_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                <i class="bi bi-box-arrow-up-right"></i> Otwórz na <?= View::e($stream['platform']) ?>
            </a>
        </div>
    </div>
</div>
