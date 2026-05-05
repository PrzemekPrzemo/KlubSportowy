<?php
use App\Helpers\View;
$a = $assignment; // null = create mode
$isCreate = $a === null;
$action = $isCreate
    ? url('fees/assignments/store')
    : url('fees/assignments/' . (int)$a['id'] . '/update');
$attachedIds = $attachedIds ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-<?= $isCreate ? 'plus-circle' : 'pencil-square' ?> text-primary me-2"></i>
        <?= $isCreate ? 'Nowa subskrypcja opłat' : 'Edytuj subskrypcję' ?>
    </h3>
    <a href="<?= url('fees/assignments') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Anuluj
    </a>
</div>

<div class="card p-4">
    <form method="POST" action="<?= $action ?>" id="assignmentForm">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Zawodnik *</label>
                <select name="member_id" class="form-select" required <?= $isCreate ? '' : 'disabled' ?>>
                    <option value="">— wybierz —</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= (int)$m['id'] ?>"
                                <?= !$isCreate && (int)$a['member_id'] === (int)$m['id'] ? 'selected' : '' ?>>
                            <?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?>
                            <?= !empty($m['member_number']) ? '(#' . View::e($m['member_number']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!$isCreate): ?>
                    <input type="hidden" name="member_id" value="<?= (int)$a['member_id'] ?>">
                    <small class="text-muted">Zawodnik nie jest edytowalny — utwórz nową subskrypcję jeśli potrzeba.</small>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">Stawka opłat *</label>
                <select name="fee_rate_id" class="form-select" id="rateSelect" required>
                    <option value="">— wybierz —</option>
                    <?php foreach ($rates as $r): ?>
                        <option value="<?= (int)$r['id'] ?>"
                                data-amount="<?= (float)$r['amount'] ?>"
                                data-period="<?= View::e($r['period']) ?>"
                                <?= !$isCreate && (int)$a['fee_rate_id'] === (int)$r['id'] ? 'selected' : '' ?>>
                            <?= View::e($r['name']) ?>
                            — <?= format_money($r['amount']) ?>
                            <?= View::e($r['period']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Aktywna od *</label>
                <input type="date" name="valid_from" class="form-control"
                       value="<?= View::e($a['valid_from'] ?? date('Y-m-d')) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Aktywna do (opcjonalnie)</label>
                <input type="date" name="valid_to" class="form-control"
                       value="<?= View::e($a['valid_to'] ?? '') ?>">
                <small class="text-muted">Puste = bezterminowo</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status *</label>
                <select name="status" class="form-select" required>
                    <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?= View::e($key) ?>"
                                <?= ($a['status'] ?? 'active') === $key ? 'selected' : '' ?>>
                            <?= View::e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Zniżki (zaznacz dowolnie wiele)</label>
                <?php if (empty($discounts)): ?>
                    <div class="alert alert-info small">
                        Brak aktywnych zniżek w klubie.
                        <a href="<?= url('fees/discounts/new') ?>">Utwórz pierwszą</a>.
                    </div>
                <?php else: ?>
                    <div class="row g-2">
                        <?php foreach ($discounts as $d):
                            $isPercent = $d['discount_type'] === 'percent';
                            $checked = in_array((int)$d['id'], $attachedIds, true);
                        ?>
                            <div class="col-md-6">
                                <div class="form-check border rounded p-2 ps-4">
                                    <input type="checkbox" name="discount_ids[]"
                                           value="<?= (int)$d['id'] ?>"
                                           class="form-check-input discount-chk"
                                           id="disc_<?= (int)$d['id'] ?>"
                                           data-stackable="<?= !empty($d['is_stackable']) ? '1' : '0' ?>"
                                           <?= $checked ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="disc_<?= (int)$d['id'] ?>">
                                        <strong><?= View::e($d['name']) ?></strong>
                                        <span class="badge bg-<?= $isPercent ? 'info' : 'warning' ?> ms-1">
                                            <?php if ($isPercent): ?>
                                                -<?= number_format((float)$d['value'], 2) ?>%
                                            <?php else: ?>
                                                -<?= format_money($d['value']) ?>
                                            <?php endif; ?>
                                        </span>
                                        <?php if (empty($d['is_stackable'])): ?>
                                            <span class="badge bg-danger ms-1" title="Niestackable — blokuje pozostałe">solo</span>
                                        <?php endif; ?>
                                        <small class="d-block text-muted"><code><?= View::e($d['code']) ?></code></small>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Live preview kalkulacji -->
            <div class="col-12">
                <div class="card bg-light p-3" id="previewBox">
                    <strong><i class="bi bi-calculator me-1"></i> Podgląd kalkulacji</strong>
                    <table class="table table-sm mb-0 mt-2">
                        <tr><td>Stawka brutto:</td><td class="text-end font-monospace" id="prevGross"><?= isset($preview) ? format_money($preview['gross_amount']) : '—' ?></td></tr>
                        <tr><td>Suma zniżek:</td><td class="text-end font-monospace text-success" id="prevDiscount"><?= isset($preview) ? '-' . format_money($preview['discount_amount']) : '—' ?></td></tr>
                        <tr class="fw-bold border-top"><td>Do zapłaty:</td><td class="text-end font-monospace" id="prevNet"><?= isset($preview) ? format_money($preview['net_amount']) : '—' ?></td></tr>
                    </table>
                    <?php if (!empty($preview['breakdown'])): ?>
                        <small class="text-muted mt-1 d-block">
                            <?php foreach ($preview['breakdown'] as $b): ?>
                                <span class="badge bg-light text-secondary border me-1">
                                    <?= View::e($b['name']) ?>: -<?= format_money($b['amount']) ?>
                                </span>
                            <?php endforeach; ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label">Notatki</label>
                <textarea name="notes" class="form-control" rows="2"
                          placeholder="np. Senior, składka roczna, scholarship od 2026"><?= View::e($a['notes'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="<?= url('fees/assignments') ?>" class="btn btn-outline-secondary">Anuluj</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2 me-1"></i>
                <?= $isCreate ? 'Utwórz subskrypcję' : 'Zapisz zmiany' ?>
            </button>
        </div>
    </form>
</div>

<script>
// Live-preview kalkulacji: rate × wybrane zniżki = net
(function() {
    const rateSelect = document.getElementById('rateSelect');
    const checkboxes = document.querySelectorAll('.discount-chk');
    const elGross    = document.getElementById('prevGross');
    const elDisc     = document.getElementById('prevDiscount');
    const elNet      = document.getElementById('prevNet');
    if (!rateSelect || !elGross) return;

    function fmt(amt) {
        return amt.toFixed(2).replace('.', ',') + ' zł';
    }

    function recalc() {
        const opt = rateSelect.options[rateSelect.selectedIndex];
        const gross = parseFloat(opt?.dataset?.amount || 0);
        if (!gross) {
            elGross.textContent = '—';
            elDisc.textContent  = '—';
            elNet.textContent   = '—';
            return;
        }

        // Symulacja JS-side calculateNet (tu uproszczona — bez API call'a)
        // Wczytaj zaznaczone zniżki + ich type/value/stackable z DOM data attrs
        // (W finalnej wersji można zrobić AJAX call do /fees/assignments/preview)
        // Ale dla MVP wystarczy zbieranie z dataset. Tutaj fallback: tylko gross.
        elGross.textContent = fmt(gross);

        // Lekka logika JS (mirror calculateNet):
        let totalDiscount = 0;
        let hasNonStackable = false;
        for (const cb of checkboxes) {
            if (!cb.checked) continue;
            if (hasNonStackable) continue;
            // Brak surowych value/type w DOM — placeholder; final calc na server-side
        }
        // Bez exact JS calc: pokazuj brutto jako net, hint że final calc na save
        elDisc.textContent = '— (kalkulacja przy zapisie)';
        elNet.textContent  = fmt(gross);
    }
    rateSelect.addEventListener('change', recalc);
    checkboxes.forEach(cb => cb.addEventListener('change', recalc));
})();
</script>
