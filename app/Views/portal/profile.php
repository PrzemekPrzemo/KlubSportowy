<?php use App\Helpers\View; ?>
<div class="row g-3">
    <div class="col-md-8">
        <div class="card p-4">
            <h5>Dane kontaktowe</h5>
            <form method="POST" action="<?= url('portal/profile/update') ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Imię</label>
                        <input type="text" value="<?= View::e($member['first_name']) ?>" class="form-control" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nazwisko</label>
                        <input type="text" value="<?= View::e($member['last_name']) ?>" class="form-control" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">E-mail</label>
                        <input type="email" value="<?= View::e($member['email'] ?? '') ?>" class="form-control" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="phone" value="<?= View::e($member['phone'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ulica</label>
                        <input type="text" name="address_street" value="<?= View::e($member['address_street'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Miasto</label>
                        <input type="text" name="address_city" value="<?= View::e($member['address_city'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Kod</label>
                        <input type="text" name="address_postal" value="<?= View::e($member['address_postal'] ?? '') ?>" class="form-control">
                    </div>
                </div>
                <button class="btn btn-success mt-3"><i class="bi bi-check2"></i> Zapisz</button>
                <small class="text-muted ms-2">Zmianę imienia, nazwiska i e-maila możesz zgłosić zarządowi klubu.</small>
            </form>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4">
            <h5>Zmiana hasła</h5>
            <form method="POST" action="<?= url('portal/password') ?>">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <label class="form-label small">Obecne hasło</label>
                    <input type="password" name="old_password" class="form-control form-control-sm" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Nowe hasło</label>
                    <input type="password" name="new_password" class="form-control form-control-sm" required minlength="8">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Powtórz nowe hasło</label>
                    <input type="password" name="new_password2" class="form-control form-control-sm" required minlength="8">
                </div>
                <button class="btn btn-warning w-100 btn-sm"><i class="bi bi-key"></i> Zmień hasło</button>
            </form>
        </div>
    </div>
</div>
