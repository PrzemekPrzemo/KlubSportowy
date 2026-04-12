<?php use App\Helpers\View; ?>
<div class="container py-4" style="max-width: 600px;">
    <h4 class="mb-3"><i class="bi bi-building"></i> Wybierz klub</h4>
    <p class="text-muted">Twoje konto jest powiązane z wieloma klubami. Wybierz, do którego chcesz się zalogować.</p>

    <?php if (empty($clubs)): ?>
        <div class="alert alert-warning">Brak aktywnych klubów dla tego konta.</div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($clubs as $club): ?>
            <form method="POST" action="<?= url('portal/club-select/' . (int)$club['id']) ?>" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?= View::e($club['name']) ?></strong>
                        <?php if (!empty($club['city'])): ?>
                            <br><small class="text-muted"><?= View::e($club['city']) ?></small>
                        <?php endif; ?>
                        <?php if (!empty($club['sport_badges'])): ?>
                            <br>
                            <?php foreach ($club['sport_badges'] as $badge): ?>
                                <span class="badge bg-secondary"><?= View::e($badge) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <i class="bi bi-chevron-right"></i>
                </button>
            </form>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="mt-3">
        <a href="<?= url('portal/logout') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-box-arrow-left"></i> Wyloguj
        </a>
    </div>
</div>
