<?php
use App\Helpers\Csrf;
use App\Helpers\View;
?>
<p class="text-muted small">
    Podaj e-mail powiazany z kontem opiekuna. Wyslemy link do resetu hasla.
</p>
<form method="post" action="<?= View::e(url('guardian/forgot-password')) ?>" novalidate>
    <?= Csrf::field() ?>
    <div class="mb-3">
        <label class="form-label" for="gp-email">E-mail</label>
        <input type="email" name="email" id="gp-email" class="form-control form-control-lg" required autofocus>
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-100">Wyslij link</button>
</form>
<div class="text-center mt-3 small">
    <a href="<?= View::e(url('guardian/login')) ?>">Wroc do logowania</a>
</div>
