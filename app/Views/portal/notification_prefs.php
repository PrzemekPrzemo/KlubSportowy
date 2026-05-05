<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1"><i class="bi bi-bell text-primary me-2"></i>Preferencje powiadomień</h3>
        <p class="text-muted mb-0 small">
            Wybierz które powiadomienia od klubu chcesz otrzymywać i jakim kanałem.
        </p>
    </div>
    <a href="<?= url('portal/profile') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Profil
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>

<form method="POST" action="<?= url('portal/notification-prefs/update') ?>">
    <?= csrf_field() ?>

    <!-- Global opt-out (kill switch) -->
    <div class="card mb-3 <?= $globalOptOut ? 'border-warning' : '' ?>">
        <div class="card-body">
            <div class="form-check form-switch ps-5">
                <input type="hidden" name="global_opt_out" value="0">
                <input type="checkbox" name="global_opt_out" value="1"
                       id="globalOff" class="form-check-input"
                       <?= $globalOptOut ? 'checked' : '' ?>>
                <label class="form-check-label" for="globalOff">
                    <strong>Wycisz WSZYSTKIE powiadomienia od klubu</strong>
                    <small class="d-block text-muted">
                        Globalna pauza. Odznacz aby ustawić preferencje per typ.
                    </small>
                </label>
            </div>
        </div>
    </div>

    <!-- Per-template prefs -->
    <div class="card">
        <div class="card-header bg-light">
            <strong>Powiadomienia per typ</strong>
        </div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Typ powiadomienia</th>
                        <th class="text-center">Otrzymuję</th>
                        <th>Kanał</th>
                    </tr>
                </thead>
                <tbody class="<?= $globalOptOut ? 'opacity-50' : '' ?>" id="tplTable">
                <?php if (empty($templates)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-3">
                        Brak skonfigurowanych typów powiadomień w klubie.
                    </td></tr>
                <?php else: foreach ($templates as $t):
                    $type = $t['template_type'];
                    $existing = $prefByTemplate[$type] ?? null;
                    $isOptedOut = $existing !== null && !empty($existing['opted_out']);
                    $channel = $existing['channel'] ?? 'both';
                    $label = $templateLabels[$type] ?? ($t['name'] ?? $type);
                ?>
                    <tr class="tpl-block">
                        <td>
                            <strong><?= View::e($label) ?></strong>
                            <small class="d-block text-muted"><code><?= View::e($type) ?></code></small>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                <input type="hidden" name="prefs[<?= View::e($type) ?>][opted_out]" value="1">
                                <input type="checkbox" name="prefs[<?= View::e($type) ?>][opted_out]"
                                       value="0"
                                       class="form-check-input"
                                       <?= $isOptedOut ? '' : 'checked' ?>
                                       <?= $globalOptOut ? 'disabled' : '' ?>>
                            </div>
                            <small class="d-block text-muted">
                                <?= $isOptedOut ? '— wyciszone' : 'tak' ?>
                            </small>
                        </td>
                        <td>
                            <select name="prefs[<?= View::e($type) ?>][channel]"
                                    class="form-select form-select-sm" style="max-width: 200px"
                                    <?= $globalOptOut ? 'disabled' : '' ?>>
                                <option value="both" <?= $channel === 'both' ? 'selected' : '' ?>>E-mail + SMS</option>
                                <option value="email" <?= $channel === 'email' ? 'selected' : '' ?>>Tylko e-mail</option>
                                <option value="sms" <?= $channel === 'sms' ? 'selected' : '' ?>>Tylko SMS</option>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="alert alert-info mt-3 small">
        <i class="bi bi-info-circle me-1"></i>
        <strong>Ważne:</strong> nawet po wyciszeniu, klub może skontaktować się z Tobą
        w sprawach krytycznych (np. zmiana terminu treningu, sprawy bezpieczeństwa).
        Nie blokujemy też mailingów wymaganych prawnie (faktury, RODO, regulaminy).
    </div>

    <div class="text-end mt-3">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2 me-1"></i> Zapisz preferencje
        </button>
    </div>
</form>

<script>
// JS: szare wyciszenie tabeli gdy global toggle, ale również disable input'ów
(function() {
    const globalChk = document.getElementById('globalOff');
    if (!globalChk) return;
    function refresh() {
        const off = globalChk.checked;
        document.querySelectorAll('#tplTable input[type="checkbox"], #tplTable select')
            .forEach(el => el.disabled = off);
        document.getElementById('tplTable').classList.toggle('opacity-50', off);
    }
    globalChk.addEventListener('change', refresh);
})();
</script>
