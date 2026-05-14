<?php
use App\Helpers\View;
$isEdit = !empty($member);
$action = $isEdit ? url('members/' . (int)$member['id'] . '/update') : url('members/store');
$assignedIds = [];
if (!empty($sports)) {
    foreach ($sports as $s) { $assignedIds[] = (int)$s['club_sport_id']; }
}
// Onboarding config (BC-safe: defaults gdy brak)
$obc = $onboardingConfig ?? [
    'require_pesel' => 0, 'require_address' => 0, 'require_emergency_contact' => 0,
    'require_medical_consent' => 0, 'require_photo' => 0,
    'custom_consents' => [], 'custom_fields' => [],
];
$obcConsents = is_array($obc['custom_consents'] ?? null) ? $obc['custom_consents'] : [];
$obcFields   = is_array($obc['custom_fields']   ?? null) ? $obc['custom_fields']   : [];
?>
<form method="POST" action="<?= $action ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label"><?= __('form.member_number') ?> *</label>
            <input type="text" name="member_number" value="<?= View::e($member['member_number'] ?? $nextNumber ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= __('form.first_name') ?> *</label>
            <input type="text" name="first_name" value="<?= View::e($member['first_name'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= __('form.last_name') ?> *</label>
            <input type="text" name="last_name" value="<?= View::e($member['last_name'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= __('form.status') ?></label>
            <select name="status" class="form-select">
                <?php foreach (['aktywny','zawieszony','wykreslony','urlop'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($member['status'] ?? 'aktywny') === $s ? 'selected' : '' ?>><?= __('status.' . $s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= __('form.pesel') ?><?= !empty($obc['require_pesel']) ? ' *' : '' ?></label>
            <input type="text" name="pesel" value="<?= View::e($member['pesel'] ?? '') ?>" class="form-control" maxlength="11" <?= !empty($obc['require_pesel']) ? 'required' : '' ?>>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= __('form.birth_date') ?></label>
            <input type="date" name="birth_date" value="<?= View::e($member['birth_date'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= __('form.gender') ?></label>
            <select name="gender" class="form-select">
                <option value="">—</option>
                <option value="M" <?= ($member['gender'] ?? '') === 'M' ? 'selected' : '' ?>><?= __('form.gender_m') ?></option>
                <option value="K" <?= ($member['gender'] ?? '') === 'K' ? 'selected' : '' ?>><?= __('form.gender_f') ?></option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?= __('form.join_date') ?> *</label>
            <input type="date" name="join_date" value="<?= View::e($member['join_date'] ?? date('Y-m-d')) ?>" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label"><?= __('form.email') ?></label>
            <input type="email" name="email" value="<?= View::e($member['email'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label"><?= __('form.phone') ?></label>
            <input type="text" name="phone" value="<?= View::e($member['phone'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label"><?= __('form.address_street') ?><?= !empty($obc['require_address']) ? ' *' : '' ?></label>
            <input type="text" name="address_street" value="<?= View::e($member['address_street'] ?? '') ?>" class="form-control" <?= !empty($obc['require_address']) ? 'required' : '' ?>>
        </div>
        <div class="col-md-4">
            <label class="form-label"><?= __('form.city') ?><?= !empty($obc['require_address']) ? ' *' : '' ?></label>
            <input type="text" name="address_city" value="<?= View::e($member['address_city'] ?? '') ?>" class="form-control" <?= !empty($obc['require_address']) ? 'required' : '' ?>>
        </div>
        <div class="col-md-2">
            <label class="form-label"><?= __('form.address_postal') ?></label>
            <input type="text" name="address_postal" value="<?= View::e($member['address_postal'] ?? '') ?>" class="form-control">
        </div>

        <?php if (!empty($obc['require_emergency_contact'])): ?>
        <div class="col-md-12">
            <label class="form-label">Kontakt awaryjny (imie + telefon) *</label>
            <input type="text" name="emergency_contact" value="<?= View::e($_POST['emergency_contact'] ?? '') ?>" class="form-control" required>
        </div>
        <?php endif; ?>

        <div class="col-12">
            <label class="form-label"><?= __('form.sports_sections') ?></label>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach (($clubSports ?? []) as $cs): ?>
                    <label class="form-check">
                        <input type="checkbox" name="club_sport_ids[]"
                               value="<?= (int)$cs['club_sport_id'] ?>"
                               class="form-check-input"
                               <?= in_array((int)$cs['club_sport_id'], $assignedIds, true) ? 'checked' : '' ?>>
                        <span class="form-check-label">
                            <i class="bi <?= View::e($cs['icon']) ?>" style="color: <?= View::e($cs['color']) ?>"></i>
                            <?= View::e($cs['name']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label"><?= __('form.notes') ?></label>
            <textarea name="notes" class="form-control" rows="3"><?= View::e($member['notes'] ?? '') ?></textarea>
        </div>

        <?php if (!empty($obcFields)): ?>
        <div class="col-12 mt-3">
            <h6 class="border-bottom pb-2">Pola dodatkowe</h6>
            <div class="row g-3">
                <?php foreach ($obcFields as $f): ?>
                    <?php
                    $fk = $f['key'] ?? '';
                    $fl = $f['label'] ?? $fk;
                    $ft = $f['type'] ?? 'text';
                    $fr = !empty($f['required']);
                    $cur = $_POST['custom_field'][$fk] ?? '';
                    ?>
                    <div class="col-md-6">
                        <label class="form-label"><?= View::e($fl) ?><?= $fr ? ' *' : '' ?></label>
                        <?php if ($ft === 'select'): ?>
                            <select name="custom_field[<?= View::e($fk) ?>]" class="form-select" <?= $fr ? 'required' : '' ?>>
                                <option value="">— wybierz —</option>
                                <?php foreach (($f['options'] ?? []) as $opt): ?>
                                    <option value="<?= View::e($opt) ?>" <?= $cur === $opt ? 'selected' : '' ?>><?= View::e($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($ft === 'textarea'): ?>
                            <textarea name="custom_field[<?= View::e($fk) ?>]" rows="2" class="form-control" <?= $fr ? 'required' : '' ?>><?= View::e($cur) ?></textarea>
                        <?php elseif ($ft === 'checkbox'): ?>
                            <div class="form-check">
                                <input type="checkbox" name="custom_field[<?= View::e($fk) ?>]" value="1" class="form-check-input" <?= !empty($cur) ? 'checked' : '' ?> <?= $fr ? 'required' : '' ?>>
                            </div>
                        <?php else: ?>
                            <input type="<?= View::e($ft) ?>" name="custom_field[<?= View::e($fk) ?>]" value="<?= View::e($cur) ?>" class="form-control" <?= $fr ? 'required' : '' ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($obc['require_medical_consent']) || !empty($obcConsents)): ?>
        <div class="col-12 mt-3">
            <h6 class="border-bottom pb-2">Zgody</h6>
            <?php if (!empty($obc['require_medical_consent'])): ?>
                <div class="form-check mb-2">
                    <input type="checkbox" id="consent_medical" name="consent[medical]" value="1" class="form-check-input" required>
                    <label class="form-check-label" for="consent_medical">
                        <span class="text-danger">*</span> Oswiadczam, ze nie ma przeciwwskazan medycznych do uprawiania sportu w klubie.
                    </label>
                </div>
            <?php endif; ?>
            <?php foreach ($obcConsents as $c): ?>
                <?php $ck = $c['key'] ?? ''; if ($ck === '') continue; ?>
                <div class="form-check mb-2">
                    <input type="checkbox" id="consent_<?= View::e($ck) ?>" name="consent[<?= View::e($ck) ?>]" value="1" class="form-check-input" <?= !empty($c['required']) ? 'required' : '' ?>>
                    <label class="form-check-label" for="consent_<?= View::e($ck) ?>">
                        <?php if (!empty($c['required'])): ?><span class="text-danger">*</span> <?php endif; ?>
                        <?= View::e($c['label'] ?? $ck) ?>
                        <?php if (!empty($c['body'])): ?>
                            <small class="text-muted d-block"><?= nl2br(View::e($c['body'])) ?></small>
                        <?php endif; ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> <?= __('btn.save') ?></button>
        <a href="<?= url('members') ?>" class="btn btn-outline-secondary"><?= __('btn.cancel') ?></a>
    </div>
</form>
