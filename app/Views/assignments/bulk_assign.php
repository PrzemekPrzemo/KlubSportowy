<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-people-fill text-primary me-2"></i>
        Masowe przypisanie składek
    </h3>
    <a href="<?= url('fees/assignments') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('warning')): ?>
    <div class="alert alert-warning"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="card p-4">
    <form method="POST" action="<?= url('fees/bulk-assign/store') ?>">
        <?= csrf_field() ?>

        <h5>Stawka opłat *</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <select name="fee_rate_id" class="form-select" required>
                    <option value="">— wybierz stawkę —</option>
                    <?php foreach ($rates as $r): ?>
                        <option value="<?= (int)$r['id'] ?>">
                            <?= View::e($r['name']) ?> — <?= number_format((float)$r['amount'], 2, ',', ' ') ?> PLN
                            (<?= View::e($r['period']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $key === 'active' ? 'selected' : '' ?>><?= View::e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h5>Okres obowiązywania</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Od</label>
                <input type="date" name="valid_from" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Do (opcjonalne)</label>
                <input type="date" name="valid_to" class="form-control">
            </div>
        </div>

        <h5>Filtr zawodników</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label">Sekcja sportowa</label>
                <select name="sport_id" class="form-select">
                    <option value="">Wszystkie</option>
                    <?php foreach (($clubSports ?? []) as $cs): ?>
                        <option value="<?= (int)$cs['club_sport_id'] ?>"><?= View::e($cs['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status zawodnika</label>
                <select name="status" class="form-select">
                    <option value="">Wszyscy</option>
                    <option value="aktywny" selected>Aktywni</option>
                    <option value="zawieszony">Zawieszeni</option>
                    <option value="urlop">Urlop</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Płeć</label>
                <select name="gender" class="form-select">
                    <option value="">Obie</option>
                    <option value="M">M</option>
                    <option value="K">K</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Wiek od / do</label>
                <div class="d-flex gap-1">
                    <input type="number" name="age_min" min="0" max="120" class="form-control" placeholder="od">
                    <input type="number" name="age_max" min="0" max="120" class="form-control" placeholder="do">
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            System pomija członków którzy już mają identyczną aktywną subskrypcję na wybraną datę.
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('fees/assignments') ?>" class="btn btn-outline-secondary">Anuluj</a>
            <button class="btn btn-primary" onclick="return confirm('Przypisać składkę wybranym członkom?')">
                <i class="bi bi-check-circle"></i> Przypisz masowo
            </button>
        </div>
    </form>
</div>
