<?php use App\Helpers\View; ?>

<div class="pub-hero mb-4">
    <div class="container">
        <h1 class="mb-1"><i class="bi bi-people"></i> Odkryj zawodnikow</h1>
        <p class="mb-0 opacity-75">Publiczne profile zawodnikow z calej platformy. Sortowane wg popularnosci.</p>
    </div>
</div>

<div class="container pb-5">
    <?php if (empty($profiles)): ?>
        <div class="card p-4 text-center text-muted">
            <i class="bi bi-inbox" style="font-size:2rem;"></i>
            <p class="mb-0 mt-2">Brak publicznych profili.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($profiles as $p): ?>
                <div class="col-md-4 col-lg-3">
                    <a href="<?= url('u/' . $p['public_profile_slug']) ?>" class="text-decoration-none text-dark">
                        <div class="card p-3 text-center h-100">
                            <?php if (!empty($p['public_profile_show_avatar']) && !empty($p['photo_path'])): ?>
                                <img src="<?= url((string)$p['photo_path']) ?>" alt=""
                                     style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin:0 auto 0.5rem;">
                            <?php else: ?>
                                <div style="width:80px;height:80px;border-radius:50%;background:#eee;display:flex;align-items:center;justify-content:center;margin:0 auto 0.5rem;font-size:1.6rem;color:#999;">
                                    <i class="bi bi-person"></i>
                                </div>
                            <?php endif; ?>
                            <div class="fw-bold"><?= View::e(trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''))) ?></div>
                            <?php if (!empty($p['public_profile_show_club']) && !empty($p['club_name'])): ?>
                                <div class="small text-muted"><?= View::e($p['club_name']) ?></div>
                            <?php endif; ?>
                            <div class="small text-muted mt-2">
                                <i class="bi bi-eye"></i> <?= (int)$p['public_profile_view_count'] ?>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (($lastPage ?? 1) > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
