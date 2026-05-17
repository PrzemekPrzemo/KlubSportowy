<?php
use App\Helpers\Csrf;
use App\Helpers\View;
?>
<form method="post" action="<?= View::e(url('guardian/login')) ?>" novalidate>
    <?= Csrf::field() ?>
    <div class="mb-3">
        <label class="form-label" for="gp-email">E-mail opiekuna</label>
        <input type="email" name="email" id="gp-email" class="form-control form-control-lg" required autofocus value="<?= View::e(old('email')) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label" for="gp-pass">Haslo</label>
        <input type="password" name="password" id="gp-pass" class="form-control form-control-lg" required>
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-100">Zaloguj sie</button>
</form>
<div class="text-center mt-3 small">
    <a href="<?= View::e(url('guardian/forgot-password')) ?>">Nie pamietasz hasla?</a>
    &middot;
    <a href="<?= View::e(url('help/parent')) ?>" target="_blank" rel="noopener">Pomoc</a>
</div>
<div class="text-center mt-3 small text-muted">
    Nie masz konta? Klub musi Cie zaprosic — sprawdz e-mail z linkiem aktywacyjnym.
</div>
