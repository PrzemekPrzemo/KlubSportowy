<?php use App\Helpers\View; ?>
<h5 class="mb-3">Wybierz klub</h5>
<p class="text-muted small">Twoje konto ma dostęp do kilku klubów. Wybierz, w którym chcesz pracować.</p>

<?php if (empty($clubs)): ?>
    <div class="alert alert-warning">Nie masz przypisanych klubów.</div>
<?php else: ?>
    <div class="list-group">
        <?php foreach ($clubs as $c): ?>
            <form method="POST" action="<?= url('club-select/' . (int)$c['club_id']) ?>" class="m-0">
                <?= csrf_field() ?>
                <button type="submit" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?= View::e($c['name']) ?></strong>
                        <?php if (!empty($c['city'])): ?>
                            <small class="text-muted d-block"><?= View::e($c['city']) ?></small>
                        <?php endif; ?>
                    </div>
                    <span class="badge bg-secondary"><?= View::e($c['role']) ?></span>
                </button>
            </form>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<hr class="my-4">
<p class="text-center mb-0"><a href="<?= url('auth/logout') ?>">Wyloguj</a></p>
