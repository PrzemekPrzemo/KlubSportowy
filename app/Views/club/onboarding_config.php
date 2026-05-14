<?php
use App\Helpers\View;
$consents = is_array($config['custom_consents'] ?? null) ? $config['custom_consents'] : [];
$fields   = is_array($config['custom_fields'] ?? null)   ? $config['custom_fields']   : [];
?>
<h1 class="h3 mb-3"><i class="bi bi-person-plus"></i> Konfiguracja onboardingu czlonka</h1>
<p class="text-muted">Okresl jak wyglada formularz dodawania nowego zawodnika dla Twojego klubu. Wymagane pola, zgody RODO, limity wieku oraz auto-przypisania.</p>

<form method="POST" action="<?= url('club/onboarding-config/save') ?>">
    <?= csrf_field() ?>

    <!-- Sekcja: pola wymagane -->
    <div class="card mb-3">
        <div class="card-header"><strong>Pola wymagane w formularzu</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4 form-check">
                    <input type="checkbox" id="require_pesel" name="require_pesel" value="1" class="form-check-input" <?= !empty($config['require_pesel']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="require_pesel">PESEL wymagany</label>
                </div>
                <div class="col-md-4 form-check">
                    <input type="checkbox" id="require_address" name="require_address" value="1" class="form-check-input" <?= !empty($config['require_address']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="require_address">Adres wymagany</label>
                </div>
                <div class="col-md-4 form-check">
                    <input type="checkbox" id="require_emergency_contact" name="require_emergency_contact" value="1" class="form-check-input" <?= !empty($config['require_emergency_contact']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="require_emergency_contact">Kontakt awaryjny</label>
                </div>
                <div class="col-md-4 form-check">
                    <input type="checkbox" id="require_medical_consent" name="require_medical_consent" value="1" class="form-check-input" <?= !empty($config['require_medical_consent']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="require_medical_consent">Zgoda medyczna</label>
                </div>
                <div class="col-md-4 form-check">
                    <input type="checkbox" id="require_photo" name="require_photo" value="1" class="form-check-input" <?= !empty($config['require_photo']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="require_photo">Zdjecie czlonka</label>
                </div>
                <div class="col-md-4 form-check">
                    <input type="checkbox" id="require_parent_data_for_minors" name="require_parent_data_for_minors" value="1" class="form-check-input" <?= !empty($config['require_parent_data_for_minors']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="require_parent_data_for_minors">Dane opiekuna dla niepelnoletnich</label>
                </div>
            </div>
        </div>
    </div>

    <!-- Sekcja: limity wieku -->
    <div class="card mb-3">
        <div class="card-header"><strong>Limity wieku</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">Minimalny wiek (lata)</label>
                <input type="number" name="min_age_years" min="0" max="99" value="<?= View::e($config['min_age_years'] ?? '') ?>" class="form-control" placeholder="brak limitu">
            </div>
            <div class="col-md-4">
                <label class="form-label">Maksymalny wiek (lata)</label>
                <input type="number" name="max_age_years" min="0" max="99" value="<?= View::e($config['max_age_years'] ?? '') ?>" class="form-control" placeholder="brak limitu">
            </div>
            <div class="col-md-4">
                <label class="form-label">Wiek wymagajacy zgody rodzica</label>
                <input type="number" name="require_parent_consent_under_age" min="0" max="99" value="<?= View::e($config['require_parent_consent_under_age'] ?? 18) ?>" class="form-control">
            </div>
        </div>
    </div>

    <!-- Sekcja: auto-assign -->
    <div class="card mb-3">
        <div class="card-header"><strong>Auto-przypisania i powitanie</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label">Domyslny sport</label>
                <select name="auto_assign_sport_id" class="form-select">
                    <option value="">— brak —</option>
                    <?php foreach (($clubSports ?? []) as $cs): ?>
                        <option value="<?= (int)$cs['club_sport_id'] ?>"
                            <?= (int)($config['auto_assign_sport_id'] ?? 0) === (int)$cs['club_sport_id'] ? 'selected' : '' ?>>
                            <?= View::e($cs['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Domyslna stawka skladki</label>
                <select name="auto_assign_fee_rate_id" class="form-select">
                    <option value="">— brak —</option>
                    <?php foreach (($feeRates ?? []) as $rate): ?>
                        <option value="<?= (int)$rate['id'] ?>"
                            <?= (int)($config['auto_assign_fee_rate_id'] ?? 0) === (int)$rate['id'] ? 'selected' : '' ?>>
                            <?= View::e($rate['name']) ?> (<?= View::e(number_format((float)$rate['amount'], 2)) ?> zl / <?= View::e($rate['period']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Wyslij welcome email</label>
                <div class="form-check mt-2">
                    <input type="checkbox" id="auto_send_welcome_email" name="auto_send_welcome_email" value="1" class="form-check-input" <?= !empty($config['auto_send_welcome_email']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="auto_send_welcome_email">Automatycznie po dodaniu czlonka</label>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Szablon emaila powitalnego</label>
                <select name="welcome_email_template" class="form-select">
                    <option value="">— domyslny (welcome) —</option>
                    <?php foreach (($events ?? []) as $ev): ?>
                        <option value="<?= View::e($ev['code']) ?>"
                            <?= ($config['welcome_email_template'] ?? '') === $ev['code'] ? 'selected' : '' ?>>
                            <?= View::e($ev['name']) ?> (<?= View::e($ev['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Klucz template z katalogu eventow. Pusto = uzyj globalnego "welcome".</small>
            </div>
        </div>
    </div>

    <!-- Sekcja: zgody -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Zgody konfigurowalne (RODO, regulamin, marketing)</strong>
            <button type="button" class="btn btn-sm btn-outline-primary" id="add-consent">
                <i class="bi bi-plus-lg"></i> Dodaj zgode
            </button>
        </div>
        <div class="card-body">
            <p class="text-muted small">Kazda zgoda bedzie wyswietlana na formularzu dodawania czlonka. Klucz to unikatowy identyfikator (np. <code>rodo</code>), label widoczny dla uzytkownika.</p>
            <div id="consents-list">
                <?php foreach ($consents as $i => $c): ?>
                    <div class="row g-2 mb-2 consent-row border-bottom pb-2">
                        <div class="col-md-2"><input type="text" name="consents[<?= $i ?>][key]" value="<?= View::e($c['key'] ?? '') ?>" placeholder="key" class="form-control form-control-sm"></div>
                        <div class="col-md-3"><input type="text" name="consents[<?= $i ?>][label]" value="<?= View::e($c['label'] ?? '') ?>" placeholder="Etykieta" class="form-control form-control-sm"></div>
                        <div class="col-md-4"><textarea name="consents[<?= $i ?>][body]" rows="1" placeholder="Tresc zgody" class="form-control form-control-sm"><?= View::e($c['body'] ?? '') ?></textarea></div>
                        <div class="col-md-1"><input type="text" name="consents[<?= $i ?>][version]" value="<?= View::e($c['version'] ?? '1.0') ?>" placeholder="wersja" class="form-control form-control-sm"></div>
                        <div class="col-md-1 form-check pt-2">
                            <input type="checkbox" name="consents[<?= $i ?>][required]" value="1" class="form-check-input" <?= !empty($c['required']) ? 'checked' : '' ?>>
                            <label class="form-check-label small">Wym.</label>
                        </div>
                        <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Sekcja: custom fields -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Pola dodatkowe (custom)</strong>
            <button type="button" class="btn btn-sm btn-outline-primary" id="add-field">
                <i class="bi bi-plus-lg"></i> Dodaj pole
            </button>
        </div>
        <div class="card-body">
            <p class="text-muted small">Dla pol typu "select" podaj opcje rozdzielone przecinkiem.</p>
            <div id="fields-list">
                <?php foreach ($fields as $i => $f): ?>
                    <div class="row g-2 mb-2 field-row border-bottom pb-2">
                        <div class="col-md-2"><input type="text" name="custom_fields[<?= $i ?>][key]" value="<?= View::e($f['key'] ?? '') ?>" placeholder="key" class="form-control form-control-sm"></div>
                        <div class="col-md-3"><input type="text" name="custom_fields[<?= $i ?>][label]" value="<?= View::e($f['label'] ?? '') ?>" placeholder="Etykieta" class="form-control form-control-sm"></div>
                        <div class="col-md-2">
                            <select name="custom_fields[<?= $i ?>][type]" class="form-select form-select-sm">
                                <?php foreach (['text','select','number','date','textarea','checkbox'] as $t): ?>
                                    <option value="<?= $t ?>" <?= ($f['type'] ?? 'text') === $t ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><input type="text" name="custom_fields[<?= $i ?>][options]" value="<?= View::e(is_array($f['options'] ?? null) ? implode(',', $f['options']) : '') ?>" placeholder="opcje, przecinkami" class="form-control form-control-sm"></div>
                        <div class="col-md-1 form-check pt-2">
                            <input type="checkbox" name="custom_fields[<?= $i ?>][required]" value="1" class="form-check-input" <?= !empty($f['required']) ? 'checked' : '' ?>>
                            <label class="form-check-label small">Wym.</label>
                        </div>
                        <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz konfiguracje</button>
        <a href="<?= url('dashboard') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>

<script>
(function() {
    let consentIdx = <?= count($consents) ?>;
    let fieldIdx = <?= count($fields) ?>;

    function addConsent() {
        const html = `<div class="row g-2 mb-2 consent-row border-bottom pb-2">
            <div class="col-md-2"><input type="text" name="consents[${consentIdx}][key]" placeholder="key" class="form-control form-control-sm"></div>
            <div class="col-md-3"><input type="text" name="consents[${consentIdx}][label]" placeholder="Etykieta" class="form-control form-control-sm"></div>
            <div class="col-md-4"><textarea name="consents[${consentIdx}][body]" rows="1" placeholder="Tresc zgody" class="form-control form-control-sm"></textarea></div>
            <div class="col-md-1"><input type="text" name="consents[${consentIdx}][version]" value="1.0" placeholder="wersja" class="form-control form-control-sm"></div>
            <div class="col-md-1 form-check pt-2"><input type="checkbox" name="consents[${consentIdx}][required]" value="1" class="form-check-input"><label class="form-check-label small">Wym.</label></div>
            <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></div>
        </div>`;
        document.getElementById('consents-list').insertAdjacentHTML('beforeend', html);
        consentIdx++;
    }

    function addField() {
        const html = `<div class="row g-2 mb-2 field-row border-bottom pb-2">
            <div class="col-md-2"><input type="text" name="custom_fields[${fieldIdx}][key]" placeholder="key" class="form-control form-control-sm"></div>
            <div class="col-md-3"><input type="text" name="custom_fields[${fieldIdx}][label]" placeholder="Etykieta" class="form-control form-control-sm"></div>
            <div class="col-md-2"><select name="custom_fields[${fieldIdx}][type]" class="form-select form-select-sm">
                <option value="text">text</option><option value="select">select</option><option value="number">number</option>
                <option value="date">date</option><option value="textarea">textarea</option><option value="checkbox">checkbox</option>
            </select></div>
            <div class="col-md-3"><input type="text" name="custom_fields[${fieldIdx}][options]" placeholder="opcje, przecinkami" class="form-control form-control-sm"></div>
            <div class="col-md-1 form-check pt-2"><input type="checkbox" name="custom_fields[${fieldIdx}][required]" value="1" class="form-check-input"><label class="form-check-label small">Wym.</label></div>
            <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></div>
        </div>`;
        document.getElementById('fields-list').insertAdjacentHTML('beforeend', html);
        fieldIdx++;
    }

    document.getElementById('add-consent').addEventListener('click', addConsent);
    document.getElementById('add-field').addEventListener('click', addField);
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row')) {
            e.target.closest('.consent-row, .field-row')?.remove();
        }
    });
})();
</script>
