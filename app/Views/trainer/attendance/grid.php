<?php
use App\Helpers\View;
use App\Helpers\Csrf;

$t = $training;
?>
<style>
/* Mobile-friendly attendance grid */
.attendance-grid .att-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
    padding: 0.75rem;
    border-bottom: 1px solid #dee2e6;
    align-items: center;
}
@media (min-width: 768px) {
    .attendance-grid .att-row {
        grid-template-columns: minmax(180px, 1fr) auto minmax(220px, 1fr);
    }
}
.attendance-grid .status-radios label.btn {
    min-width: 56px;
    padding: 0.5rem 0.75rem; /* touch-friendly */
}
.attendance-sticky-bar {
    position: sticky;
    bottom: 0;
    z-index: 10;
    background: #fff;
    border-top: 1px solid #dee2e6;
    padding: 0.75rem;
    box-shadow: 0 -2px 6px rgba(0,0,0,0.05);
}
</style>

<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <div>
        <h2 class="mb-1"><i class="bi bi-check2-square"></i> Obecnosc</h2>
        <div class="text-muted">
            <strong><?= View::e($t['name']) ?></strong>
            · <i class="bi bi-clock"></i> <?= View::e($t['start_time']) ?>
            <?php if (!empty($t['location'])): ?>
                · <i class="bi bi-geo-alt"></i> <?= View::e($t['location']) ?>
            <?php endif; ?>
        </div>
    </div>
    <div>
        <a href="<?= url('trainer/dashboard') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Panel
        </a>
        <a href="<?= url('trainings/' . (int)$t['id']) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-eye"></i> Szczegoly
        </a>
    </div>
</div>

<?php if (!$editable): ?>
    <div class="alert alert-warning">
        <i class="bi bi-lock"></i>
        Edycja zablokowana: trening odbyl sie ponad <?= (int)$pastLimitDays ?> dni temu.
        Zmiany moze wprowadzic tylko zarzad/admin.
    </div>
<?php endif; ?>

<?php if (empty($attendees)): ?>
    <div class="alert alert-info">
        Nikt jeszcze nie zapisal sie na ten trening.
        <a href="<?= url('trainings/' . (int)$t['id']) ?>">Dodaj zawodnikow</a> w widoku treningu.
    </div>
<?php else: ?>

<form method="post" action="<?= url('trainer/training/' . (int)$t['id'] . '/attendance/save') ?>">
    <?= Csrf::field() ?>

    <?php if ($editable): ?>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <button type="submit" name="bulk_action" value="all_present"
                class="btn btn-sm btn-outline-success">
            <i class="bi bi-check-all"></i> Wszyscy obecni
        </button>
        <button type="submit" name="bulk_action" value="all_absent"
                class="btn btn-sm btn-outline-danger">
            <i class="bi bi-x-octagon"></i> Wszyscy nieobecni
        </button>
    </div>
    <?php endif; ?>

    <div class="card attendance-grid">
        <div class="card-body p-0">
            <?php foreach ($attendees as $a): ?>
                <?php
                $aid     = (int)$a['id'];
                $current = (string)$a['status'];
                $full    = trim($a['last_name'] . ' ' . $a['first_name']);
                ?>
                <div class="att-row">
                    <div>
                        <strong><?= View::e($full) ?></strong>
                        <small class="text-muted d-block">
                            <?= View::e($a['member_number']) ?>
                        </small>
                    </div>
                    <div class="status-radios btn-group btn-group-sm"
                         role="group"
                         aria-label="Status obecnosci dla <?= View::e($full) ?>">
                        <?php
                        $opts = [
                            'obecny'    => ['Obecny',    'success'],
                            'spozniony' => ['Spoz.',     'warning'],
                            'nieobecny' => ['Nieob.',    'danger'],
                            'wypisany'  => ['Anul.',     'secondary'],
                        ];
                        foreach ($opts as $val => [$label, $color]):
                            $id  = 'st_' . $aid . '_' . $val;
                            $checked = $current === $val ? 'checked' : '';
                            $disabled = !$editable ? 'disabled' : '';
                        ?>
                            <input type="radio"
                                   class="btn-check"
                                   name="status[<?= $aid ?>]"
                                   id="<?= $id ?>"
                                   value="<?= $val ?>"
                                   <?= $checked ?> <?= $disabled ?>>
                            <label class="btn btn-outline-<?= $color ?>" for="<?= $id ?>"><?= $label ?></label>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <input type="text"
                               class="form-control form-control-sm"
                               name="notes[<?= $aid ?>]"
                               value="<?= View::e((string)($a['notes'] ?? '')) ?>"
                               placeholder="Notatka (opcjonalnie)"
                               maxlength="255"
                               <?= !$editable ? 'disabled' : '' ?>>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($editable): ?>
    <div class="attendance-sticky-bar text-end">
        <button type="submit" class="btn btn-primary btn-lg w-100 w-md-auto">
            <i class="bi bi-save"></i> Zapisz obecnosc
        </button>
    </div>
    <?php endif; ?>
</form>

<?php endif; ?>
