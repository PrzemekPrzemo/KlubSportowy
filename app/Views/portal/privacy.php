<?php use App\Helpers\View; ?>
<?php
    $visibility = $member['public_profile_visibility'] ?? 'private';
    $enabled    = $visibility !== 'private';
    $slug       = $member['public_profile_slug'] ?? '';
    $bio        = $member['public_profile_bio'] ?? '';
    $views      = (int)($member['public_profile_view_count'] ?? 0);
    $checked = function (string $key, int $default = 1) use ($member): string {
        $val = $member[$key] ?? $default;
        return $val ? 'checked' : '';
    };
?>
<div class="row g-3">
    <div class="col-md-9">
        <div class="card p-4">
            <h5><i class="bi bi-globe2"></i> Profil publiczny</h5>
            <p class="text-muted small mb-3">
                Domyslnie Twoj profil jest <strong>prywatny</strong>. Wlacz publiczny widget zeby udostepnic
                rankingi i osiagniecia przez stabilny URL (idealny do CV sportowego, social media).
                Publiczny profil nigdy nie pokazuje danych wrazliwych (PESEL, adres, telefon, email).
            </p>

            <?php if (!empty($member['is_anonymized'])): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-shield-exclamation"></i>
                    Twoj profil zostal zanonimizowany (RODO). Publiczny profil nie jest dostepny.
                </div>
            <?php else: ?>

            <form method="POST" action="<?= url('portal/profile/privacy') ?>">
                <?= csrf_field() ?>

                <div class="form-check form-switch mb-3">
                    <input type="checkbox" name="public_profile_enabled" id="ppe"
                           class="form-check-input" <?= $enabled ? 'checked' : '' ?>
                           onchange="document.getElementById('public-settings').style.display = this.checked ? 'block' : 'none';
                                     if(!this.checked){ document.querySelector('[name=public_profile_visibility]').value='private'; }
                                     else if(document.querySelector('[name=public_profile_visibility]').value==='private'){ document.querySelector('[name=public_profile_visibility]').value='public'; }">
                    <label for="ppe" class="form-check-label">Wlacz profil publiczny</label>
                </div>

                <div id="public-settings" style="display: <?= $enabled ? 'block' : 'none' ?>;">
                    <div class="mb-3">
                        <label class="form-label">Widocznosc:</label>
                        <select name="public_profile_visibility" class="form-select">
                            <option value="private"   <?= $visibility === 'private'   ? 'selected' : '' ?>>Tylko ja (wylaczone)</option>
                            <option value="club_only" <?= $visibility === 'club_only' ? 'selected' : '' ?>>Tylko czlonkowie klubu</option>
                            <option value="public"    <?= $visibility === 'public'    ? 'selected' : '' ?>>Publicznie (dostepne przez URL)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">URL profilu:</label>
                        <div class="input-group">
                            <span class="input-group-text"><?= View::e($profileBase ?? '/u/') ?></span>
                            <input type="text" name="public_profile_slug" id="ppslug"
                                   class="form-control"
                                   pattern="^[a-z0-9-]{3,120}$"
                                   maxlength="120"
                                   value="<?= View::e($slug) ?>"
                                   placeholder="jan-kowalski-azs-warszawa"
                                   oninput="document.getElementById('profile-url-preview').textContent = (this.value || '...');">
                        </div>
                        <small class="text-muted">Dozwolone: a-z, 0-9, myslnik. Min 3, max 120 znakow. Pozostaw puste zeby auto-wygenerowac.</small>
                    </div>

                    <h6 class="mt-4">Co pokazywac:</h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="form-check"><input type="checkbox" name="show_avatar"       id="sa"  class="form-check-input" <?= $checked('public_profile_show_avatar') ?>><label for="sa"  class="form-check-label">Zdjecie profilowe</label></div>
                            <div class="form-check"><input type="checkbox" name="show_age"          id="sg"  class="form-check-input" <?= $checked('public_profile_show_age', 0) ?>><label for="sg"  class="form-check-label">Wiek</label></div>
                            <div class="form-check"><input type="checkbox" name="show_birth_year"   id="sby" class="form-check-input" <?= $checked('public_profile_show_birth_year', 0) ?>><label for="sby" class="form-check-label">Rok urodzenia</label></div>
                            <div class="form-check"><input type="checkbox" name="show_sports"       id="ss"  class="form-check-input" <?= $checked('public_profile_show_sports') ?>><label for="ss"  class="form-check-label">Sporty</label></div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check"><input type="checkbox" name="show_rankings"     id="sr"  class="form-check-input" <?= $checked('public_profile_show_rankings') ?>><label for="sr"  class="form-check-label">Rankingi</label></div>
                            <div class="form-check"><input type="checkbox" name="show_achievements" id="sac" class="form-check-input" <?= $checked('public_profile_show_achievements') ?>><label for="sac" class="form-check-label">Osiagniecia (badges)</label></div>
                            <div class="form-check"><input type="checkbox" name="show_tournaments"  id="st"  class="form-check-input" <?= $checked('public_profile_show_tournaments') ?>><label for="st"  class="form-check-label">Turnieje</label></div>
                            <div class="form-check"><input type="checkbox" name="show_club"         id="sc"  class="form-check-input" <?= $checked('public_profile_show_club') ?>><label for="sc"  class="form-check-label">Nazwa klubu</label></div>
                        </div>
                    </div>

                    <div class="mb-3 mt-3">
                        <label class="form-label">Krotki opis "o mnie":</label>
                        <textarea name="public_profile_bio" class="form-control" maxlength="500" rows="4"
                                  placeholder="Np: Pasjonat tenisa od 10 lat, reprezentant klubu na turniejach krajowych."><?= View::e($bio) ?></textarea>
                        <small class="text-muted">Max 500 znakow. Nie zamieszczaj danych kontaktowych ani wrazliwych.</small>
                    </div>

                    <p class="text-muted small mb-1">Profil bedzie dostepny pod:
                        <code><?= View::e($profileBase ?? '/u/') ?><span id="profile-url-preview"><?= View::e($slug !== '' ? $slug : '...') ?></span></code>
                    </p>
                    <p class="text-muted small">Liczba wyswietlen: <strong><?= $views ?></strong></p>
                </div>

                <button class="btn btn-success mt-3"><i class="bi bi-check2"></i> Zapisz ustawienia</button>
                <?php if ($enabled && $slug !== ''): ?>
                    <a href="<?= url('u/' . $slug) ?>" target="_blank" class="btn btn-outline-primary mt-3 ms-2">
                        <i class="bi bi-box-arrow-up-right"></i> Zobacz swoj profil
                    </a>
                <?php endif; ?>
            </form>

            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <h6><i class="bi bi-shield-check"></i> Co NIE jest pokazywane</h6>
            <ul class="small text-muted mb-0">
                <li>PESEL</li>
                <li>Adres zamieszkania</li>
                <li>Telefon</li>
                <li>E-mail</li>
                <li>Dane medyczne</li>
                <li>Numer czlonkowski</li>
            </ul>
        </div>
        <a href="<?= url('portal/profile') ?>" class="btn btn-outline-secondary btn-sm mt-3">
            <i class="bi bi-arrow-left"></i> Wroc do profilu
        </a>
    </div>
</div>
