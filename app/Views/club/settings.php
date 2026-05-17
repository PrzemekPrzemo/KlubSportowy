<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('club/settings/save') ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Nazwa klubu *</label>
            <input type="text" name="name" value="<?= View::e($club['name'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Skrót</label>
            <input type="text" name="short_name" value="<?= View::e($club['short_name'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">Miasto</label>
            <input type="text" name="city" value="<?= View::e($club['city'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">NIP</label>
            <input type="text" name="nip" value="<?= View::e($club['nip'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">REGON</label>
            <input type="text" name="regon" value="<?= View::e($club['regon'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">E-mail klubu</label>
            <input type="email" name="email" value="<?= View::e($club['email'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">Telefon</label>
            <input type="text" name="phone" value="<?= View::e($club['phone'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">Strona WWW</label>
            <input type="url" name="website" value="<?= View::e($club['website'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Adres</label>
            <textarea name="address" class="form-control" rows="2"><?= View::e($club['address'] ?? '') ?></textarea>
        </div>
        <div class="col-12">
            <label class="form-label"><?= __('club.settings.default_locale.title') ?></label>
            <div class="form-text mb-2"><?= __('club.settings.default_locale.help') ?></div>
            <?php $defLoc = $club['default_locale'] ?? 'pl'; ?>
            <div class="d-flex gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="default_locale" id="club_locale_pl"
                        value="pl" <?= $defLoc === 'pl' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="club_locale_pl">Polski (PL)</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="default_locale" id="club_locale_en"
                        value="en" <?= $defLoc === 'en' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="club_locale_en">English (EN)</label>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-4">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
    </div>
</form>
