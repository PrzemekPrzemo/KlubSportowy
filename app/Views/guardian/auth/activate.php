<?php
use App\Helpers\Csrf;
use App\Helpers\View;
?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= View::e($error) ?></div>
<?php endif; ?>

<?php if (empty($token) || empty($guardian)): ?>
    <div class="text-center mt-3">
        <a href="<?= View::e(url('guardian/login')) ?>" class="btn btn-outline-secondary">Wroc do logowania</a>
    </div>
<?php else: ?>
    <p class="text-muted small">
        Aktywujesz konto dla <strong><?= View::e($guardian['email']) ?></strong>.
        Ustaw haslo, aby uzyskac dostep do portalu opiekuna.
    </p>
    <form method="post" action="<?= View::e(url('guardian/activate/' . $token)) ?>" novalidate>
        <?= Csrf::field() ?>
        <div class="mb-3">
            <label class="form-label" for="gp-pass">Nowe haslo (min. 8 znakow)</label>
            <input type="password" name="password" id="gp-pass" class="form-control form-control-lg" required minlength="8">
        </div>
        <div class="mb-3">
            <label class="form-label" for="gp-pass2">Powtorz haslo</label>
            <input type="password" name="password_confirm" id="gp-pass2" class="form-control form-control-lg" required minlength="8">
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="accept_terms" id="gp-terms" required>
            <label class="form-check-label small" for="gp-terms">
                Akceptuje regulamin i polityke prywatnosci portalu opiekuna.
            </label>
        </div>
        <button type="submit" class="btn btn-success btn-lg w-100">
            <i class="bi bi-shield-check"></i> Aktywuj konto
        </button>
    </form>
<?php endif; ?>
