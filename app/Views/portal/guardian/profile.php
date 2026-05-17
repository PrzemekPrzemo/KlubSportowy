<?php
use App\Helpers\Csrf;
use App\Helpers\View;
?>
<h2 class="h5 mb-3">Moj profil opiekuna</h2>

<form method="post" action="<?= View::e(url('portal/guardian/profile')) ?>" class="gp-card">
    <?= Csrf::field() ?>
    <h3 class="h6">Dane kontaktowe</h3>
    <div class="mb-2">
        <label class="form-label small">E-mail (login)</label>
        <input type="email" class="form-control" value="<?= View::e($guardian['email']) ?>" disabled>
    </div>
    <div class="row g-2">
        <div class="col">
            <label class="form-label small">Imie</label>
            <input type="text" name="first_name" class="form-control" maxlength="200" value="<?= View::e($guardian['first_name'] ?? '') ?>">
        </div>
        <div class="col">
            <label class="form-label small">Nazwisko</label>
            <input type="text" name="last_name" class="form-control" maxlength="200" value="<?= View::e($guardian['last_name'] ?? '') ?>">
        </div>
    </div>
    <div class="mt-2">
        <label class="form-label small">Telefon</label>
        <input type="tel" name="phone" class="form-control" maxlength="20" value="<?= View::e($guardian['phone'] ?? '') ?>">
    </div>
    <div class="mt-2">
        <label class="form-label small">Jezyk komunikacji</label>
        <select name="preferred_locale" class="form-select">
            <option value="">— domyslny klubu —</option>
            <option value="pl" <?= ($guardian['preferred_locale'] ?? '') === 'pl' ? 'selected' : '' ?>>Polski</option>
            <option value="en" <?= ($guardian['preferred_locale'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary w-100 mt-3">Zapisz</button>
</form>

<form method="post" action="<?= View::e(url('portal/guardian/password')) ?>" class="gp-card">
    <?= Csrf::field() ?>
    <h3 class="h6">Zmiana hasla</h3>
    <div class="mb-2">
        <label class="form-label small">Aktualne haslo</label>
        <input type="password" name="current_password" class="form-control" required>
    </div>
    <div class="mb-2">
        <label class="form-label small">Nowe haslo (min 8 znakow)</label>
        <input type="password" name="new_password" class="form-control" minlength="8" required>
    </div>
    <div class="mb-2">
        <label class="form-label small">Powtorz nowe haslo</label>
        <input type="password" name="new_password_confirm" class="form-control" minlength="8" required>
    </div>
    <button type="submit" class="btn btn-outline-warning w-100">Zmien haslo</button>
</form>
