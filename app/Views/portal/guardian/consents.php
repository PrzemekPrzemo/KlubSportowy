<?php
use App\Helpers\Csrf;
use App\Helpers\View;
use App\Models\GuardianMinorConsentModel;
?>
<a href="<?= View::e(url('portal/guardian/child/' . (int)$member['id'])) ?>" class="small text-decoration-none">
    <i class="bi bi-arrow-left"></i> Wroc do profilu
</a>

<h2 class="h5 mt-2">Zgody RODO — <?= View::e(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')) ?></h2>
<p class="small text-muted">
    Jako opiekun masz wylaczne prawo decydowac o przetwarzaniu danych dziecka
    (RODO art. 8). Mozesz w kazdej chwili udzielic lub odwolac zgody.
    Kazda zmiana jest logowana z timestampem i adresem IP.
</p>

<form method="post" action="<?= View::e(url('portal/guardian/child/' . (int)$member['id'] . '/consents')) ?>">
    <?= Csrf::field() ?>
    <div class="gp-card">
        <?php foreach ($types as $type): ?>
            <?php
            $c       = $consents[$type] ?? null;
            $granted = !empty($c['granted']) && empty($c['revoked_at']);
            ?>
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                <div class="me-3">
                    <div class="fw-semibold">
                        <?= View::e(GuardianMinorConsentModel::labelFor($type)) ?>
                    </div>
                    <?php if (!empty($c['granted_at']) && $granted): ?>
                        <div class="small text-muted">
                            Udzielono: <?= View::e($c['granted_at']) ?>
                        </div>
                    <?php elseif (!empty($c['revoked_at'])): ?>
                        <div class="small text-warning">
                            Odwolana: <?= View::e($c['revoked_at']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox"
                           name="consents[<?= View::e($type) ?>]"
                           value="1"
                           id="c-<?= View::e($type) ?>"
                           <?= $granted ? 'checked' : '' ?>>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-save"></i> Zapisz zgody
    </button>
</form>
