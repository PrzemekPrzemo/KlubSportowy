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

            <hr class="my-4">

            <h5><i class="bi bi-shield-lock"></i> Uwierzytelnianie dwuetapowe (2FA)</h5>
            <?php $member = $member ?? \App\Helpers\MemberAuth::member(); $has2fa = !empty($member['totp_enabled']) && !empty($member['totp_confirmed_at']); ?>
            <?php if ($has2fa): ?>
                <div class="alert alert-success small mb-2">
                    <i class="bi bi-check-circle"></i> Aktywne od <?= \App\Helpers\View::e($member['totp_confirmed_at']) ?>
                </div>
                <a href="<?= url('portal/2fa/backup-codes') ?>" class="btn btn-outline-primary btn-sm w-100 mb-2">
                    <i class="bi bi-key"></i> Kody zapasowe
                </a>
                <form method="POST" action="<?= url('portal/2fa/disable') ?>" onsubmit="return confirm('Wyłączyć 2FA? Twoje konto będzie mniej bezpieczne.');">
                    <?= csrf_field() ?>
                    <input type="password" name="password" class="form-control form-control-sm mb-2" placeholder="Wpisz hasło aby wyłączyć" required>
                    <button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-shield-x"></i> Wyłącz 2FA</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning small mb-2">
                    <i class="bi bi-exclamation-triangle"></i>
                    2FA nie jest włączone. Rekomendujemy gdy konto zawiera dane medyczne.
                </div>
                <a href="<?= url('portal/2fa/setup') ?>" class="btn btn-success btn-sm w-100">
                    <i class="bi bi-shield-lock"></i> Włącz 2FA
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Preferencje powiadomień (Faza S.2 RODO opt-out) -->
<div class="card mt-3">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <strong><i class="bi bi-bell me-1"></i> Preferencje powiadomień</strong>
            <small class="d-block text-muted">
                Wybierz które powiadomienia od klubu chcesz otrzymywać i jakim kanałem (email/SMS).
            </small>
        </div>
        <a href="<?= url('portal/notification-prefs') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-gear"></i> Ustaw
        </a>
    </div>
</div>

<!-- Jezyk interfejsu — preferowany jezyk portalu/emaili/PDF -->
<div class="card mt-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <strong><i class="bi bi-translate me-1"></i> <?= __('portal.profile.locale.title') ?></strong>
                <small class="d-block text-muted"><?= __('portal.profile.locale.help') ?></small>
            </div>
        </div>
        <?php $currentLocale = $member['preferred_locale'] ?? \App\Helpers\Translator::getLocale(); ?>
        <form method="POST" action="<?= url('portal/profile/locale') ?>" class="d-flex align-items-center gap-3 flex-wrap">
            <?= csrf_field() ?>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="preferred_locale" id="locale_pl" value="pl"
                    <?= $currentLocale === 'pl' ? 'checked' : '' ?>>
                <label class="form-check-label" for="locale_pl">Polski</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="preferred_locale" id="locale_en" value="en"
                    <?= $currentLocale === 'en' ? 'checked' : '' ?>>
                <label class="form-check-label" for="locale_en">English</label>
            </div>
            <button type="submit" class="btn btn-outline-primary btn-sm ms-auto">
                <i class="bi bi-check2"></i> <?= __('portal.profile.locale.save') ?>
            </button>
        </form>
    </div>
</div>

<!-- Profil publiczny — opt-in widget dla rankingow -->
<div class="card mt-3">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <strong><i class="bi bi-globe2 me-1"></i> Profil publiczny</strong>
            <small class="d-block text-muted">
                <?php $vis = $member['public_profile_visibility'] ?? 'private'; ?>
                <?php if ($vis === 'public'): ?>
                    Aktywny <strong>publicznie</strong> — pod URL: <code>/u/<?= View::e($member['public_profile_slug'] ?? '') ?></code>
                <?php elseif ($vis === 'club_only'): ?>
                    Aktywny <strong>tylko dla klubu</strong>.
                <?php else: ?>
                    Wylaczony. Udostepnij swoje rankingi i osiagniecia przez stabilny URL.
                <?php endif; ?>
            </small>
        </div>
        <a href="<?= url('portal/profile/privacy') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-gear"></i> Ustaw
        </a>
    </div>
</div>
