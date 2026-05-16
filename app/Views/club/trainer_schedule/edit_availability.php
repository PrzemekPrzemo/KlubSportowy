<?php
use App\Helpers\View;
$weekdays = [1=>'Poniedzialek', 2=>'Wtorek', 3=>'Sroda', 4=>'Czwartek', 5=>'Piatek', 6=>'Sobota', 7=>'Niedziela'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><i class="bi bi-clock"></i> Dostepnosc: <?= View::e($trainer['full_name'] ?? $trainer['username']) ?></h2>
    <a href="<?= url('club/trainer-schedule') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrot
    </a>
</div>

<form method="POST" action="<?= url('club/trainer-schedule/' . (int)$trainer['id'] . '/availability/store') ?>" class="card p-3">
    <?= csrf_field() ?>
    <p class="text-muted small">
        Dodaj sloty dostepnosci dla kazdego dnia tygodnia. Slot "globalny" obowiazuje
        we wszystkich klubach, w ktorych pracuje trener. Slot "klubowy" tylko w aktualnym klubie.
    </p>

    <div id="slotsContainer"></div>

    <div class="d-flex gap-2 mt-3">
        <button type="button" class="btn btn-outline-primary btn-sm" id="addSlotBtn">
            <i class="bi bi-plus"></i> Dodaj slot
        </button>
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz dostepnosc</button>
    </div>
</form>

<template id="slotRowTpl">
    <div class="row g-2 align-items-end mb-2 slot-row border-bottom pb-2">
        <div class="col-md-2">
            <label class="form-label small">Dzien</label>
            <select name="slots[__IDX__][weekday]" class="form-select form-select-sm">
                <?php foreach ($weekdays as $wd => $lbl): ?>
                    <option value="<?= $wd ?>"><?= $lbl ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Od</label>
            <input type="time" name="slots[__IDX__][time_start]" class="form-control form-control-sm" value="09:00" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Do</label>
            <input type="time" name="slots[__IDX__][time_end]" class="form-control form-control-sm" value="17:00" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Zakres</label>
            <select name="slots[__IDX__][scope]" class="form-select form-select-sm">
                <option value="club">Tylko ten klub</option>
                <option value="global">Globalna</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Od daty</label>
            <input type="date" name="slots[__IDX__][valid_from]" class="form-control form-control-sm">
        </div>
        <div class="col-md-1">
            <label class="form-label small">Do daty</label>
            <input type="date" name="slots[__IDX__][valid_until]" class="form-control form-control-sm">
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-outline-danger btn-sm rm-slot"><i class="bi bi-x"></i></button>
        </div>
    </div>
</template>

<script>
(function() {
    var idx = 0;
    var container = document.getElementById('slotsContainer');
    var tpl = document.getElementById('slotRowTpl').innerHTML;

    function addRow(prefill) {
        var html = tpl.replace(/__IDX__/g, idx++);
        var wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        var row = wrap.firstChild;
        if (prefill) {
            row.querySelector('[name$="[weekday]"]').value = prefill.weekday;
            row.querySelector('[name$="[time_start]"]').value = prefill.time_start;
            row.querySelector('[name$="[time_end]"]').value = prefill.time_end;
            row.querySelector('[name$="[scope]"]').value = prefill.scope;
            if (prefill.valid_from) row.querySelector('[name$="[valid_from]"]').value = prefill.valid_from;
            if (prefill.valid_until) row.querySelector('[name$="[valid_until]"]').value = prefill.valid_until;
        }
        row.querySelector('.rm-slot').addEventListener('click', function() { row.remove(); });
        container.appendChild(row);
    }

    document.getElementById('addSlotBtn').addEventListener('click', function() { addRow(null); });

    var existing = <?= json_encode(array_map(function($a) {
        return [
            'weekday'     => (int)$a['weekday'],
            'time_start'  => substr((string)$a['time_start'], 0, 5),
            'time_end'    => substr((string)$a['time_end'], 0, 5),
            'scope'       => empty($a['club_id']) ? 'global' : 'club',
            'valid_from'  => $a['valid_from'] ?? '',
            'valid_until' => $a['valid_until'] ?? '',
        ];
    }, $availability)) ?>;
    if (existing.length === 0) {
        addRow({weekday:1, time_start:'09:00', time_end:'17:00', scope:'club'});
    } else {
        existing.forEach(addRow);
    }
})();
</script>
