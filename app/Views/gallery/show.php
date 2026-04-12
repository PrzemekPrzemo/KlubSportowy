<?php use App\Helpers\View; ?>

<style>
/* Pure CSS lightbox using :target */
.lightbox-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,.85);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}
.lightbox-overlay:target {
    display: flex;
}
.lightbox-overlay img {
    max-width: 90vw;
    max-height: 85vh;
    object-fit: contain;
    border-radius: .5rem;
    box-shadow: 0 0 40px rgba(0,0,0,.5);
}
.lightbox-overlay .lightbox-close {
    position: absolute;
    top: 1rem; right: 1.5rem;
    color: #fff;
    font-size: 2rem;
    text-decoration: none;
    line-height: 1;
    z-index: 10000;
}
.lightbox-overlay .lightbox-close:hover {
    color: #f38ba8;
}
.lightbox-overlay .lightbox-caption {
    position: absolute;
    bottom: 1rem;
    left: 50%;
    transform: translateX(-50%);
    color: #fff;
    background: rgba(0,0,0,.6);
    padding: .4rem 1rem;
    border-radius: .3rem;
    font-size: .9rem;
    max-width: 80vw;
    text-align: center;
}
</style>

<div class="mb-3 d-flex justify-content-between align-items-center">
    <div>
        <a href="<?= url('gallery') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Powrot do galerii
        </a>
        <?php if ($album['is_public']): ?>
            <span class="badge bg-success ms-2">publiczny</span>
        <?php endif; ?>
    </div>
    <form method="POST" action="<?= url('gallery/' . (int)$album['id'] . '/delete') ?>"
          onsubmit="return confirm('Usunac album i wszystkie zdjecia?')">
        <?= csrf_field() ?>
        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> Usun album</button>
    </form>
</div>

<?php if (!empty($album['description'])): ?>
    <div class="card p-3 mb-3">
        <p class="mb-0"><?= nl2br(View::e($album['description'])) ?></p>
    </div>
<?php endif; ?>

<?php if (empty($album['photos'])): ?>
    <div class="card p-4 text-center text-muted mb-3">Brak zdjec w tym albumie. Dodaj pierwsze zdjecie ponizej.</div>
<?php else: ?>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-2 mb-4">
        <?php foreach ($album['photos'] as $i => $photo): ?>
            <div class="col">
                <div class="card h-100">
                    <a href="#lightbox-<?= (int)$photo['id'] ?>">
                        <img src="<?= url(View::e($photo['file_path'])) ?>"
                             class="card-img-top"
                             alt="<?= View::e($photo['caption'] ?? '') ?>"
                             style="height:150px; object-fit:cover; cursor:pointer;">
                    </a>
                    <div class="card-body p-2">
                        <?php if (!empty($photo['caption'])): ?>
                            <small class="d-block text-muted"><?= View::e($photo['caption']) ?></small>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <small class="text-muted"><?= format_datetime($photo['created_at']) ?></small>
                            <form method="POST" action="<?= url('gallery/photo/' . (int)$photo['id'] . '/delete') ?>"
                                  onsubmit="return confirm('Usunac zdjecie?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger p-0 px-1" title="Usun">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lightbox overlay -->
            <div id="lightbox-<?= (int)$photo['id'] ?>" class="lightbox-overlay">
                <a href="#" class="lightbox-close">&times;</a>
                <img src="<?= url(View::e($photo['file_path'])) ?>" alt="<?= View::e($photo['caption'] ?? '') ?>">
                <?php if (!empty($photo['caption'])): ?>
                    <div class="lightbox-caption"><?= View::e($photo['caption']) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Upload form -->
<div class="card p-4">
    <h5 class="mb-3"><i class="bi bi-cloud-arrow-up"></i> Dodaj zdjecie</h5>
    <form method="POST" action="<?= url('gallery/' . (int)$album['id'] . '/upload') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Plik (jpg, png, gif, webp) *</label>
                <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Podpis</label>
                <input type="text" name="caption" class="form-control" maxlength="255" placeholder="Opcjonalny podpis do zdjecia">
            </div>
        </div>
        <div class="mt-3">
            <button class="btn btn-primary"><i class="bi bi-upload"></i> Wgraj</button>
        </div>
    </form>
</div>
