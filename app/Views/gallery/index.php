<?php use App\Helpers\View; ?>
<div class="mb-3 text-end">
    <a href="<?= url('gallery/create') ?>" class="btn btn-success">
        <i class="bi bi-plus"></i> Nowy album
    </a>
</div>

<?php if (empty($pagination['data'])): ?>
    <div class="card p-4 text-center text-muted">Brak albumow w galerii.</div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-3">
        <?php foreach ($pagination['data'] as $a): ?>
            <div class="col">
                <a href="<?= url('gallery/' . (int)$a['id']) ?>" class="text-decoration-none">
                    <div class="card h-100">
                        <?php if (!empty($a['cover_path'])): ?>
                            <img src="<?= url(View::e($a['cover_path'])) ?>"
                                 class="card-img-top"
                                 alt="<?= View::e($a['title']) ?>"
                                 style="height:180px; object-fit:cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center"
                                 style="height:180px;">
                                <i class="bi bi-images text-muted" style="font-size:3rem;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h6 class="card-title mb-1">
                                <?= View::e($a['title']) ?>
                            </h6>
                            <div class="d-flex gap-1 flex-wrap mb-1">
                                <span class="badge bg-secondary">
                                    <i class="bi bi-image"></i> <?= (int)$a['photo_count'] ?>
                                </span>
                                <?php if ($a['is_public']): ?>
                                    <span class="badge bg-success">publiczny</span>
                                <?php endif; ?>
                                <?php if (!empty($a['sport_name'])): ?>
                                    <span class="badge bg-info"><?= View::e($a['sport_name']) ?></span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">
                                <?= format_datetime($a['created_at']) ?>
                                <?php if (!empty($a['author_name'])): ?>
                                    &bull; <?= View::e($a['author_name']) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($pagination['last_page'] > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($p = 1; $p <= $pagination['last_page']; $p++): ?>
                    <li class="page-item <?= $p === $pagination['current_page'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('gallery?page=' . $p) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>
